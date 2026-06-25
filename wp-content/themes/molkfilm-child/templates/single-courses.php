<?php
/**
 * Template: single-courses.php
 *
 * Full course detail page.  Layout:
 *   - Full-width hero with course title, instructor, badges
 *   - Two-column: main (description, stats, curriculum) | sidebar (enroll box)
 *   - Stats row (hours, sessions, mode, start date)
 *   - Tutor LMS curriculum / lesson list
 *   - Instructor bio section
 *
 * Reads custom fields via MolkFilm_Course_Fields helpers.
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! have_posts() ) {
    get_footer();
    return;
}

the_post();

$course_id  = get_the_ID();
$meta       = MolkFilm_Course_Fields::get_course_meta( $course_id );
$seats      = MolkFilm_Course_Fields::get_seats_info( $course_id );
$mode_lbl   = MolkFilm_Course_Fields::get_mode_label( $meta['mf_mode'] );

// WC product
$product_id = get_post_meta( $course_id, '_tutor_course_product_id', true );
$product    = $product_id ? wc_get_product( $product_id ) : null;
$price_html = $product ? $product->get_price_html() : '';
$add_to_cart_url = $product ? $product->add_to_cart_url() : '';

// SEO description (used by class-seo.php but also surfaced here)
$excerpt = get_the_excerpt();
?>

<!-- ── Course hero ────────────────────────────────────────────────────────── -->
<section class="mf-course-hero" itemscope itemtype="https://schema.org/Course">
    <meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>">
    <meta itemprop="description" content="<?php echo esc_attr( $excerpt ); ?>">
    <?php if ( $meta['mf_instructor_name'] ) : ?>
        <meta itemprop="instructor" content="<?php echo esc_attr( $meta['mf_instructor_name'] ); ?>">
    <?php endif; ?>

    <div class="mf-container">
        <!-- Breadcrumb -->
        <nav class="mf-breadcrumb" aria-label="<?php esc_attr_e( 'مسار التنقل', 'molkfilm' ); ?>">
            <a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'الرئيسية', 'molkfilm' ); ?></a>
            <span aria-hidden="true">&larr;</span>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>"><?php esc_html_e( 'الدورات', 'molkfilm' ); ?></a>
            <span aria-hidden="true">&larr;</span>
            <span aria-current="page"><?php the_title(); ?></span>
        </nav>

        <div class="mf-course-hero__inner">
            <div class="mf-course-hero__content">
                <!-- Category + mode badges -->
                <div class="mf-hero-badges">
                    <span class="badge-pill"><?php echo esc_html( $mode_lbl ); ?></span>
                    <?php
                    $terms = get_the_terms( $course_id, 'course-category' );
                    if ( $terms && ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $term ) {
                            echo '<a href="' . esc_url( get_term_link( $term ) ) . '" class="badge-pill badge-pill--outline">' . esc_html( $term->name ) . '</a>';
                        }
                    }
                    ?>
                </div>

                <h1 class="mf-course-hero__title" itemprop="name"><?php the_title(); ?></h1>

                <?php if ( $excerpt ) : ?>
                    <p class="mf-course-hero__subtitle"><?php echo esc_html( $excerpt ); ?></p>
                <?php endif; ?>

                <!-- Instructor -->
                <?php if ( $meta['mf_instructor_name'] ) : ?>
                    <div class="mf-course-hero__instructor">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                        <span><?php echo esc_html( $meta['mf_instructor_name'] ); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hero thumbnail -->
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="mf-course-hero__thumb">
                    <?php the_post_thumbnail( 'large', [ 'loading' => 'eager', 'itemprop' => 'image' ] ); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Stats bar ──────────────────────────────────────────────────────────── -->
<section class="mf-stats-section">
    <div class="mf-container">
        <div class="mf-stats-grid">
            <?php if ( $meta['mf_total_hours_text'] ) : ?>
                <div class="mf-stat-item">
                    <span class="mf-stat-item__value"><?php echo esc_html( $meta['mf_total_hours_text'] ); ?></span>
                    <span class="mf-stat-item__label"><?php esc_html_e( 'محتوى تدريبي', 'molkfilm' ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( $meta['mf_session_count'] ) : ?>
                <div class="mf-stat-item">
                    <span class="mf-stat-item__value"><?php echo esc_html( (int) $meta['mf_session_count'] ); ?></span>
                    <span class="mf-stat-item__label"><?php esc_html_e( 'جلسة تدريبية', 'molkfilm' ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( $meta['mf_session_duration_minutes'] ) : ?>
                <div class="mf-stat-item">
                    <span class="mf-stat-item__value"><?php echo esc_html( (int) $meta['mf_session_duration_minutes'] ); ?></span>
                    <span class="mf-stat-item__label"><?php esc_html_e( 'دقيقة / جلسة', 'molkfilm' ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( $seats['total'] > 0 ) : ?>
                <div class="mf-stat-item">
                    <span class="mf-stat-item__value" style="color:<?php echo $seats['is_full'] ? '#ff6b6b' : 'var(--brand-yellow)'; ?>">
                        <?php echo esc_html( $seats['total'] - $seats['taken'] ); ?>
                    </span>
                    <span class="mf-stat-item__label"><?php esc_html_e( 'مقعد متبقٍ', 'molkfilm' ); ?></span>
                </div>
            <?php endif; ?>

            <div class="mf-stat-item">
                <span class="mf-stat-item__value"><?php echo esc_html( $mode_lbl ); ?></span>
                <span class="mf-stat-item__label"><?php esc_html_e( 'طريقة التدريس', 'molkfilm' ); ?></span>
            </div>
        </div>
    </div>
</section>

<!-- ── Two-column content ─────────────────────────────────────────────────── -->
<section class="mf-course-body">
    <div class="mf-container">
        <div class="mf-two-col">

            <!-- Main column -->
            <div class="mf-course-main">

                <!-- Course description -->
                <div class="mf-course-description">
                    <h2 class="mf-section-title"><?php esc_html_e( 'عن الدورة', 'molkfilm' ); ?></h2>
                    <div class="mf-prose"><?php the_content(); ?></div>
                </div>

                <!-- Schedule info -->
                <?php if ( $meta['mf_schedule_text'] || $meta['mf_start_date'] ) : ?>
                    <div class="mf-course-schedule">
                        <h2 class="mf-section-title"><?php esc_html_e( 'الجدول الزمني', 'molkfilm' ); ?></h2>
                        <div class="mf-schedule-card">
                            <?php if ( $meta['mf_start_date'] ) : ?>
                                <div class="mf-schedule-row">
                                    <strong><?php esc_html_e( 'تاريخ البدء:', 'molkfilm' ); ?></strong>
                                    <span data-countdown="<?php echo esc_attr( $meta['mf_start_date'] ); ?>"
                                          class="mf-countdown">
                                        <?php echo esc_html( date_i18n( 'l، j F Y', strtotime( $meta['mf_start_date'] ) ) ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if ( $meta['mf_schedule_text'] ) : ?>
                                <div class="mf-schedule-row">
                                    <strong><?php esc_html_e( 'المواعيد:', 'molkfilm' ); ?></strong>
                                    <span><?php echo esc_html( $meta['mf_schedule_text'] ); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ( $meta['mf_mode'] === 'offline' && $meta['mf_location'] ) : ?>
                                <div class="mf-schedule-row">
                                    <strong><?php esc_html_e( 'الموقع:', 'molkfilm' ); ?></strong>
                                    <span><?php echo esc_html( $meta['mf_location'] ); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tutor LMS curriculum (rendered by Tutor) -->
                <div class="mf-curriculum">
                    <h2 class="mf-section-title"><?php esc_html_e( 'منهج الدورة', 'molkfilm' ); ?></h2>
                    <?php
                    // Tutor LMS renders its own curriculum component when called correctly.
                    // Use the Tutor template function if available; fallback to manual query.
                    if ( function_exists( 'tutor_course_topics' ) ) {
                        tutor_course_topics(); // Tutor's native curriculum accordion
                    } else {
                        // Fallback: manual topic + lesson query
                        $topics = get_posts( [
                            'post_type'      => 'topics',
                            'post_parent'    => $course_id,
                            'posts_per_page' => -1,
                            'orderby'        => 'menu_order',
                            'order'          => 'ASC',
                        ] );

                        if ( $topics ) :
                            echo '<div class="mf-topic-list">';
                            foreach ( $topics as $topic ) :
                                echo '<div class="mf-topic">';
                                echo '<h3 class="mf-topic__title">' . esc_html( $topic->post_title ) . '</h3>';

                                $lessons = get_posts( [
                                    'post_type'      => 'lesson',
                                    'post_parent'    => $topic->ID,
                                    'posts_per_page' => -1,
                                    'orderby'        => 'menu_order',
                                    'order'          => 'ASC',
                                ] );

                                if ( $lessons ) :
                                    echo '<ul class="mf-lesson-list">';
                                    foreach ( $lessons as $lesson ) :
                                        $is_preview = get_post_meta( $lesson->ID, '_is_preview', true );
                                        echo '<li class="mf-lesson-item' . ( $is_preview ? ' is-preview' : '' ) . '">';
                                        if ( $is_preview ) {
                                            echo '<a href="' . esc_url( get_permalink( $lesson->ID ) ) . '">';
                                        }
                                        echo esc_html( $lesson->post_title );
                                        if ( $is_preview ) {
                                            echo ' <span class="badge-pill badge-pill--sm">' . esc_html__( 'مجاني', 'molkfilm' ) . '</span>';
                                            echo '</a>';
                                        }
                                        echo '</li>';
                                    endforeach;
                                    echo '</ul>';
                                endif;

                                echo '</div>'; // .mf-topic
                            endforeach;
                            echo '</div>'; // .mf-topic-list
                        endif;
                    }
                    ?>
                </div>

                <!-- Instructor bio -->
                <?php if ( $meta['mf_instructor_name'] ) : ?>
                    <div class="mf-instructor-bio">
                        <h2 class="mf-section-title"><?php esc_html_e( 'المدرب', 'molkfilm' ); ?></h2>
                        <div class="mf-instructor-card">
                            <div class="mf-instructor-card__avatar" aria-hidden="true">
                                <?php echo esc_html( mb_substr( $meta['mf_instructor_name'], 0, 1 ) ); ?>
                            </div>
                            <div class="mf-instructor-card__info">
                                <h3><?php echo esc_html( $meta['mf_instructor_name'] ); ?></h3>
                                <p><?php esc_html_e( 'مدرب ومخرج محترف في أكاديمية ملك فيلم', 'molkfilm' ); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div><!-- .mf-course-main -->

            <!-- Sticky sidebar: enroll box -->
            <aside class="mf-course-sidebar" aria-label="<?php esc_attr_e( 'معلومات التسجيل', 'molkfilm' ); ?>">
                <div class="mf-enroll-box"
                     data-seats-taken="<?php echo esc_attr( $seats['taken'] ); ?>"
                     data-seats-total="<?php echo esc_attr( $seats['total'] ); ?>">

                    <?php if ( $price_html ) : ?>
                        <div class="mf-enroll-box__price"><?php echo wp_kses_post( $price_html ); ?></div>
                    <?php endif; ?>

                    <!-- Seats progress -->
                    <?php if ( $seats['total'] > 0 ) : ?>
                        <div class="mf-seats-bar" style="margin-bottom: 20px;"
                             role="progressbar"
                             aria-valuenow="<?php echo esc_attr( $seats['taken'] ); ?>"
                             aria-valuemin="0"
                             aria-valuemax="<?php echo esc_attr( $seats['total'] ); ?>">
                            <div class="mf-seats-bar__track">
                                <div class="mf-seats-bar__fill" data-pct="<?php echo esc_attr( $seats['pct'] ); ?>"></div>
                            </div>
                            <div class="mf-seats-bar__label">
                                <?php
                                printf(
                                    esc_html__( 'تم حجز %1$d من %2$d مقعد', 'molkfilm' ),
                                    $seats['taken'],
                                    $seats['total']
                                );
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- CTA button -->
                    <?php if ( $seats['is_full'] ) : ?>
                        <button class="btn-primary btn-enroll is-sold-out" disabled style="width:100%;font-size:1.1rem;padding:14px 0;">
                            <?php esc_html_e( 'المقاعد ممتلئة', 'molkfilm' ); ?>
                        </button>
                    <?php elseif ( $add_to_cart_url ) : ?>
                        <a href="<?php echo esc_url( $add_to_cart_url ); ?>"
                           class="btn-primary btn-enroll"
                           style="width:100%;text-align:center;display:block;font-size:1.1rem;padding:14px 0;">
                            <?php esc_html_e( 'سجّل الآن', 'molkfilm' ); ?>
                        </a>
                    <?php endif; ?>

                    <!-- Features list -->
                    <ul class="mf-enroll-box__features">
                        <?php if ( $meta['mf_total_hours_text'] ) : ?>
                            <li>&#10003; <?php echo esc_html( $meta['mf_total_hours_text'] ) . ' ' . esc_html__( 'محتوى تدريبي', 'molkfilm' ); ?></li>
                        <?php endif; ?>
                        <?php if ( $meta['mf_session_count'] ) : ?>
                            <li>&#10003; <?php printf( esc_html__( '%d جلسة مباشرة', 'molkfilm' ), (int) $meta['mf_session_count'] ); ?></li>
                        <?php endif; ?>
                        <li>&#10003; <?php esc_html_e( 'شهادة إتمام', 'molkfilm' ); ?></li>
                        <li>&#10003; <?php esc_html_e( 'وصول مدى الحياة', 'molkfilm' ); ?></li>
                        <?php if ( $meta['mf_mode'] === 'online' ) : ?>
                            <li>&#10003; <?php esc_html_e( 'تسجيل الجلسات', 'molkfilm' ); ?></li>
                        <?php endif; ?>
                        <?php if ( $meta['mf_start_date'] ) : ?>
                            <li>
                                &#128197; <?php esc_html_e( 'يبدأ', 'molkfilm' ); ?>
                                <?php echo esc_html( date_i18n( 'j F Y', strtotime( $meta['mf_start_date'] ) ) ); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ( $meta['mf_schedule_text'] ) : ?>
                            <li>&#128336; <?php echo esc_html( $meta['mf_schedule_text'] ); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </aside>

        </div><!-- .mf-two-col -->
    </div>
</section>

<style>
/* ── Course hero ── */
.mf-course-hero {
    background-color: var(--brand-green);
    background-image: url('<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/waves-bg.svg' ); ?>');
    background-size: cover;
    color: var(--white);
    padding: 56px 0 40px;
    direction: rtl;
}
.mf-breadcrumb { font-size: .85rem; margin-bottom: 24px; opacity: .8; direction: rtl; }
.mf-breadcrumb a { color: var(--brand-yellow); text-decoration: none; }
.mf-breadcrumb span { margin: 0 8px; }

