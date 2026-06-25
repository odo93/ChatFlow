<?php
/**
 * MolkFilm_Setup
 *
 * Runs once on plugin activation.  Creates:
 *  - Course categories (Tutor taxonomy: course-category)
 *  - Core WP pages (Home, Courses, About, Contact, WC pages)
 *  - WooCommerce option flags (shop page, checkout page, etc.)
 *  - Permalink structure (/course/%postname%)
 *  - One sample course with WC product, 3 lessons, custom meta
 *  - Global plugin option defaults
 */

defined( 'ABSPATH' ) || exit;

class MolkFilm_Setup {

    /**
     * Entry point — called from register_activation_hook.
     */
    public static function run() {
        self::create_categories();
        self::create_pages();
        self::set_woocommerce_options();
        self::set_permalink();
        self::seed_sample_course();
        self::set_plugin_defaults();
    }

    // ── Course categories ─────────────────────────────────────────────────────

    private static function create_categories() {
        $taxonomy = 'course-category'; // Tutor LMS taxonomy slug

        $cats = [
            [ 'name' => 'صناعة الأفلام',    'slug' => 'filmmaking',         'desc' => 'كل ما يتعلق بصناعة الأفلام' ],
            [ 'name' => 'الإخراج',           'slug' => 'directing',          'desc' => 'فنون الإخراج السينمائي' ],
            [ 'name' => 'المونتاج',          'slug' => 'editing',            'desc' => 'مونتاج الصوت والصورة' ],
            [ 'name' => 'كتابة السيناريو',   'slug' => 'screenwriting',      'desc' => 'فن كتابة السيناريو' ],
        ];

        foreach ( $cats as $cat ) {
            if ( ! term_exists( $cat['slug'], $taxonomy ) ) {
                wp_insert_term(
                    $cat['name'],
                    $taxonomy,
                    [ 'slug' => $cat['slug'], 'description' => $cat['desc'] ]
                );
            }
        }
    }

    // ── Core pages ────────────────────────────────────────────────────────────

    private static function create_pages() {
        $pages = [
            'home'        => [ 'title' => 'الرئيسية',     'slug' => 'home' ],
            'courses'     => [ 'title' => 'الدورات',       'slug' => 'courses' ],
            'about'       => [ 'title' => 'عن الأكاديمية', 'slug' => 'about' ],
            'contact'     => [ 'title' => 'تواصل معنا',    'slug' => 'contact' ],
            'checkout'    => [ 'title' => 'الدفع',          'slug' => 'checkout',    'content' => '[woocommerce_checkout]' ],
            'my-account'  => [ 'title' => 'حسابي',          'slug' => 'my-account',  'content' => '[woocommerce_my_account]' ],
            'cart'        => [ 'title' => 'عربة التسوق',   'slug' => 'cart',        'content' => '[woocommerce_cart]' ],
            'dashboard'   => [ 'title' => 'لوحة المتعلم',   'slug' => 'dashboard' ],
        ];

        $created_ids = [];
        foreach ( $pages as $key => $page ) {
            $existing = get_page_by_path( $page['slug'] );
            if ( $existing ) {
                $created_ids[ $key ] = $existing->ID;
                continue;
            }
            $id = wp_insert_post( [
                'post_title'   => $page['title'],
                'post_name'    => $page['slug'],
                'post_content' => $page['content'] ?? '',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ] );
            if ( ! is_wp_error( $id ) ) {
                $created_ids[ $key ] = $id;
            }
        }

        // Set front page to 'home' and posts page to blog (optional)
        if ( isset( $created_ids['home'] ) ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $created_ids['home'] );
        }

        // Store page IDs for WC
        $wc_page_map = [
            'woocommerce_checkout_page_id'   => 'checkout',
            'woocommerce_myaccount_page_id'  => 'my-account',
            'woocommerce_cart_page_id'       => 'cart',
            'woocommerce_shop_page_id'       => 'courses',
        ];
        foreach ( $wc_page_map as $option => $key ) {
            if ( isset( $created_ids[ $key ] ) ) {
                update_option( $option, $created_ids[ $key ] );
            }
        }

