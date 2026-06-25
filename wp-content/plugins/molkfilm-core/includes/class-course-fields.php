<?php
/**
 * MolkFilm_Course_Fields
 *
 * Registers custom meta fields on the Tutor LMS 'courses' CPT:
 *  - mode, total_seats, seats_taken, session_count,
 *    session_duration_minutes, total_hours_text,
 *    start_date, schedule_text, location, online_link, instructor_name
 *
 * Adds a meta box to the WP course editor, saves with nonce,
 * exposes helper functions for templates, and blocks enrollment
 * when seats are full via WooCommerce add-to-cart validation.
 */

defined( 'ABSPATH' ) || exit;

class MolkFilm_Course_Fields {

    const META_BOX_ID = 'molkfilm_course_details';
    const NONCE_ACTION = 'molkfilm_save_course_fields';
    const NONCE_FIELD  = 'molkfilm_course_fields_nonce';

    // All custom meta keys (course details + per-course SEO)
    const FIELDS = [
        '_mf_mode'                     => [ 'type' => 'select',  'label' => 'طريقة التدريس' ],
        '_mf_total_seats'              => [ 'type' => 'number',  'label' => 'إجمالي المقاعد' ],
        '_mf_seats_taken'              => [ 'type' => 'number',  'label' => 'المقاعد المحجوزة' ],
        '_mf_session_count'            => [ 'type' => 'number',  'label' => 'عدد الجلسات' ],
        '_mf_session_duration_minutes' => [ 'type' => 'number',  'label' => 'مدة الجلسة (دقيقة)' ],
        '_mf_total_hours_text'         => [ 'type' => 'text',    'label' => 'إجمالي الساعات (نص)' ],
        '_mf_start_date'               => [ 'type' => 'date',    'label' => 'تاريخ البدء' ],
        '_mf_schedule_text'            => [ 'type' => 'text',    'label' => 'جدول المواعيد' ],
        '_mf_location'                 => [ 'type' => 'text',    'label' => 'الموقع (وجاهي)' ],
        '_mf_online_link'              => [ 'type' => 'url',     'label' => 'رابط الجلسة الأونلاين' ],
        '_mf_instructor_name'          => [ 'type' => 'text',    'label' => 'اسم المدرب' ],
        // Per-course SEO overrides (used by class-seo.php)
        '_mf_seo_title'                => [ 'type' => 'text',    'label' => 'عنوان SEO (اختياري)' ],
        '_mf_seo_description'          => [ 'type' => 'text',    'label' => 'وصف SEO (اختياري)' ],
        '_mf_seo_locale'               => [ 'type' => 'text',    'label' => 'اللغة (og:locale)' ],
    ];