.mf-course-hero__inner {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 40px;
    align-items: center;
}
.mf-hero-badges { margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 8px; }
.badge-pill--outline {
    background: transparent;
    border: 2px solid var(--brand-yellow);
    color: var(--brand-yellow);
    padding: 4px 14px;
    border-radius: 999px;
    font-weight: 600;
    font-size: .875rem;
    text-decoration: none;
}
.mf-course-hero__title { font-size: clamp(1.75rem, 3.5vw, 2.5rem); color: var(--white); margin-bottom: 12px; }
.mf-course-hero__subtitle { font-size: 1.05rem; opacity: .9; margin-bottom: 20px; }
.mf-course-hero__instructor {
    display: flex; align-items: center; gap: 8px;
    font-size: .95rem; color: var(--brand-yellow);
}
.mf-course-hero__thumb img { border-radius: 12px; width: 100%; }

/* ── Stats ── */
.mf-stats-section { background: var(--brand-green-dark); padding: 24px 0; }
.mf-stats-section .mf-stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    margin: 0;
}

/* ── Body layout ── */
.mf-course-body { padding: 56px 0; }
.mf-two-col {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 40px;
    align-items: start;
    direction: rtl;
}
.mf-course-description,
.mf-course-schedule,
.mf-curriculum,
.mf-instructor-bio { margin-bottom: 48px; }

