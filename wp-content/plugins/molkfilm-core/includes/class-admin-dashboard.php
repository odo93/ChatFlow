<?php
/**
 * MolkFilm_Admin_Dashboard
 *
 * Registers a top-level "ملك فيلم" wp-admin menu with sub-pages:
 *  overview | courses | orders | students | categories | seo | settings
 *
 * Each page queries live data and renders a Shopify-style branded panel.
 * The Settings page stores everything in wp_options (no code edits needed).
 */

defined( 'ABSPATH' ) || exit;

class MolkFilm_Admin_Dashboard {

    const MENU_SLUG    = 'molkfilm';
    const NONCE_ACTION = 'molkfilm_settings_save';
    const NONCE_FIELD  = 'molkfilm_settings_nonce';

    public static function init() {
        add_action( 'admin_menu',            [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
        add_action( 'admin_post_molkfilm_save_settings', [ __CLASS__, 'save_settings' ] );
    }

    // ── Menu registration ─────────────────────────────────────────────────────

    public static function register_menus() {
        add_menu_page(
            esc_html__( 'ملك فيلم', 'molkfilm' ),
            esc_html__( 'ملك فيلم', 'molkfilm' ),
            'manage_options',
            self::MENU_SLUG,
            [ __CLASS__, 'page_overview' ],
            'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#FFD844" d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18l7.5 3.75v8.14L12 19.82l-7.5-3.75V7.93L12 4.18z"/></svg>' ),
            30
        );

        $sub_pages = [
            [ 'slug' => self::MENU_SLUG,                    'title' => 'نظرة عامة',      'cb' => 'page_overview' ],
            [ 'slug' => self::MENU_SLUG . '-courses',       'title' => 'الدورات',         'cb' => 'page_courses' ],
            [ 'slug' => self::MENU_SLUG . '-orders',        'title' => 'الطلبات',         'cb' => 'page_orders' ],
            [ 'slug' => self::MENU_SLUG . '-students',      'title' => 'الطلاب',          'cb' => 'page_students' ],
            [ 'slug' => self::MENU_SLUG . '-categories',    'title' => 'التصنيفات',       'cb' => 'page_categories' ],
            [ 'slug' => self::MENU_SLUG . '-seo',           'title' => 'مدير SEO',        'cb' => 'page_seo' ],
            [ 'slug' => self::MENU_SLUG . '-settings',      'title' => 'الإعدادات',       'cb' => 'page_settings' ],
        ];

        foreach ( $sub_pages as $p ) {
            add_submenu_page(
                self::MENU_SLUG,
                esc_html( $p['title'] ) . ' — ' . esc_html__( 'ملك فيلم', 'molkfilm' ),
                esc_html( $p['title'] ),
                'manage_options',
                $p['slug'],
                [ __CLASS__, $p['cb'] ]
            );
        }
    }

    // ── Asset enqueue ─────────────────────────────────────────────────────────

    public static function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, self::MENU_SLUG ) === false ) {
            return;
        }
        wp_enqueue_style(
            'molkfilm-admin',
            MOLKFILM_PLUGIN_URI . 'assets/admin.css',
            [],
            MOLKFILM_VERSION
        );
        // Arabic admin font
        wp_enqueue_style(
            'molkfilm-admin-fonts',
            'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800;900&display=swap',
            [],
            null
        );
    }

    // ── Shared sidebar wrapper ────────────────────────────────────────────────

    private static function wrap_open( $active_slug ) {
        $current = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : self::MENU_SLUG;
        $pages   = [
            self::MENU_SLUG                    => [ 'icon' => '&#9632;',  'label' => 'نظرة عامة' ],
            self::MENU_SLUG . '-courses'       => [ 'icon' => '&#127916;','label' => 'الدورات' ],
            self::MENU_SLUG . '-orders'        => [ 'icon' => '&#128179;','label' => 'الطلبات' ],
            self::MENU_SLUG . '-students'      => [ 'icon' => '&#128100;','label' => 'الطلاب' ],
            self::MENU_SLUG . '-categories'    => [ 'icon' => '&#128193;','label' => 'التصنيفات' ],
            self::MENU_SLUG . '-seo'           => [ 'icon' => '&#128269;','label' => 'مدير SEO' ],
            self::MENU_SLUG . '-settings'      => [ 'icon' => '&#9881;',  'label' => 'الإعدادات' ],
        ];
        echo '<div class="mf-admin-wrap">';
        echo '<div class="mf-admin-sidebar">';
        echo '<div class="mf-admin-sidebar__logo"><h2>' . esc_html__( 'ملك فيلم', 'molkfilm' ) . '</h2><p>' . esc_html__( 'لوحة التحكم', 'molkfilm' ) . '</p></div>';
        echo '<ul class="mf-admin-nav">';
        foreach ( $pages as $slug => $p ) {
            $url    = admin_url( 'admin.php?page=' . $slug );
            $active = ( $current === $slug ) ? ' is-active' : '';
            printf(
                '<li><a href="%s" class="%s"><span class="mf-admin-nav__icon">%s</span> %s</a></li>',
                esc_url( $url ),
                'mf-admin-nav__link' . esc_attr( $active ),
                $p['icon'], // already escaped HTML entities
                esc_html( $p['label'] )
            );
        }
        echo '</ul></div>';
        echo '<div class="mf-admin-content">';
    }

    private static function wrap_close() {
        echo '</div></div>'; // .mf-admin-content + .mf-admin-wrap
    }

    // ── Page: Overview ────────────────────────────────────────────────────────

    public static function page_overview() {
        self::wrap_open( self::MENU_SLUG );

        // Fetch stats
        $revenue     = self::get_total_revenue();
        $enrollments = self::get_enrollment_count();
        $courses     = wp_count_posts( 'courses' )->publish ?? 0;
        $students    = self::get_student_count();

        ?>
        <div class="mf-admin-header">
            <h1><?php esc_html_e( 'نظرة عامة', 'molkfilm' ); ?></h1>
            <span style="font-size:.85rem;color:#8FB3A6;"><?php echo esc_html( date_i18n( 'l، j F Y' ) ); ?></span>
        </div>

        <div class="mf-stat-cards">
            <div class="mf-stat-card mf-stat-card--yellow">
                <div class="mf-stat-card__label"><?php esc_html_e( 'إجمالي الإيرادات', 'molkfilm' ); ?></div>
                <div class="mf-stat-card__value"><?php echo esc_html( number_format( $revenue, 0 ) ); ?></div>
                <div class="mf-stat-card__sub"><?php echo esc_html( get_option( 'molkfilm_currency', 'EGP' ) ); ?></div>
            </div>
            <div class="mf-stat-card">
                <div class="mf-stat-card__label"><?php esc_html_e( 'إجمالي التسجيلات', 'molkfilm' ); ?></div>
                <div class="mf-stat-card__value"><?php echo esc_html( $enrollments ); ?></div>
                <div class="mf-stat-card__sub"><?php esc_html_e( 'طالب مسجّل', 'molkfilm' ); ?></div>
            </div>
            <div class="mf-stat-card">
                <div class="mf-stat-card__label"><?php esc_html_e( 'الدورات النشطة', 'molkfilm' ); ?></div>
                <div class="mf-stat-card__value"><?php echo esc_html( $courses ); ?></div>
                <div class="mf-stat-card__sub"><?php esc_html_e( 'دورة منشورة', 'molkfilm' ); ?></div>
            </div>
            <div class="mf-stat-card">
                <div class="mf-stat-card__label"><?php esc_html_e( 'الطلاب', 'molkfilm' ); ?></div>
                <div class="mf-stat-card__value"><?php echo esc_html( $students ); ?></div>
                <div class="mf-stat-card__sub"><?php esc_html_e( 'مستخدم نشط', 'molkfilm' ); ?></div>
            </div>
        </div>

        <!-- Recent orders -->
        <div class="mf-admin-card">
            <div class="mf-admin-card__head">&#128179; <?php esc_html_e( 'أحدث الطلبات', 'molkfilm' ); ?></div>
            <div class="mf-admin-card__body">
                <?php self::render_recent_orders( 8 ); ?>
            </div>
        </div>
        <?php

        self::wrap_close();
    }

    // ── Page: Courses ─────────────────────────────────────────────────────────

    public static function page_courses() {
        self::wrap_open( self::MENU_SLUG . '-courses' );
        ?>
        <div class="mf-admin-header">
            <h1><?php esc_html_e( 'الدورات', 'molkfilm' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=courses' ) ); ?>"
               class="mf-btn mf-btn--primary">
                + <?php esc_html_e( 'دورة جديدة', 'molkfilm' ); ?>
            </a>
        </div>

        <div class="mf-admin-card">
            <div class="mf-admin-card__head">&#127916; <?php esc_html_e( 'جميع الدورات', 'molkfilm' ); ?></div>
            <div class="mf-admin-card__body">
                <?php
                $courses = get_posts( [
                    'post_type'      => 'courses',
                    'posts_per_page' => 50,
                    'post_status'    => [ 'publish', 'draft', 'pending' ],
                ] );
                if ( $courses ) :
                ?>
                <table class="mf-admin-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'الدورة', 'molkfilm' ); ?></th>
                            <th><?php esc_html_e( 'المدرب', 'molkfilm' ); ?></th>
                            <th><?php esc_html_e( 'المقاعد', 'molkfilm' ); ?></th>
                            <th><?php esc_html_e( 'الحالة', 'molkfilm' ); ?></th>
                            <th><?php esc_html_e( 'إجراءات', 'molkfilm' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $courses as $c ) :
                        $taken = (int) get_post_meta( $c->ID, '_mf_seats_taken', true );
                        $total = (int) get_post_meta( $c->ID, '_mf_total_seats', true );
                        $instr = get_post_meta( $c->ID, '_mf_instructor_name', true );
                        $status_map = [
                            'publish' => [ 'label' => 'منشور',  'class' => 'paid' ],
                            'draft'   => [ 'label' => 'مسودة',  'class' => 'pending' ],
                            'pending' => [ 'label' => 'قيد المراجعة', 'class' => 'pending' ],
                        ];
                        $st = $status_map[ $c->post_status ] ?? [ 'label' => $c->post_status, 'class' => 'pending' ];
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $c->post_title ); ?></strong></td>
                        <td><?php echo esc_html( $instr ?: '—' ); ?></td>
                        <td><?php echo esc_html( $taken . ' / ' . ( $total ?: '∞' ) ); ?></td>
                        <td><span class="mf-status mf-status--<?php echo esc_attr( $st['class'] ); ?>"><?php echo esc_html( $st['label'] ); ?></span></td>
                        <td>
                            <a href="<?php echo esc_url( get_edit_post_link( $c->ID ) ); ?>" class="mf-btn mf-btn--outline" style="padding:4px 12px;font-size:.8rem;">
                                <?php esc_html_e( 'تعديل', 'molkfilm' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <p><?php esc_html_e( 'لا توجد دورات بعد.', 'molkfilm' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        self::wrap_close();
    }

    // ── Page: Orders ──────────────────────────────────────────────────────────

    public static function page_orders() {
        self::wrap_open( self::MENU_SLUG . '-orders' );
        ?>
        <div class="mf-admin-header">
            <h1><?php esc_html_e( 'الطلبات والتسجيلات', 'molkfilm' ); ?></h1>
        </div>
        <div class="mf-admin-card">
            <div class="mf-admin-card__head">&#128179; <?php esc_html_e( 'طلبات الدورات', 'molkfilm' ); ?></div>
            <div class="mf-admin-card__body">
                <?php self::render_orders_table( 50 ); ?>
            </div>
        </div>
        <?php
        self::wrap_close();
    }

    // ── Page: Students ────────────────────────────────────────────────────────

    public static function page_students() {
        self::wrap_open( self::MENU_SLUG . '-students' );
        ?>
        <div class="mf-admin-header">
            <h1><?php esc_html_e( 'الطلاب', 'molkfilm' ); ?></h1>
        </div>
        <div class="mf-admin-card">
            <div class="mf-admin-card__head">&#128100; <?php esc_html_e( 'قائمة الطلاب', 'molkfilm' ); ?></div>
            <div class="mf-admin-card__body">
                <?php
                $students = get_users( [
                    'role__in'  => [ 'subscriber', 'student', 'tutor_student' ],
                    'number'    => 100,
                    'orderby'   => 'registered',
                    'order'     => 'DESC',
                ] );
                if ( $students ) :
                ?>
                <table class="mf-admin-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'الاسم', 'molkfilm' ); ?></th>
                            <th><?php esc_html_e( 'البريد الإلكتروني', 'molkfilm' ); ?></th>
                            <th><?php esc_html_e( 'تاريخ التسجيل', 'molkfilm' ); ?></th>
                            <th><?php esc_html_e( 'الدورات', 'molkfilm' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $students as $user ) :
                        // Count enrolled courses (Tutor meta)
                        $enrolled_count = 0;
                        if ( function_exists( 'tutor_utils' ) ) {
                            $enrolled = tutor_utils()->get_enrolled_courses_by_user( $user->ID );
                            $enrolled_count = $enrolled ? $enrolled->post_count : 0;
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $user->display_name ); ?></strong></td>
                        <td><?php echo esc_html( $user->user_email ); ?></td>
                        <td><?php echo esc_html( date_i18n( 'j M Y', strtotime( $user->user_registered ) ) ); ?></td>
                        <td><?php echo esc_html( $enrolled_count ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <p><?php esc_html_e( 'لا يوجد طلاب بعد.', 'molkfilm' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        self::wrap_close();
    }

    // ── Page: Categories ──────────────────────────────────────────────────────

    public static function page_categories() {
        self::wrap_open( self::MENU_SLUG . '-categories' );
        ?>
        <div class="mf-admin-header">
            <h1><?php esc_html_e( 'تصنيفات الدورات', 'molkfilm' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=course-category&post_type=courses' ) ); ?>"
               class="mf-btn mf-btn--primary">
                <?php esc_html_e( 'إدارة التصنيفات', 'molkfilm' ); ?>
            </a>
        </div>
        <div class="mf-admin-card">
            <div class="mf-admin-card__head">&#128193; <?php esc_html_e( 'التصنيفات الحالية', 'molkfilm' ); ?></div>
            <div class="mf-admin-card__body">
                <?php
                $terms = get_terms( [ 'taxonomy' => 'course-category', 'hide_empty' => false ] );
                if ( ! is_wp_error( $terms ) && $terms ) :
                ?>
                <table class="mf-admin-table">
                    <thead><tr>
                        <th><?php esc_html_e( 'التصنيف', 'molkfilm' ); ?></th>
                        <th><?php esc_html_e( 'الرابط', 'molkfilm' ); ?></th>
                        <th><?php esc_html_e( 'عدد الدورات', 'molkfilm' ); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $terms as $term ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $term->name ); ?></strong></td>
                        <td><code><?php echo esc_html( $term->slug ); ?></code></td>
                        <td><?php echo esc_html( $term->count ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                    <p><?php esc_html_e( 'لا توجد تصنيفات.', 'molkfilm' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        self::wrap_close();
    }

    // ── Page: SEO ─────────────────────────────────────────────────────────────

    public static function page_seo() {
        self::wrap_open( self::MENU_SLUG . '-seo' );
        self::render_settings_form( [
            [ 'section' => 'الإعدادات العامة للـ SEO' ],
            [ 'key' => 'molkfilm_seo_title',       'label' => 'عنوان الموقع الافتراضي',     'type' => 'text' ],
            [ 'key' => 'molkfilm_seo_description', 'label' => 'الوصف الافتراضي',             'type' => 'textarea', 'note' => 'يُستخدم عند عدم توفر وصف خاص بالصفحة' ],
            [ 'key' => 'molkfilm_ga_id',           'label' => 'Google Analytics ID',         'type' => 'text', 'note' => 'مثال: G-XXXXXXXXXX', 'placeholder' => 'G-XXXXXXXXXX' ],
            [ 'key' => 'molkfilm_sitemap_enabled', 'label' => 'تفعيل خريطة الموقع (Sitemap)', 'type' => 'select', 'options' => [ '1' => 'نعم', '0' => 'لا' ] ],
        ], 'مدير SEO' );

        // Per-course SEO editor ────────────────────────────────────────────────
        // Handle inline save of per-course SEO fields
        if ( isset( $_POST['mf_course_seo_save'] ) &&
             check_admin_referer( 'molkfilm_course_seo', 'mf_course_seo_nonce' ) &&
             current_user_can( 'manage_options' ) ) {
            $cid  = absint( $_POST['mf_course_id'] ?? 0 );
            $seo_title = sanitize_text_field( wp_unslash( $_POST['mf_seo_title'] ?? '' ) );
            $seo_desc  = sanitize_textarea_field( wp_unslash( $_POST['mf_seo_description'] ?? '' ) );
            if ( $cid ) {
                update_post_meta( $cid, '_mf_seo_title',       $seo_title );
                update_post_meta( $cid, '_mf_seo_description', $seo_desc );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'تم حفظ SEO الدورة.', 'molkfilm' ) . '</p></div>';
            }
        }

        $courses = get_posts( [ 'post_type' => 'courses', 'posts_per_page' => 100, 'post_status' => [ 'publish', 'draft' ] ] );
        if ( $courses ) :
        ?>
        <div class="mf-admin-card" style="margin-top:28px;">
            <div class="mf-admin-card__head">&#128269; <?php esc_html_e( 'SEO لكل دورة', 'molkfilm' ); ?></div>
            <div class="mf-admin-card__body">
                <p style="font-size:.85rem;color:var(--adm-muted);margin-top:0;"><?php esc_html_e( 'الحقول الفارغة تُورّث الإعدادات العامة أعلاه.', 'molkfilm' ); ?></p>
                <?php foreach ( $courses as $c ) :
                    $seo_title = get_post_meta( $c->ID, '_mf_seo_title', true );
                    $seo_desc  = get_post_meta( $c->ID, '_mf_seo_description', true );
                ?>
                <details style="border-bottom:1px solid var(--adm-border);padding:12px 0;">
                    <summary style="cursor:pointer;font-weight:700;color:var(--adm-dark);">
                        <?php echo esc_html( $c->post_title ); ?>
                        <?php if ( $seo_title || $seo_desc ) echo ' <small style="color:var(--adm-green);">✓ مُخصَّص</small>'; ?>
                    </summary>
                    <form method="post" style="padding:16px 0 4px;direction:rtl;">
                        <?php wp_nonce_field( 'molkfilm_course_seo', 'mf_course_seo_nonce' ); ?>
                        <input type="hidden" name="mf_course_id" value="<?php echo esc_attr( $c->ID ); ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                            <div>
                                <label style="display:block;font-size:.82rem;font-weight:700;margin-bottom:4px;"><?php esc_html_e( 'عنوان SEO', 'molkfilm' ); ?></label>
                                <input type="text" name="mf_seo_title" value="<?php echo esc_attr( $seo_title ); ?>"
                                       style="width:100%;padding:7px 10px;border:1px solid var(--adm-border);border-radius:4px;direction:rtl;"
                                       placeholder="<?php esc_attr_e( 'اتركه فارغاً للاستخدام الافتراضي', 'molkfilm' ); ?>">
                            </div>
                            <div>
                                <label style="display:block;font-size:.82rem;font-weight:700;margin-bottom:4px;"><?php esc_html_e( 'وصف SEO', 'molkfilm' ); ?></label>
                                <input type="text" name="mf_seo_description" value="<?php echo esc_attr( $seo_desc ); ?>"
                                       style="width:100%;padding:7px 10px;border:1px solid var(--adm-border);border-radius:4px;direction:rtl;"
                                       placeholder="<?php esc_attr_e( 'اتركه فارغاً للاستخدام الافتراضي', 'molkfilm' ); ?>">
                            </div>
                        </div>
                        <button type="submit" name="mf_course_seo_save" value="1" class="mf-btn mf-btn--primary" style="padding:6px 16px;font-size:.82rem;">
                            <?php esc_html_e( 'حفظ', 'molkfilm' ); ?>
                        </button>
                    </form>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif;

        self::wrap_close();
    }

    // ── Page: Settings ────────────────────────────────────────────────────────

    public static function page_settings() {
        self::wrap_open( self::MENU_SLUG . '-settings' );
        self::render_settings_form( [
            [ 'section' => 'هوية الموقع' ],
            [ 'key' => 'molkfilm_site_name',     'label' => 'اسم الموقع',          'type' => 'text' ],
            [ 'key' => 'molkfilm_tagline',        'label' => 'الشعار الفرعي',        'type' => 'text' ],
            [ 'key' => 'molkfilm_hero_title',     'label' => 'عنوان الهيرو',         'type' => 'text' ],
            [ 'key' => 'molkfilm_hero_subtitle',  'label' => 'وصف الهيرو',           'type' => 'textarea' ],
            [ 'section' => 'التواصل والشبكات الاجتماعية' ],
            [ 'key' => 'molkfilm_contact_email',  'label' => 'البريد الإلكتروني',   'type' => 'email' ],
            [ 'key' => 'molkfilm_contact_phone',  'label' => 'رقم الهاتف',           'type' => 'text' ],
            [ 'key' => 'molkfilm_facebook',       'label' => 'فيسبوك',               'type' => 'url' ],
            [ 'key' => 'molkfilm_instagram',      'label' => 'إنستغرام',             'type' => 'url' ],
            [ 'key' => 'molkfilm_youtube',        'label' => 'يوتيوب',               'type' => 'url' ],
            [ 'section' => 'العملة والإعدادات المالية' ],
            [ 'key' => 'molkfilm_currency',       'label' => 'العملة',               'type' => 'select', 'options' => [ 'EGP' => 'جنيه مصري (EGP)', 'SAR' => 'ريال سعودي (SAR)', 'AED' => 'درهم إماراتي (AED)', 'USD' => 'دولار أمريكي (USD)' ] ],
            [ 'section' => 'بوابات الدفع — Paymob' ],
            [ 'key' => 'molkfilm_paymob_api_key',         'label' => 'Paymob API Key',         'type' => 'password', 'note' => 'TODO: الصق مفتاح Paymob API هنا' ],
            [ 'key' => 'molkfilm_paymob_integration_id',  'label' => 'Paymob Integration ID',  'type' => 'text', 'note' => 'TODO: Integration ID من Paymob Dashboard' ],
            [ 'key' => 'molkfilm_paymob_iframe_id',       'label' => 'Paymob iFrame ID',       'type' => 'text', 'note' => 'TODO: iFrame ID من Paymob Dashboard' ],
            [ 'section' => 'بوابات الدفع — PayTabs' ],
            [ 'key' => 'molkfilm_paytabs_profile_id',  'label' => 'PayTabs Profile ID', 'type' => 'text',     'note' => 'TODO: الصق Profile ID هنا' ],
            [ 'key' => 'molkfilm_paytabs_server_key',  'label' => 'PayTabs Server Key', 'type' => 'password', 'note' => 'TODO: الصق Server Key هنا' ],
            [ 'section' => 'بوابات الدفع — PayPal' ],
            [ 'key' => 'molkfilm_paypal_client_id', 'label' => 'PayPal Client ID', 'type' => 'text',     'note' => 'TODO: الصق Client ID هنا' ],
            [ 'key' => 'molkfilm_paypal_secret',    'label' => 'PayPal Secret',    'type' => 'password', 'note' => 'TODO: الصق Secret هنا' ],
            [ 'key' => 'molkfilm_paypal_mode',      'label' => 'وضع PayPal',       'type' => 'select',   'options' => [ 'sandbox' => 'تجريبي (Sandbox)', 'live' => 'إنتاج (Live)' ] ],
        ], 'الإعدادات' );
        self::wrap_close();
    }

    // ── Shared: render settings form ──────────────────────────────────────────

    private static function render_settings_form( array $fields, string $title ) {
        // Handle saved notice
        if ( isset( $_GET['updated'] ) && $_GET['updated'] === '1' ) { // phpcs:ignore
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'تم حفظ الإعدادات بنجاح.', 'molkfilm' ) . '</p></div>';
        }
        ?>
        <div class="mf-admin-header">
            <h1><?php echo esc_html( $title ); ?></h1>
        </div>
        <div class="mf-admin-card">
            <div class="mf-admin-card__head">&#9881; <?php echo esc_html( $title ); ?></div>
            <div class="mf-admin-card__body">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mf-settings-form">
                    <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
                    <input type="hidden" name="action" value="molkfilm_save_settings">
                    <input type="hidden" name="_redirect_page" value="<?php echo esc_attr( sanitize_key( $_GET['page'] ?? self::MENU_SLUG ) ); ?>">

                    <div class="mf-meta-grid" style="display:grid;grid-template-columns:1fr;">
                    <?php foreach ( $fields as $field ) :
                        if ( isset( $field['section'] ) ) : ?>
                            <div class="mf-settings-section-head"><?php echo esc_html( $field['section'] ); ?></div>
                        <?php continue; endif;

                        $value = get_option( $field['key'], '' );
                        $type  = $field['type'];
                    ?>
                    <div class="mf-field">
                        <label for="<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
                        <div>
                        <?php if ( $type === 'textarea' ) : ?>
                            <textarea id="<?php echo esc_attr( $field['key'] ); ?>"
                                      name="<?php echo esc_attr( $field['key'] ); ?>"
                                      rows="3"><?php echo esc_textarea( $value ); ?></textarea>
                        <?php elseif ( $type === 'select' ) : ?>
                            <select id="<?php echo esc_attr( $field['key'] ); ?>"
                                    name="<?php echo esc_attr( $field['key'] ); ?>">
                                <?php foreach ( $field['options'] as $opt_val => $opt_label ) : ?>
                                    <option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $value, $opt_val ); ?>>
                                        <?php echo esc_html( $opt_label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input type="<?php echo esc_attr( $type ); ?>"
                                   id="<?php echo esc_attr( $field['key'] ); ?>"
                                   name="<?php echo esc_attr( $field['key'] ); ?>"
                                   value="<?php echo esc_attr( $value ); ?>"
                                   placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>">
                        <?php endif; ?>
                        <?php if ( ! empty( $field['note'] ) ) : ?>
                            <p class="mf-field-note"><?php echo esc_html( $field['note'] ); ?></p>
                        <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                    <div style="margin-top:24px;text-align:right;">
                        <button type="submit" class="mf-btn mf-btn--primary">
                            <?php esc_html_e( 'حفظ الإعدادات', 'molkfilm' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    // ── Save handler ──────────────────────────────────────────────────────────

    public static function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'ليس لديك صلاحية.', 'molkfilm' ) );
        }

        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
            wp_die( esc_html__( 'فشل التحقق الأمني.', 'molkfilm' ) );
        }

        $allowed_keys = [
            'molkfilm_site_name', 'molkfilm_tagline', 'molkfilm_hero_title', 'molkfilm_hero_subtitle',
            'molkfilm_contact_email', 'molkfilm_contact_phone',
            'molkfilm_facebook', 'molkfilm_instagram', 'molkfilm_youtube',
            'molkfilm_currency', 'molkfilm_language',
            'molkfilm_ga_id', 'molkfilm_seo_title', 'molkfilm_seo_description', 'molkfilm_sitemap_enabled',
            'molkfilm_paymob_api_key', 'molkfilm_paymob_integration_id', 'molkfilm_paymob_iframe_id',
            'molkfilm_paytabs_profile_id', 'molkfilm_paytabs_server_key',
            'molkfilm_paypal_client_id', 'molkfilm_paypal_secret', 'molkfilm_paypal_mode',
        ];

        foreach ( $allowed_keys as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                $raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore
                // Sanitize by field type
                if ( strpos( $key, 'email' ) !== false ) {
                    $value = sanitize_email( $raw );
                } elseif ( strpos( $key, 'url' ) !== false || strpos( $key, 'facebook' ) !== false ||
                           strpos( $key, 'instagram' ) !== false || strpos( $key, 'youtube' ) !== false ) {
                    $value = esc_url_raw( $raw );
                } elseif ( strpos( $key, 'description' ) !== false || strpos( $key, 'subtitle' ) !== false ) {
                    $value = sanitize_textarea_field( $raw );
                } else {
                    $value = sanitize_text_field( $raw );
                }
                update_option( $key, $value );
            }
        }

        $redirect_page = sanitize_key( $_POST['_redirect_page'] ?? self::MENU_SLUG );
        wp_safe_redirect( admin_url( 'admin.php?page=' . $redirect_page . '&updated=1' ) );
        exit;
    }

    // ── Data helpers ──────────────────────────────────────────────────────────

    private static function get_total_revenue() {
        global $wpdb;
        $result = $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta}
             WHERE meta_key = '_order_total'
             AND post_id IN (
                 SELECT ID FROM {$wpdb->posts}
                 WHERE post_type = 'shop_order' AND post_status IN ('wc-completed','wc-processing')
             )"
        );
        return floatval( $result );
    }

    private static function get_enrollment_count() {
        if ( function_exists( 'tutor_utils' ) ) {
            return (int) tutor_utils()->get_total_enrolment_count();
        }
        // Fallback: count completed/processing WC orders
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->posts}
             WHERE post_type='shop_order' AND post_status IN ('wc-completed','wc-processing')"
        );
    }

    private static function get_student_count() {
        $count = 0;
        foreach ( [ 'subscriber', 'student', 'tutor_student' ] as $role ) {
            $users  = get_users( [ 'role' => $role, 'fields' => 'ID', 'number' => 9999 ] );
            $count += count( $users );
        }
        return $count;
    }

    private static function render_recent_orders( $limit = 5 ) {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            echo '<p>' . esc_html__( 'WooCommerce غير مفعّل.', 'molkfilm' ) . '</p>';
            return;
        }
        $orders = wc_get_orders( [
            'limit'  => $limit,
            'status' => [ 'completed', 'processing', 'pending' ],
            'orderby'=> 'date',
            'order'  => 'DESC',
        ] );
        if ( ! $orders ) {
            echo '<p>' . esc_html__( 'لا توجد طلبات بعد.', 'molkfilm' ) . '</p>';
            return;
        }
        foreach ( $orders as $order ) {
            $status_map = [
                'completed'  => [ 'label' => 'مكتمل',   'class' => 'paid' ],
                'processing' => [ 'label' => 'قيد المعالجة', 'class' => 'pending' ],
                'pending'    => [ 'label' => 'معلّق',    'class' => 'pending' ],
                'failed'     => [ 'label' => 'فشل',      'class' => 'failed' ],
                'cancelled'  => [ 'label' => 'ملغى',     'class' => 'cancelled' ],
            ];
            $status = str_replace( 'wc-', '', $order->get_status() );
            $st     = $status_map[ $status ] ?? [ 'label' => $status, 'class' => 'pending' ];
            ?>
            <div class="mf-order-row">
                <span class="mf-order-row__id">#<?php echo esc_html( $order->get_id() ); ?></span>
                <span class="mf-order-row__name"><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></span>
                <span><span class="mf-status mf-status--<?php echo esc_attr( $st['class'] ); ?>"><?php echo esc_html( $st['label'] ); ?></span></span>
                <span class="mf-order-row__amount"><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></span>
                <a href="<?php echo esc_url( get_edit_post_link( $order->get_id() ) ); ?>" style="font-size:.8rem;color:var(--adm-green);"><?php esc_html_e( 'عرض', 'molkfilm' ); ?></a>
            </div>
            <?php
        }
    }

    private static function render_orders_table( $limit = 20 ) {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            echo '<p>' . esc_html__( 'WooCommerce غير مفعّل.', 'molkfilm' ) . '</p>';
            return;
        }
        $orders = wc_get_orders( [
            'limit'  => $limit,
            'status' => [ 'completed', 'processing', 'pending', 'failed', 'cancelled' ],
            'orderby'=> 'date',
            'order'  => 'DESC',
        ] );
        if ( ! $orders ) {
            echo '<p>' . esc_html__( 'لا توجد طلبات بعد.', 'molkfilm' ) . '</p>';
            return;
        }
        ?>
        <table class="mf-admin-table">
            <thead><tr>
                <th><?php esc_html_e( '#', 'molkfilm' ); ?></th>
                <th><?php esc_html_e( 'الطالب', 'molkfilm' ); ?></th>
                <th><?php esc_html_e( 'المنتج / الدورة', 'molkfilm' ); ?></th>
                <th><?php esc_html_e( 'بوابة الدفع', 'molkfilm' ); ?></th>
                <th><?php esc_html_e( 'المبلغ', 'molkfilm' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'molkfilm' ); ?></th>
                <th><?php esc_html_e( 'التاريخ', 'molkfilm' ); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $orders as $order ) :
                $status_map = [
                    'completed'  => [ 'label' => 'مكتمل',   'class' => 'paid' ],
                    'processing' => [ 'label' => 'قيد المعالجة', 'class' => 'pending' ],
                    'pending'    => [ 'label' => 'معلّق',    'class' => 'pending' ],
                    'failed'     => [ 'label' => 'فشل',      'class' => 'failed' ],
                    'cancelled'  => [ 'label' => 'ملغى',     'class' => 'cancelled' ],
                ];
                $status = str_replace( 'wc-', '', $order->get_status() );
                $st     = $status_map[ $status ] ?? [ 'label' => $status, 'class' => 'pending' ];

                // First item product name
                $items      = $order->get_items();
                $first_item = reset( $items );
                $item_name  = $first_item ? $first_item->get_name() : '—';

                // Transaction ID
                $txn = $order->get_transaction_id();
            ?>
            <tr>
                <td><a href="<?php echo esc_url( get_edit_post_link( $order->get_id() ) ); ?>">#<?php echo esc_html( $order->get_id() ); ?></a></td>
                <td><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?><br><small><?php echo esc_html( $order->get_billing_email() ); ?></small></td>
                <td><?php echo esc_html( $item_name ); ?></td>
                <td><?php echo esc_html( $order->get_payment_method_title() ); ?><?php if ( $txn ) echo '<br><small>' . esc_html( $txn ) . '</small>'; ?></td>
                <td><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></td>
                <td><span class="mf-status mf-status--<?php echo esc_attr( $st['class'] ); ?>"><?php echo esc_html( $st['label'] ); ?></span></td>
                <td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'j M Y' ) : '—' ); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
