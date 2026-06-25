<?php
/**
 * Molk Film Child Theme — functions.php
 *
 * Responsibilities:
 *  - Enqueue parent (Astra) + child styles and scripts
 *  - Load Google Fonts (Tajawal, Cairo, Poppins)
 *  - Force RTL support and add <html lang="ar" dir="rtl">
 *  - Register widget areas and nav menus
 *  - Add theme support declarations
 *  - Language toggle helper
 *  - Branded WooCommerce / Tutor LMS tweaks
 */

defined( 'ABSPATH' ) || exit;

define( 'MOLKFILM_THEME_VERSION', '1.0.0' );
define( 'MOLKFILM_THEME_DIR', get_stylesheet_directory() );
define( 'MOLKFILM_THEME_URI', get_stylesheet_directory_uri() );

/* ── Theme support ───────────────────────────────── */
add_action( 'after_setup_theme', 'molkfilm_theme_setup' );
function molkfilm_theme_setup() {
    load_child_theme_textdomain( 'molkfilm', MOLKFILM_THEME_DIR . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'gallery', 'caption', 'script', 'style' ] );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'customize-selective-refresh-widgets' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );

    // Nav menus
    register_nav_menus( [
        'primary'  => esc_html__( 'القائمة الرئيسية', 'molkfilm' ),
        'footer'   => esc_html__( 'قائمة التذييل', 'molkfilm' ),
        'mobile'   => esc_html__( 'القائمة المحمول', 'molkfilm' ),
    ] );
}

/* ── Enqueue styles + scripts ────────────────────── */
add_action( 'wp_enqueue_scripts', 'molkfilm_enqueue_assets' );
function molkfilm_enqueue_assets() {
    // Google Fonts — Tajawal (Arabic), Cairo (Arabic fallback), Poppins (Latin)
    $google_fonts_url = 'https://fonts.googleapis.com/css2?' .
        'family=Tajawal:wght@400;500;700;800;900' .
        '&family=Cairo:wght@400;600;700;900' .
        '&family=Poppins:wght@400;500;600;700' .
        '&display=swap';
    wp_enqueue_style( 'molkfilm-google-fonts', $google_fonts_url, [], null );

    // Parent theme (Astra)
    wp_enqueue_style(
        'astra-theme-style',
        get_template_directory_uri() . '/style.css',
        [],
        MOLKFILM_THEME_VERSION
    );

    // Brand design system
    wp_enqueue_style(
        'molkfilm-brand',
        MOLKFILM_THEME_URI . '/assets/css/brand.css',
        [ 'astra-theme-style' ],
        MOLKFILM_THEME_VERSION
    );

    // Child theme style (extends parent + brand)
    wp_enqueue_style(
        'molkfilm-child',
        MOLKFILM_THEME_URI . '/style.css',
        [ 'molkfilm-brand' ],
        MOLKFILM_THEME_VERSION
    );

    // RTL stylesheet loaded automatically by WordPress for RTL locales
    // but we also offer it explicitly:
    if ( is_rtl() ) {
        wp_enqueue_style(
            'molkfilm-rtl',
            MOLKFILM_THEME_URI . '/rtl.css',
            [ 'molkfilm-child' ],
            MOLKFILM_THEME_VERSION
        );
    }

    // Main JS
    wp_enqueue_script(
        'molkfilm-main',
        MOLKFILM_THEME_URI . '/assets/js/main.js',
        [],
        MOLKFILM_THEME_VERSION,
        true  // footer
    );

    // Localise JS with dynamic data
    wp_localize_script( 'molkfilm-main', 'MolkFilm', [
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'molkfilm_nonce' ),
        'langUrls' => molkfilm_get_lang_urls(),
        'i18n'     => [
            'soldOut' => esc_html__( 'المقاعد ممتلئة', 'molkfilm' ),
            'started' => esc_html__( 'بدأ البرنامج', 'molkfilm' ),
        ],
    ] );
}

/* ── Language URL helper (Polylang / WPML compat) ── */
function molkfilm_get_lang_urls() {
    $urls = [];
    if ( function_exists( 'pll_the_languages' ) ) {
        // Polylang
        $langs = pll_the_languages( [ 'raw' => 1 ] );
        foreach ( $langs as $lang ) {
            $urls[ $lang['slug'] ] = $lang['url'];
        }
    } elseif ( function_exists( 'icl_get_languages' ) ) {
        // WPML
        $langs = icl_get_languages( 'skip_missing=0' );
        foreach ( $langs as $lang ) {
            $urls[ $lang['language_code'] ] = $lang['url'];
        }
    }
    return $urls;
}

/* ── Force RTL attributes on <html> ─────────────── */
add_filter( 'language_attributes', 'molkfilm_html_attrs' );
function molkfilm_html_attrs( $output ) {
    // Ensure dir="rtl" is always present for Arabic
    if ( get_locale() === 'ar' || strpos( get_locale(), 'ar_' ) === 0 ) {
        $output = preg_replace( '/dir="[^"]*"/', 'dir="rtl"', $output );
        if ( strpos( $output, 'dir=' ) === false ) {
            $output .= ' dir="rtl"';
        }
    }
    return $output;
}

