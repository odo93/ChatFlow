<?php
/**
 * MolkFilm_SEO
 *
 * Outputs dynamic <title>, meta description, Open Graph tags,
 * Twitter Card, JSON-LD Course schema, Google Analytics snippet,
 * and an XML sitemap endpoint — all reading from wp_options defaults
 * or per-course overrides.
 *
 * Compatible with Rank Math / Yoast: deactivates its own output
 * when those plugins are active, so there is never duplicate markup.
 */

defined( 'ABSPATH' ) || exit;

class MolkFilm_SEO {

    public static function init() {
        // Bail if Rank Math or Yoast is handling SEO
        add_action( 'wp_head', [ __CLASS__, 'maybe_output_meta' ], 1 );
        add_action( 'wp_head', [ __CLASS__, 'inject_google_analytics' ], 2 );
        add_filter( 'wp_title',            [ __CLASS__, 'filter_title' ], 10, 2 );
        add_filter( 'document_title_parts',[ __CLASS__, 'filter_title_parts' ] );

        // JSON-LD on single course pages
        add_action( 'wp_head', [ __CLASS__, 'output_json_ld' ], 5 );

        // XML sitemap
        add_action( 'init', [ __CLASS__, 'register_sitemap_rewrite' ] );
        add_action( 'template_redirect', [ __CLASS__, 'serve_sitemap' ] );
        add_filter( 'robots_txt', [ __CLASS__, 'append_sitemap_to_robots' ], 10, 2 );
    }

    // ── Title filter ──────────────────────────────────────────────────────────

    public static function filter_title( $title, $sep ) {
        if ( self::is_third_party_seo() ) return $title;
        return self::build_title() . " $sep " . get_bloginfo( 'name' );
    }

    public static function filter_title_parts( $parts ) {
        if ( self::is_third_party_seo() ) return $parts;
        $parts['title'] = self::build_title();
        return $parts;
    }

    private static function build_title() {
        if ( is_singular( 'courses' ) ) {
            $seo_title = get_post_meta( get_the_ID(), '_mf_seo_title', true );
            return $seo_title ?: get_the_title();
        }
        if ( is_post_type_archive( 'courses' ) ) {
            return esc_html__( 'الدورات — ملك فيلم', 'molkfilm' );
        }
        if ( is_tax( 'course-category' ) ) {
            return single_term_title( '', false ) . ' — ' . esc_html__( 'ملك فيلم', 'molkfilm' );
        }
        return get_option( 'molkfilm_seo_title', get_bloginfo( 'name' ) );
    }

    // ── Meta tags ─────────────────────────────────────────────────────────────