        update_option( 'molkfilm_page_ids', $created_ids );
    }

    // ── WooCommerce options ───────────────────────────────────────────────────

    private static function set_woocommerce_options() {
        $defaults = [
            'woocommerce_currency'              => 'EGP',
            'woocommerce_currency_pos'          => 'right_space',
            'woocommerce_price_num_decimals'    => '0',
            'woocommerce_default_country'       => 'EG',
            'woocommerce_calc_taxes'            => 'no',
            'woocommerce_enable_guest_checkout' => 'yes',
            'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
            'woocommerce_enable_myaccount_registration' => 'yes',
            'woocommerce_registration_generate_username' => 'yes',
            'woocommerce_registration_generate_password' => 'yes',
            'woocommerce_force_ssl_checkout'    => 'no', // Set to 'yes' on live
            'woocommerce_email_from_name'       => 'ملك فيلم',
            'woocommerce_email_from_address'    => 'no-reply@molkfilm.com',
        ];
        foreach ( $defaults as $key => $value ) {
            // Only set if not already configured by a previous WC run
            if ( get_option( $key ) === false ) {
                update_option( $key, $value );
            }
        }
    }

    // ── Permalink structure ───────────────────────────────────────────────────

    private static function set_permalink() {
        // /course/%postname%
        global $wp_rewrite;
        update_option( 'permalink_structure', '/course/%postname%/' );
        if ( isset( $wp_rewrite ) ) {
            $wp_rewrite->set_permalink_structure( '/course/%postname%/' );
            $wp_rewrite->flush_rules( true );
        }
    }

    // ── Sample course ─────────────────────────────────────────────────────────

    private static function seed_sample_course() {
        // Idempotent: skip if already seeded
        if ( get_option( 'molkfilm_sample_seeded' ) ) {
            return;
        }

        /* 1. Create WooCommerce product (Simple product) */
        $product = new WC_Product_Simple();
        $product->set_name( 'ماستر كلاس — صناعة الأفلام الوثائقية' );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        $product->set_regular_price( '4500' );
        $product->set_virtual( true );
        $product->set_sold_individually( true );
        $product->set_description( 'رحلة الفيلم الوثائقي من الفكرة إلى التوزيع — ٢١ ساعة تدريب مكثف مع المخرج علي حجازي.' );
        $product_id = $product->save();

        if ( ! $product_id || is_wp_error( $product_id ) ) {
            return; // WC not ready yet; bail silently
        }

        /* 2. Get filmmaking category term_id */
        $term = get_term_by( 'slug', 'filmmaking', 'course-category' );
        $term_id = $term ? [ $term->term_id ] : [];

        /* 3. Create the Tutor LMS course (CPT: courses) */
        $course_id = wp_insert_post( [
            'post_type'    => 'courses',
            'post_title'   => 'ماستر كلاس — صناعة الأفلام الوثائقية',
            'post_name'    => 'masterclass-documentary-filmmaking',
            'post_excerpt' => 'رحلة الفيلم من الفكرة إلى التوزيع',
            'post_content' => '<p>تعلّم صناعة الأفلام الوثائقية من الصفر وحتى التوزيع الاحترافي. يقودك المخرج علي حجازي خلال ٢١ ساعة من المحتوى العملي والنظري المكثّف.</p>
<p><strong>ماذا ستتعلم؟</strong></p>
<ul>
<li>أساسيات الفيلم الوثائقي وأنواعه</li>
<li>البحث والتطوير للأفكار</li>
<li>التصوير والإضاءة الميدانية</li>
<li>المونتاج والموسيقى</li>
<li>التوزيع على المهرجانات والمنصات</li>
</ul>',
            'post_status'  => 'publish',
            'post_author'  => 1,
        ] );

        if ( ! $course_id || is_wp_error( $course_id ) ) {
            return;
        }

        // Assign category
        if ( $term_id ) {
            wp_set_post_terms( $course_id, $term_id, 'course-category' );
        }

        // Link WC product to Tutor course
        update_post_meta( $course_id, '_tutor_course_price_type', 'paid' );
        update_post_meta( $course_id, 'course_price_type', 'paid' );

        // Tutor stores the linked product ID differently across versions:
        update_post_meta( $course_id, '_tutor_course_product_id', $product_id );
        update_post_meta( $course_id, 'course_product_id', $product_id );

        // Custom meta fields (defined in class-course-fields.php)
        $meta_map = [
            '_mf_mode'                     => 'online',
            '_mf_total_seats'              => 30,
            '_mf_seats_taken'              => 0,
            '_mf_session_count'            => 18,
            '_mf_session_duration_minutes' => 70,
            '_mf_total_hours_text'         => '21 ساعة',
            '_mf_start_date'               => date( 'Y-m-d', strtotime( '+14 days' ) ),
            '_mf_schedule_text'            => 'كل سبت — ٦ مساءً بتوقيت القاهرة',
            '_mf_instructor_name'          => 'علي حجازي',
            '_mf_online_link'              => 'https://zoom.us/j/XXXXXXXXXX', // TODO: replace
        ];
        foreach ( $meta_map as $key => $val ) {
            update_post_meta( $course_id, $key, $val );
        }

        /* 4. Create a Tutor topic (section) */
        $topic_id = wp_insert_post( [
            'post_type'    => 'topics',
            'post_title'   => 'الوحدة الأولى: مقدمة في الفيلم الوثائقي',
            'post_status'  => 'publish',
            'post_parent'  => $course_id,
            'post_author'  => 1,
            'menu_order'   => 0,
        ] );

        /* 5. Create 3 sample lessons */
        $lessons = [
            [
                'title'        => 'ما هو الفيلم الوثائقي؟ (معاينة مجانية)',
                'content'      => '<p>في هذا الدرس نستعرض تعريف الفيلم الوثائقي وتاريخه وأنواعه الرئيسية.</p>',
                'video_url'    => 'https://www.youtube.com/watch?v=XXXXXXXXXX',
                'is_preview'   => 1,
                'menu_order'   => 0,
            ],
            [
                'title'        => 'اختيار الفكرة وتطويرها',
                'content'      => '<p>كيف تجد فكرة فيلمك الوثائقي وكيف تطوّرها إلى مشروع قابل للتنفيذ.</p>',
                'video_url'    => 'https://vimeo.com/XXXXXXXXXX',
                'is_preview'   => 0,
                'menu_order'   => 1,
            ],
            [
                'title'        => 'البحث والتوثيق الميداني',
                'content'      => '<p>أدوات وأساليب البحث الميداني: المقابلات، الأرشيف، الأماكن.</p>',
                'video_url'    => '',
                'is_preview'   => 0,
                'menu_order'   => 2,
            ],
        ];

        foreach ( $lessons as $lesson ) {
            $lesson_id = wp_insert_post( [
                'post_type'    => 'lesson',
                'post_title'   => $lesson['title'],
                'post_content' => $lesson['content'],
                'post_status'  => 'publish',
                'post_parent'  => $topic_id ?: $course_id,
                'post_author'  => 1,
                'menu_order'   => $lesson['menu_order'],
            ] );

            if ( $lesson_id && ! is_wp_error( $lesson_id ) ) {
                if ( $lesson['video_url'] ) {
                    update_post_meta( $lesson_id, '_tutor_video', [
                        'source'       => strpos( $lesson['video_url'], 'youtube' ) !== false ? 'youtube' : 'vimeo',
                        'source_video_id' => $lesson['video_url'],
                    ] );
                }
                // Mark free preview
                update_post_meta( $lesson_id, '_is_preview', $lesson['is_preview'] );
                update_post_meta( $lesson_id, 'is_preview', $lesson['is_preview'] );
            }
        }

        update_option( 'molkfilm_sample_seeded', 1 );
        update_option( 'molkfilm_sample_course_id', $course_id );
        update_option( 'molkfilm_sample_product_id', $product_id );
    }

    // ── Plugin option defaults ────────────────────────────────────────────────

    private static function set_plugin_defaults() {
        $defaults = [
            'molkfilm_site_name'       => 'ملك فيلم',
            'molkfilm_tagline'         => 'أكاديمية صناعة الأفلام',
            'molkfilm_currency'        => 'EGP',
            'molkfilm_language'        => 'ar',
            'molkfilm_contact_email'   => 'info@molkfilm.com',
            'molkfilm_contact_phone'   => '+201000000000',
            'molkfilm_facebook'        => 'https://facebook.com/molkfilm',
            'molkfilm_instagram'       => 'https://instagram.com/molkfilm',
            'molkfilm_youtube'         => 'https://youtube.com/@molkfilm',
            'molkfilm_hero_title'      => 'اكتشف فن صناعة الأفلام',
            'molkfilm_hero_subtitle'   => 'دورات احترافية في صناعة الأفلام مع خبراء الصناعة',
            'molkfilm_ga_id'           => '', // TODO: paste GA4 measurement ID
            'molkfilm_seo_title'       => 'ملك فيلم — أكاديمية صناعة الأفلام',
            'molkfilm_seo_description' => 'تعلّم صناعة الأفلام والإخراج والمونتاج وكتابة السيناريو مع خبراء ملك فيلم',
            'molkfilm_sitemap_enabled' => '1',
            'molkfilm_paymob_api_key'  => '', // TODO: paste Paymob API key
            'molkfilm_paymob_integration_id' => '', // TODO: paste Paymob integration ID
            'molkfilm_paymob_iframe_id'      => '', // TODO: paste Paymob iframe ID
            'molkfilm_paytabs_profile_id'    => '', // TODO: paste PayTabs profile ID
            'molkfilm_paytabs_server_key'    => '', // TODO: paste PayTabs server key
            'molkfilm_paypal_client_id'      => '', // TODO: paste PayPal client ID
            'molkfilm_paypal_secret'         => '', // TODO: paste PayPal secret
            'molkfilm_paypal_mode'           => 'sandbox', // Change to 'live' when ready
        ];

        foreach ( $defaults as $key => $value ) {
            // add_option only writes if the option doesn't already exist
            add_option( $key, $value );
        }
    }
}