/* ── Language toggle button in header ─────────────── */
add_action( 'wp_body_open', 'molkfilm_lang_toggle_button' );
function molkfilm_lang_toggle_button() {
    $current_lang = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE :
                    ( function_exists( 'pll_current_language' ) ? pll_current_language() : substr( get_locale(), 0, 2 ) );
    $label        = $current_lang === 'ar' ? 'English' : 'عربي';
    ?>
    <div class="mf-lang-toggle-wrap" aria-label="<?php esc_attr_e( 'تغيير اللغة', 'molkfilm' ); ?>">
        <button id="mf-lang-toggle" class="mf-lang-btn" aria-label="<?php echo esc_attr( $label ); ?>">
            <?php echo esc_html( $label ); ?>
        </button>
    </div>
    <?php
}

/* ── Widget areas ────────────────────────────────── */
add_action( 'widgets_init', 'molkfilm_register_sidebars' );
function molkfilm_register_sidebars() {
    $shared = [
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ];

    register_sidebar( array_merge( $shared, [
        'name' => esc_html__( 'الشريط الجانبي للدورات', 'molkfilm' ),
        'id'   => 'courses-sidebar',
    ] ) );

    register_sidebar( array_merge( $shared, [
        'name' => esc_html__( 'تذييل — عمود ١', 'molkfilm' ),
        'id'   => 'footer-col-1',
    ] ) );

    register_sidebar( array_merge( $shared, [
        'name' => esc_html__( 'تذييل — عمود ٢', 'molkfilm' ),
        'id'   => 'footer-col-2',
    ] ) );

    register_sidebar( array_merge( $shared, [
        'name' => esc_html__( 'تذييل — عمود ٣', 'molkfilm' ),
        'id'   => 'footer-col-3',
    ] ) );
}

/* ── Astra child-theme style load order fix ──────── */
add_filter( 'astra_get_css_prefix', '__return_empty_string' );

/* ── Remove emoji scripts (performance) ─────────── */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/* ── WooCommerce: re-enqueue core WC styles (remove only theme CSS) ─────── */
// Keep WC functional styles; brand.css overrides tokens on top
add_filter( 'woocommerce_enqueue_styles', function( $styles ) {
    // Remove WC's layout/theme CSS; keep the base (handles form inputs etc.)
    unset( $styles['woocommerce-layout'] );
    unset( $styles['woocommerce-smallscreen'] );
    return $styles;
} );

/* ── Template loader: point 'courses' CPT to our /templates/ folder ─────── */
add_filter( 'template_include', 'molkfilm_template_loader', 99 );
function molkfilm_template_loader( $template ) {
    if ( is_singular( 'courses' ) ) {
        $custom = MOLKFILM_THEME_DIR . '/templates/single-courses.php';
        if ( file_exists( $custom ) ) return $custom;
    }
    if ( is_post_type_archive( 'courses' ) || is_tax( 'course-category' ) ) {
        $custom = MOLKFILM_THEME_DIR . '/templates/archive-courses.php';
        if ( file_exists( $custom ) ) return $custom;
    }
    return $template;
}

/* ── Tutor LMS: override Tutor's own templates via its filter ────────────── */
add_filter( 'tutor_get_template_path', 'molkfilm_tutor_template_path', 10, 2 );
function molkfilm_tutor_template_path( $template, $name ) {
    $custom = MOLKFILM_THEME_DIR . '/tutor/' . $name . '.php';
    if ( file_exists( $custom ) ) {
        return $custom;
    }
    return $template;
}

/* ── Tutor LMS: load after WC ────────────────────── */
add_action( 'wp_enqueue_scripts', 'molkfilm_load_tutor_compat', 20 );
function molkfilm_load_tutor_compat() {
    // Ensure Tutor's own styles load; brand.css overrides colour tokens on top
}

/* ── Custom excerpt length ───────────────────────── */
add_filter( 'excerpt_length', fn() => 25 );
add_filter( 'excerpt_more',   fn() => '&hellip;' );

/* ── Breadcrumb separator ────────────────────────── */
add_filter( 'astra_breadcrumb_separator', fn() => is_rtl() ? ' ← ' : ' → ' );

/* ── Body classes ────────────────────────────────── */
add_filter( 'body_class', 'molkfilm_body_classes' );
function molkfilm_body_classes( $classes ) {
    $classes[] = 'molkfilm-theme';
    if ( is_rtl() ) {
        $classes[] = 'rtl-site';
    }
    return $classes;
}

/* ── Customizer additions ────────────────────────── */
add_action( 'customize_register', 'molkfilm_customizer' );
function molkfilm_customizer( $wp_customize ) {
    $wp_customize->add_section( 'molkfilm_branding', [
        'title'    => esc_html__( 'ملك فيلم — الهوية البصرية', 'molkfilm' ),
        'priority' => 30,
    ] );

    // Hero headline
    $wp_customize->add_setting( 'molkfilm_hero_title', [
        'default'           => 'اكتشف فن صناعة الأفلام',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'molkfilm_hero_title', [
        'label'   => esc_html__( 'عنوان الهيرو', 'molkfilm' ),
        'section' => 'molkfilm_branding',
        'type'    => 'text',
    ] );

    // Hero subtitle
    $wp_customize->add_setting( 'molkfilm_hero_subtitle', [
        'default'           => 'دورات احترافية في صناعة الأفلام مع خبراء الصناعة',
        'sanitize_callback' => 'sanitize_textarea_field',
    ] );
    $wp_customize->add_control( 'molkfilm_hero_subtitle', [
        'label'   => esc_html__( 'وصف الهيرو', 'molkfilm' ),
        'section' => 'molkfilm_branding',
        'type'    => 'textarea',
    ] );
}