    public static function init() {
        add_action( 'add_meta_boxes',             [ __CLASS__, 'register_meta_box' ] );
        add_action( 'save_post_courses',           [ __CLASS__, 'save_meta' ], 10, 2 );
        add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'block_if_seats_full' ], 10, 3 );
        add_action( 'woocommerce_payment_complete',[ __CLASS__, 'increment_seats_on_payment' ] );
        add_action( 'tutor_after_enroll',          [ __CLASS__, 'increment_seats_on_free_enroll' ] );
    }

    // ── Meta box registration ─────────────────────────────────────────────────

    public static function register_meta_box() {
        add_meta_box(
            self::META_BOX_ID,
            esc_html__( 'تفاصيل الدورة — ملك فيلم', 'molkfilm' ),
            [ __CLASS__, 'render_meta_box' ],
            'courses',
            'normal',
            'high'
        );
    }

    // ── Meta box render ───────────────────────────────────────────────────────

    public static function render_meta_box( $post ) {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

        $mode     = get_post_meta( $post->ID, '_mf_mode', true ) ?: 'online';
        $fields   = self::FIELDS;
        $values   = [];
        foreach ( $fields as $key => $_ ) {
            $values[ $key ] = get_post_meta( $post->ID, $key, true );
        }
        ?>
        <style>
        .mf-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; direction: rtl; }
        .mf-meta-grid .full { grid-column: 1 / -1; }
        .mf-meta-grid label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 13px; }
        .mf-meta-grid input, .mf-meta-grid select { width: 100%; padding: 6px 8px; border-radius: 4px; border: 1px solid #ddd; }
        .mf-section-head { background: #155541; color: #FFD844; padding: 6px 10px; border-radius: 4px;
                           margin: 16px 0 8px; font-weight: 700; font-size: 13px; grid-column: 1 / -1; }
        </style>
        <div class="mf-meta-grid">

            <div class="mf-section-head"><?php esc_html_e( 'المعلومات الأساسية', 'molkfilm' ); ?></div>

            <div>
                <label for="mf_instructor_name"><?php esc_html_e( 'اسم المدرب', 'molkfilm' ); ?></label>
                <input type="text" id="mf_instructor_name" name="_mf_instructor_name"
                       value="<?php echo esc_attr( $values['_mf_instructor_name'] ); ?>" />
            </div>

            <div>
                <label for="mf_mode"><?php esc_html_e( 'طريقة التدريس', 'molkfilm' ); ?></label>
                <select id="mf_mode" name="_mf_mode">
                    <?php
                    $modes = [ 'online' => 'أونلاين', 'offline' => 'وجاهي', 'hybrid' => 'هجين' ];
                    foreach ( $modes as $val => $label ) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr( $val ),
                            selected( $mode, $val, false ),
                            esc_html( $label )
                        );
                    }
                    ?>
                </select>
            </div>

            <div class="mf-section-head"><?php esc_html_e( 'الجلسات والمقاعد', 'molkfilm' ); ?></div>

            <div>
                <label for="mf_total_seats"><?php esc_html_e( 'إجمالي المقاعد', 'molkfilm' ); ?></label>
                <input type="number" id="mf_total_seats" name="_mf_total_seats" min="0"
                       value="<?php echo esc_attr( $values['_mf_total_seats'] ); ?>" />
            </div>

            <div>
                <label for="mf_seats_taken"><?php esc_html_e( 'المقاعد المحجوزة (تلقائي)', 'molkfilm' ); ?></label>
                <input type="number" id="mf_seats_taken" name="_mf_seats_taken" min="0"
                       value="<?php echo esc_attr( $values['_mf_seats_taken'] ?: 0 ); ?>" />
            </div>

            <div>
                <label for="mf_session_count"><?php esc_html_e( 'عدد الجلسات', 'molkfilm' ); ?></label>
                <input type="number" id="mf_session_count" name="_mf_session_count" min="0"
                       value="<?php echo esc_attr( $values['_mf_session_count'] ); ?>" />
            </div>

            <div>
                <label for="mf_session_duration_minutes"><?php esc_html_e( 'مدة الجلسة (دقيقة)', 'molkfilm' ); ?></label>
                <input type="number" id="mf_session_duration_minutes" name="_mf_session_duration_minutes" min="0"
                       value="<?php echo esc_attr( $values['_mf_session_duration_minutes'] ); ?>" />
            </div>

            <div>
                <label for="mf_total_hours_text"><?php esc_html_e( 'إجمالي الساعات (نص)', 'molkfilm' ); ?></label>
                <input type="text" id="mf_total_hours_text" name="_mf_total_hours_text"
                       placeholder="مثال: 21 ساعة"
                       value="<?php echo esc_attr( $values['_mf_total_hours_text'] ); ?>" />
            </div>

            <div class="mf-section-head"><?php esc_html_e( 'الجدول والموقع', 'molkfilm' ); ?></div>

            <div>
                <label for="mf_start_date"><?php esc_html_e( 'تاريخ البدء', 'molkfilm' ); ?></label>
                <input type="date" id="mf_start_date" name="_mf_start_date"
                       value="<?php echo esc_attr( $values['_mf_start_date'] ); ?>" />
            </div>

            <div>
                <label for="mf_schedule_text"><?php esc_html_e( 'جدول المواعيد', 'molkfilm' ); ?></label>
                <input type="text" id="mf_schedule_text" name="_mf_schedule_text"
                       placeholder="كل سبت — 6 مساءً"
                       value="<?php echo esc_attr( $values['_mf_schedule_text'] ); ?>" />
            </div>

            <div>
                <label for="mf_location"><?php esc_html_e( 'الموقع (وجاهي)', 'molkfilm' ); ?></label>
                <input type="text" id="mf_location" name="_mf_location"
                       value="<?php echo esc_attr( $values['_mf_location'] ); ?>" />
            </div>

            <div>
                <label for="mf_online_link"><?php esc_html_e( 'رابط الجلسة الأونلاين', 'molkfilm' ); ?></label>
                <input type="url" id="mf_online_link" name="_mf_online_link"
                       value="<?php echo esc_attr( $values['_mf_online_link'] ); ?>" />
            </div>

            <div class="mf-section-head"><?php esc_html_e( 'تحسين محركات البحث (SEO)', 'molkfilm' ); ?></div>

            <div class="full">
                <label for="mf_seo_title"><?php esc_html_e( 'عنوان SEO (يُستبدل به العنوان الافتراضي)', 'molkfilm' ); ?></label>
                <input type="text" id="mf_seo_title" name="_mf_seo_title"
                       placeholder="<?php esc_attr_e( 'اتركه فارغاً لاستخدام عنوان الدورة', 'molkfilm' ); ?>"
                       value="<?php echo esc_attr( $values['_mf_seo_title'] ?? '' ); ?>" />
            </div>

            <div class="full">
                <label for="mf_seo_description"><?php esc_html_e( 'وصف SEO (meta description)', 'molkfilm' ); ?></label>
                <input type="text" id="mf_seo_description" name="_mf_seo_description"
                       placeholder="<?php esc_attr_e( 'اتركه فارغاً لاستخدام المقتطف', 'molkfilm' ); ?>"
                       value="<?php echo esc_attr( $values['_mf_seo_description'] ?? '' ); ?>" />
            </div>

            <div>
                <label for="mf_seo_locale"><?php esc_html_e( 'اللغة (og:locale)', 'molkfilm' ); ?></label>
                <input type="text" id="mf_seo_locale" name="_mf_seo_locale"
                       placeholder="ar_EG"
                       value="<?php echo esc_attr( $values['_mf_seo_locale'] ?? 'ar_EG' ); ?>" />
            </div>

        </div><!-- .mf-meta-grid -->
        <?php
    }

    // ── Save meta ─────────────────────────────────────────────────────────────

    public static function save_meta( $post_id, $post ) {
        // Nonce check
        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
            return;
        }
        // Capability check
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        // Skip autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $sanitizers = [
            'select' => 'sanitize_key',
            'number' => 'absint',
            'text'   => 'sanitize_text_field',
            'date'   => 'sanitize_text_field',
            'url'    => 'esc_url_raw',
        ];

        foreach ( self::FIELDS as $key => $config ) {
            if ( ! isset( $_POST[ $key ] ) ) {
                continue;
            }
            $raw       = wp_unslash( $_POST[ $key ] ); // phpcs:ignore
            $sanitizer = $sanitizers[ $config['type'] ] ?? 'sanitize_text_field';
            $value     = call_user_func( $sanitizer, $raw );
            update_post_meta( $post_id, $key, $value );
        }
    }

    // ── Seat enforcement (WooCommerce add-to-cart) ────────────────────────────

    public static function block_if_seats_full( $passed, $product_id, $quantity ) {
        $course_id = self::get_course_by_product( $product_id );
        if ( ! $course_id ) {
            return $passed;
        }

        $total = (int) get_post_meta( $course_id, '_mf_total_seats', true );
        $taken = (int) get_post_meta( $course_id, '_mf_seats_taken', true );

        if ( $total > 0 && $taken >= $total ) {
            wc_add_notice(
                esc_html__( 'عذراً، لقد امتلأت مقاعد هذه الدورة.', 'molkfilm' ),
                'error'
            );
            return false;
        }
        return $passed;
    }

    // ── Increment seats on paid WC order ──────────────────────────────────────

    public static function increment_seats_on_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $course_id  = self::get_course_by_product( $product_id );
            if ( $course_id ) {
                $taken = (int) get_post_meta( $course_id, '_mf_seats_taken', true );
                update_post_meta( $course_id, '_mf_seats_taken', $taken + 1 );
            }
        }
    }

    // ── Increment seats on free Tutor enroll ─────────────────────────────────

    public static function increment_seats_on_free_enroll( $course_id ) {
        $taken = (int) get_post_meta( $course_id, '_mf_seats_taken', true );
        update_post_meta( $course_id, '_mf_seats_taken', $taken + 1 );
    }

    // ── Helper: find course by WC product ID ──────────────────────────────────

    public static function get_course_by_product( $product_id ) {
        $courses = get_posts( [
            'post_type'      => 'courses',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => '_tutor_course_product_id',
                    'value' => $product_id,
                ],
            ],
            'fields' => 'ids',
        ] );
        return $courses ? $courses[0] : null;
    }

    // ── Template helpers ──────────────────────────────────────────────────────

    /** Returns all custom meta for a course as an associative array. */
    public static function get_course_meta( $course_id ) {
        $out = [];
        foreach ( self::FIELDS as $key => $_ ) {
            $out[ ltrim( $key, '_' ) ] = get_post_meta( $course_id, $key, true );
        }
        return $out;
    }

    /** Returns seats availability as [ 'taken', 'total', 'pct', 'is_full' ]. */
    public static function get_seats_info( $course_id ) {
        $total = (int) get_post_meta( $course_id, '_mf_total_seats', true );
        $taken = (int) get_post_meta( $course_id, '_mf_seats_taken', true );
        $pct   = $total > 0 ? round( ( $taken / $total ) * 100 ) : 0;
        return [
            'taken'   => $taken,
            'total'   => $total,
            'pct'     => $pct,
            'is_full' => $total > 0 && $taken >= $total,
        ];
    }

    /** Human-readable mode label. */
    public static function get_mode_label( $mode ) {
        $labels = [
            'online'  => esc_html__( 'أونلاين', 'molkfilm' ),
            'offline' => esc_html__( 'وجاهي',   'molkfilm' ),
            'hybrid'  => esc_html__( 'هجين',    'molkfilm' ),
        ];
        return $labels[ $mode ] ?? esc_html__( 'أونلاين', 'molkfilm' );
    }
}