.mf-prose { line-height: 1.8; font-size: 1.05rem; }
.mf-prose ul { padding-right: 20px; }
.mf-prose li { margin-bottom: 8px; }

/* ── Schedule card ── */
.mf-schedule-card { background: #f7f9f7; border-radius: 8px; padding: 20px; direction: rtl; }
.mf-schedule-row { display: flex; gap: 12px; padding: 8px 0; border-bottom: 1px solid rgba(21,85,65,.08); font-size: .95rem; }
.mf-schedule-row:last-child { border: none; }
.mf-countdown { color: var(--brand-green); font-weight: 700; }

/* ── Topic / lesson list (fallback) ── */
.mf-topic-list { direction: rtl; }
.mf-topic { margin-bottom: 20px; border: 1px solid rgba(21,85,65,.12); border-radius: 8px; overflow: hidden; }
.mf-topic__title {
    background: var(--brand-green); color: var(--white);
    padding: 12px 16px; margin: 0; font-size: 1rem; font-weight: 700;
}
.mf-lesson-list { list-style: none; padding: 0; margin: 0; }
.mf-lesson-item {
    padding: 10px 16px; border-bottom: 1px solid rgba(21,85,65,.06);
    font-size: .9rem; display: flex; align-items: center; gap: 8px;
}
.mf-lesson-item:last-child { border: none; }
.mf-lesson-item.is-preview { color: var(--brand-green); font-weight: 600; }
.mf-lesson-item a { color: inherit; text-decoration: none; display: flex; align-items: center; gap: 8px; }
.badge-pill--sm { padding: 2px 8px; font-size: .75rem; }

/* ── Instructor card ── */
.mf-instructor-card { display: flex; align-items: center; gap: 20px; direction: rtl; }
.mf-instructor-card__avatar {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--brand-green); color: var(--brand-yellow);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 900; flex-shrink: 0;
}
.mf-instructor-card__info h3 { margin: 0 0 4px; font-size: 1.1rem; }
.mf-instructor-card__info p  { margin: 0; color: var(--muted); font-size: .9rem; }

/* ── Sidebar enroll box ── */
.mf-enroll-box { box-shadow: 0 8px 40px rgba(21,85,65,.25); }
.mf-enroll-box.is-scrolled { box-shadow: 0 12px 48px rgba(21,85,65,.35); }
.is-sold-out { opacity: .5 !important; cursor: not-allowed !important; }

/* ── Responsive ── */
@media (max-width: 900px) {
    .mf-course-hero__inner { grid-template-columns: 1fr; }
    .mf-course-hero__thumb { display: none; }
    .mf-two-col { grid-template-columns: 1fr; }
    .mf-course-sidebar { order: -1; }
}
</style>

<?php get_footer(); ?>
