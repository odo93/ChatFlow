<?php
/**
 * Template: archive-courses.php
 *
 * Overrides Tutor LMS default course listing.
 * Displays a hero banner, category filters, and a responsive card grid.
 * Uses brand variables from brand.css and custom meta from MolkFilm_Course_Fields.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────── -->
<section class="mf-hero">
    <div class="mf-container">
        <span class="badge-pill"><?php esc_html_e( 'الدورات المتاحة', 'molkfilm' ); ?></span>
        <h1><?php esc_html_e( 'اكتشف دوراتنا الاحترافية', 'molkfilm' ); ?></h1>
        <p><?php esc_html_e( 'تعلّم صناعة الأفلام والإخراج والمونتاج من خبراء الصناعة', 'molkfilm' ); ?></p>
    </div>
</section>

<!-- ── Category filter tabs ───────────────────────────────────────────────── -->
<section class="mf-filters" aria-label="<?php esc_attr_e( 'تصفية الدورات', 'molkfilm' ); ?>">
    <div class="mf-container">
        <?php
        $terms = get_terms( [
            'taxonomy'   => 'course-category',
            'hide_empty' => true,
        ] );
        if ( ! is_wp_error( $terms ) && $terms ) :
            $current_cat = get_query_var( 'course-category' ) ?: '';
            ?>
            <div class="mf-cat-tabs" role="tablist">
                <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>"
                   class="mf-cat-tab <?php echo $current_cat ? '' : 'is-active'; ?>"
                   role="tab" aria-selected="<?php echo $current_cat ? 'false' : 'true'; ?>">
                    <?php esc_html_e( 'الكل', 'molkfilm' ); ?>
                </a>
                <?php foreach ( $terms as $term ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $term ) ); ?>"
                       class="mf-cat-tab <?php echo ( $current_cat === $term->slug ) ? 'is-active' : ''; ?>"
                       role="tab" aria-selected="<?php echo ( $current_cat === $term->slug ) ? 'true' : 'false'; ?>">
                        <?php echo esc_html( $term->name ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Course grid ───────────────────────────────────────────────────────── -->
<main class="mf-archive" id="main">
    <div class="mf-container">

        <?php if ( have_posts() ) : ?>

            <div class="mf-courses-grid" role="list">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $course_id = get_the_ID();
                    $meta      = MolkFilm_Course_Fields::get_course_meta( $course_id );
                    $seats     = MolkFilm_Course_Fields::get_seats_info( $course_id );
                    $mode_lbl  = MolkFilm_Course_Fields::get_mode_label( $meta['mf_mode'] );

                    // WooCommerce product price
                    $product_id = get_post_meta( $course_id, '_tutor_course_product_id', true );
                    $product    = $product_id ? wc_get_product( $product_id ) : null;
                    $price_html = $product ? $product->get_price_html() : '';
                    ?>
                    <article class="mf-course-card" role="listitem"
                             aria-label="<?php echo esc_attr( get_the_title() ); ?>">

                        <!-- Thumbnail -->
                        <div class="mf-course-card__thumb">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <a href="<?php the_permalink(); ?>" tabindex="-1">
                                    <?php the_post_thumbnail( 'large', [ 'loading' => 'lazy', 'alt' => get_the_title() ] ); ?>
                                </a>
                            <?php else : ?>
                                <div class="mf-thumb-placeholder" aria-hidden="true"></div>
                            <?php endif; ?>

                            <!-- Mode badge -->
                            <span class="badge-pill mf-course-card__badge">
                                <?php echo esc_html( $mode_lbl ); ?>
                            </span>

                            <!-- Sold out overlay -->
                            <?php if ( $seats['is_full'] ) : ?>
                                <div class="mf-course-card__sold-out" aria-label="<?php esc_attr_e( 'المقاعد ممتلئة', 'molkfilm' ); ?>">
                                    <?php esc_html_e( 'ممتلئ', 'molkfilm' ); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Body -->
                        <div class="mf-course-card__body">
                            <h2 class="mf-course-card__title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <div class="mf-course-card__meta">
                                <?php if ( $meta['mf_instructor_name'] ) : ?>
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                        </svg>
                                        <?php echo esc_html( $meta['mf_instructor_name'] ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( $meta['mf_total_hours_text'] ) : ?>
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm.5 11H11V7h1.5v6zm0 4H11v-1.5h1.5V17z"/>
                                        </svg>
                                        <?php echo esc_html( $meta['mf_total_hours_text'] ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( $meta['mf_session_count'] ) : ?>
                                    <span>
                                        <?php
                                        printf(
                                            /* translators: %d: number of sessions */
                                            esc_html__( '%d جلسة', 'molkfilm' ),
                                            (int) $meta['mf_session_count']
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( $meta['mf_start_date'] ) : ?>
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
                                        </svg>
                                        <?php echo esc_html( date_i18n( 'j F Y', strtotime( $meta['mf_start_date'] ) ) ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Seats progress bar -->
                            <?php if ( $seats['total'] > 0 ) : ?>
                                <div class="mf-seats-bar" role="progressbar"
                                     aria-valuenow="<?php echo esc_attr( $seats['taken'] ); ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="<?php echo esc_attr( $seats['total'] ); ?>"
                                     aria-label="<?php esc_attr_e( 'المقاعد المتاحة', 'molkfilm' ); ?>">
                                    <div class="mf-seats-bar__track">
                                        <div class="mf-seats-bar__fill"
                                             data-pct="<?php echo esc_attr( $seats['pct'] ); ?>"></div>
                                    </div>
                                    <div class="mf-seats-bar__label">
                                        <?php
                                        printf(
                                            /* translators: 1: taken, 2: total */
                                            esc_html__( '%1$d / %2$d مقعد', 'molkfilm' ),
                                            $seats['taken'],
                                            $seats['total']
                                        );
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Footer: price + CTA -->
                        <div class="mf-course-card__footer">
                            <div class="mf-course-card__price-row">
                                <?php if ( $price_html ) : ?>
                                    <div class="mf-course-card__price"><?php echo wp_kses_post( $price_html ); ?></div>
                                <?php endif; ?>
                                <a href="<?php the_permalink(); ?>"
                                   class="btn-primary mf-card-cta"
                                   aria-label="<?php echo esc_attr( sprintf( __( 'تسجيل في %s', 'molkfilm' ), get_the_title() ) ); ?>">
                                    <?php esc_html_e( 'سجّل الآن', 'molkfilm' ); ?>
                                </a>
                            </div>
                        </div>

                    </article>
                <?php endwhile; ?>
            </div><!-- .mf-courses-grid -->

            <!-- Pagination -->
            <div class="mf-pagination">
                <?php
                echo wp_kses_post( paginate_links( [
                    'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
                    'next_text' => is_rtl() ? '&larr;' : '&rarr;',
                ] ) );
                ?>
            </div>

        <?php else : ?>
            <div class="mf-no-courses">
                <p><?php esc_html_e( 'لا توجد دورات متاحة حالياً. تابعنا قريباً!', 'molkfilm' ); ?></p>
            </div>
        <?php endif; ?>

    </div><!-- .mf-container -->
</main>

<style>
/* Archive-specific layout */
.mf-filters { background: #f7f9f7; border-bottom: 1px solid rgba(21,85,65,.08); padding: 16px 0; }
.mf-cat-tabs { display: flex; flex-wrap: wrap; gap: 8px; direction: rtl; }
.mf-cat-tab {
    padding: 6px 18px; border-radius: 999px; font-size: .875rem; font-weight: 600;
    background: transparent; border: 2px solid var(--brand-green); color: var(--brand-green);
    text-decoration: none; transition: all .2s;
}
.mf-cat-tab.is-active,
.mf-cat-tab:hover { background: var(--brand-green); color: var(--white); }

.mf-archive { padding: 48px 0; }
.mf-courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 28px;
    margin-bottom: 48px;
}

.mf-thumb-placeholder { background: var(--brand-green-light); aspect-ratio: 16/9; }

.mf-course-card__sold-out {
    position: absolute; inset: 0; background: rgba(24,65,51,.75);
    display: flex; align-items: center; justify-content: center;
    color: var(--brand-yellow); font-weight: 800; font-size: 1.25rem;
}
.mf-course-card__price-row {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    direction: rtl;
}
.mf-card-cta { padding: 8px 20px !important; font-size: .9rem !important; }

.mf-pagination { text-align: center; margin-top: 32px; }
.mf-pagination .page-numbers {
    display: inline-flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; border-radius: 50%; margin: 0 3px;
    border: 2px solid var(--brand-green); color: var(--brand-green);
    text-decoration: none; font-weight: 600;
}
.mf-pagination .page-numbers.current { background: var(--brand-green); color: var(--white); }

.mf-no-courses { text-align: center; padding: 80px 0; color: var(--muted); font-size: 1.1rem; }

@media (max-width: 640px) {
    .mf-courses-grid { grid-template-columns: 1fr; }
}
</style>

<?php get_footer(); ?>