    public static function maybe_output_meta() {
        if ( self::is_third_party_seo() ) return;
        ?>
        <!-- Molk Film SEO Meta -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php

        $desc   = self::get_description();
        $title  = self::build_title();
        $url    = get_permalink() ?: home_url( '/' );
        $image  = self::get_og_image();
        $locale = is_singular( 'courses' ) ? ( get_post_meta( get_the_ID(), '_mf_seo_locale', true ) ?: 'ar_EG' ) : 'ar_EG';

        if ( $desc ) {
            printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
        }

        // Open Graph
        printf( '<meta property="og:type" content="%s">' . "\n", is_singular() ? 'article' : 'website' );
        printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
        if ( $desc ) {
            printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
        }
        printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
        printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( $locale ) );
        printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_option( 'molkfilm_site_name', get_bloginfo( 'name' ) ) ) );
        if ( $image ) {
            printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
            printf( '<meta property="og:image:width" content="1200">' . "\n" );
            printf( '<meta property="og:image:height" content="630">' . "\n" );
        }

        // Twitter Card
        printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
        printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
        if ( $desc ) {
            printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
        }
        if ( $image ) {
            printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
        }

        // Canonical
        printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
    }

    private static function get_description() {
        if ( is_singular( 'courses' ) ) {
            $custom = get_post_meta( get_the_ID(), '_mf_seo_description', true );
            if ( $custom ) return $custom;
            $excerpt = get_the_excerpt();
            if ( $excerpt ) return $excerpt;
        }
        return get_option( 'molkfilm_seo_description', '' );
    }

    private static function get_og_image() {
        if ( is_singular() && has_post_thumbnail() ) {
            $src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
            return $src ? $src[0] : '';
        }
        $logo = get_option( 'molkfilm_logo_url', '' );
        return $logo;
    }

    // ── JSON-LD Course schema ─────────────────────────────────────────────────

    public static function output_json_ld() {
        if ( self::is_third_party_seo() ) return;
        if ( ! is_singular( 'courses' ) ) return;

        $course_id = get_the_ID();
        $meta      = MolkFilm_Course_Fields::get_course_meta( $course_id );
        $product_id= get_post_meta( $course_id, '_tutor_course_product_id', true );
        $product   = $product_id ? wc_get_product( $product_id ) : null;

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Course',
            'name'        => get_the_title(),
            'description' => self::get_description() ?: get_the_excerpt(),
            'url'         => get_permalink(),
            'inLanguage'  => 'ar',
            'provider'    => [
                '@type' => 'Organization',
                'name'  => get_option( 'molkfilm_site_name', 'ملك فيلم' ),
                'url'   => home_url(),
            ],
        ];

        if ( $meta['mf_instructor_name'] ) {
            $schema['instructor'] = [
                '@type' => 'Person',
                'name'  => $meta['mf_instructor_name'],
            ];
        }

        if ( $meta['mf_total_hours_text'] ) {
            $schema['timeRequired'] = $meta['mf_total_hours_text'];
        }

        if ( $meta['mf_start_date'] ) {
            $schema['startDate'] = $meta['mf_start_date'];
        }

        if ( has_post_thumbnail() ) {
            $src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
            if ( $src ) {
                $schema['image'] = $src[0];
            }
        }

        if ( $product ) {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'price'         => $product->get_price(),
                'priceCurrency' => get_option( 'molkfilm_currency', 'EGP' ),
                'availability'  => 'https://schema.org/InStock',
                'url'           => get_permalink(),
            ];
        }

        $mode_map = [
            'online'  => 'Online',
            'offline' => 'Offline',
            'hybrid'  => 'BlendedLearning',
        ];
        $mode = $meta['mf_mode'] ?? 'online';
        if ( isset( $mode_map[ $mode ] ) ) {
            $schema['courseMode'] = $mode_map[ $mode ];
        }

        printf(
            '<script type="application/ld+json">%s</script>' . "\n",
            wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
        );
    }

    // ── Google Analytics ──────────────────────────────────────────────────────

    public static function inject_google_analytics() {
        $ga_id = get_option( 'molkfilm_ga_id', '' );
        if ( ! $ga_id ) return;
        // Sanitize: only allow GA4 (G-XXXXXXXX) or UA-XXXXX format
        if ( ! preg_match( '/^(G-[A-Z0-9]+|UA-[0-9]+-[0-9]+)$/', $ga_id ) ) return;
        ?>
        <!-- Google Analytics (ملك فيلم) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc_js( $ga_id ); ?>', { 'anonymize_ip': true });
        </script>
        <?php
    }

    // ── XML Sitemap ───────────────────────────────────────────────────────────

    public static function register_sitemap_rewrite() {
        if ( ! get_option( 'molkfilm_sitemap_enabled', '1' ) ) return;
        add_rewrite_rule( '^molkfilm-sitemap\.xml$', 'index.php?molkfilm_sitemap=1', 'top' );
        add_rewrite_tag( '%molkfilm_sitemap%', '([^&]+)' );
    }

    public static function serve_sitemap() {
        if ( ! get_query_var( 'molkfilm_sitemap' ) ) return;
        if ( ! get_option( 'molkfilm_sitemap_enabled', '1' ) ) return;

        header( 'Content-Type: application/xml; charset=UTF-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Home
        echo self::sitemap_url( home_url( '/' ), date( 'Y-m-d' ), 'daily', '1.0' );

        // Courses archive
        echo self::sitemap_url( get_post_type_archive_link( 'courses' ), date( 'Y-m-d' ), 'daily', '0.9' );

        // Individual courses
        $courses = get_posts( [ 'post_type' => 'courses', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' ] );
        foreach ( $courses as $id ) {
            echo self::sitemap_url( get_permalink( $id ), get_post_modified_time( 'Y-m-d', false, $id ), 'weekly', '0.8' );
        }

        // Category pages
        $terms = get_terms( [ 'taxonomy' => 'course-category', 'hide_empty' => true ] );
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                echo self::sitemap_url( get_term_link( $term ), date( 'Y-m-d' ), 'weekly', '0.7' );
            }
        }

        // Core pages
        $pages = get_pages( [ 'post_status' => 'publish' ] );
        foreach ( $pages as $page ) {
            echo self::sitemap_url( get_permalink( $page->ID ), $page->post_modified_date, 'monthly', '0.6' );
        }

        echo '</urlset>';
        exit;
    }

    private static function sitemap_url( $loc, $lastmod = '', $changefreq = 'monthly', $priority = '0.5' ) {
        $out  = "  <url>\n";
        $out .= "    <loc>" . esc_url( $loc ) . "</loc>\n";
        if ( $lastmod ) {
            $out .= "    <lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
        }
        $out .= "    <changefreq>" . esc_html( $changefreq ) . "</changefreq>\n";
        $out .= "    <priority>" . esc_html( $priority ) . "</priority>\n";
        $out .= "  </url>\n";
        return $out;
    }

    public static function append_sitemap_to_robots( $output, $public ) {
        if ( $public && get_option( 'molkfilm_sitemap_enabled', '1' ) ) {
            $output .= "\nSitemap: " . esc_url( home_url( '/molkfilm-sitemap.xml' ) ) . "\n";
        }
        return $output;
    }

    // ── Utility ───────────────────────────────────────────────────────────────

    private static function is_third_party_seo() {
        return defined( 'WPSEO_VERSION' ) || // Yoast
               defined( 'RANK_MATH_VERSION' ) || // Rank Math
               class_exists( 'AIOSEOP_Plugin' );  // All-in-One SEO
    }
}
