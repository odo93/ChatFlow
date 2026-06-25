<?php
/**
 * front-page.php — Molk Film homepage
 *
 * Sections:
 *  1. Full-width hero with animated waves background
 *  2. Stats bar (total courses, students, hours, instructors)
 *  3. Featured courses grid (latest 6)
 *  4. How it works / steps
 *  5. CTA banner
 *  6. Categories strip
 */

defined( 'ABSPATH' ) || exit;
get_header();

$hero_title    = get_option( 'molkfilm_hero_title',    'اكتشف فن صناعة الأفلام' );
$hero_subtitle = get_option( 'molkfilm_hero_subtitle', 'دورات احترافية في صناعة الأفلام مع خبراء الصناعة' );
?>

<!-- ── 1. Hero ────────────────────────────────────────────────────────────── -->
<section class="mf-hero mf-hp-hero" aria-labelledby="hero-heading">
    <div class="mf-container">
        <span class="badge-pill"><?php esc_html_e( 'أكاديمية ملك فيلم', 'molkfilm' ); ?></span>
        <h1 id="hero-heading" class="mf-hp-hero__title">
            <?php echo esc_html( $hero_title ); ?>
        </h1>
        <p class="mf-hp-hero__subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
        <div class="mf-hp-hero__cta">
            <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>"
               class="btn-primary mf-btn-hero">
                <?php esc_html_e( 'استعرض الدورات', 'molkfilm' ); ?>
            </a>
            <a href="#how-it-works" class="mf-btn-ghost">
                <?php esc_html_e( 'كيف يعمل؟', 'molkfilm' ); ?>
            </a>
        </div>
    </div>
    <!-- decorative circles -->
    <div class="mf-hero-deco" aria-hidden="true">
        <span class="mf-deco-circle mf-deco-circle--1"></span>
        <span class="mf-deco-circle mf-deco-circle--2"></span>
        <span class="mf-deco-circle mf-deco-circle--3"></span>
    </div>
</section>

<!-- ── 2. Stats bar ───────────────────────────────────────────────────────── -->
<section class="mf-hp-stats" aria-label="<?php esc_attr_e( 'إحصائيات الأكاديمية', 'molkfilm' ); ?>">
    <div class="mf-container">
        <div class="mf-hp-stats__grid">
            <?php
            // Live counts from DB
            $course_count  = wp_count_posts( 'courses' )->publish ?? 0;
            $student_count = count( get_users( [ 'role__in' => [ 'subscriber', 'student', 'tutor_student' ], 'fields' => 'ID', 'number' => 9999 ] ) );
            $stats = [
                [ 'value' => $course_count,  'label' => 'دورة احترافية',        'icon' => '&#127916;' ],
                [ 'value' => $student_count, 'label' => 'طالب مسجّل',            'icon' => '&#128100;' ],
                [ 'value' => '21+',          'label' => 'ساعة محتوى لكل دورة',  'icon' => '&#9200;' ],
                [ 'value' => '100%',         'label' => 'محتوى تطبيقي عملي',    'icon' => '&#127942;' ],
            ];
            foreach ( $stats as $stat ) :
            ?>
            <div class="mf-hp-stat">
                <span class="mf-hp-stat__icon" aria-hidden="true"><?php echo $stat['icon']; ?></span>
                <strong class="mf-hp-stat__value"><?php echo esc_html( $stat['value'] ); ?></strong>
                <span class="mf-hp-stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── 3. Featured courses ────────────────────────────────────────────────── -->
<section class="mf-hp-courses" aria-labelledby="courses-heading">
    <div class="mf-container">
        <div class="mf-section-head-row">
            <h2 class="mf-section-title" id="courses-heading">
                <?php esc_html_e( 'دوراتنا المميزة', 'molkfilm' ); ?>
            </h2>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>"
               class="mf-link-all">
                <?php esc_html_e( 'عرض كل الدورات', 'molkfilm' ); ?> &larr;
            </a>
        </div>

        <?php
        $featured_courses = get_posts( [
            'post_type'      => 'courses',
            'posts_per_page' => 6,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
        ?>

        <?php if ( $featured_courses ) : ?>
        <div class="mf-courses-grid">
            <?php foreach ( $featured_courses as $course ) :
                $cid       = $course->ID;
                $meta      = MolkFilm_Course_Fields::get_course_meta( $cid );
                $seats     = MolkFilm_Course_Fields::get_seats_info( $cid );
                $mode_lbl  = MolkFilm_Course_Fields::get_mode_label( $meta['mf_mode'] );
                $product_id = get_post_meta( $cid, '_tutor_course_product_id', true );
                $product    = $product_id ? wc_get_product( $product_id ) : null;
                $price_html = $product ? $product->get_price_html() : '';
            ?>
            <article class="mf-course-card">
                <div class="mf-course-card__thumb">
                    <?php if ( has_post_thumbnail( $cid ) ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $cid ) ); ?>" tabindex="-1">
                            <?php echo get_the_post_thumbnail( $cid, 'large', [ 'loading' => 'lazy', 'alt' => esc_attr( $course->post_title ) ] ); ?>
                        </a>
                    <?php else : ?>
                        <div class="mf-thumb-placeholder"></div>
                    <?php endif; ?>
                    <span class="badge-pill mf-course-card__badge"><?php echo esc_html( $mode_lbl ); ?></span>
                    <?php if ( $seats['is_full'] ) : ?>
                        <div class="mf-course-card__sold-out"><?php esc_html_e( 'ممتلئ', 'molkfilm' ); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mf-course-card__body">
                    <h3 class="mf-course-card__title">
                        <a href="<?php echo esc_url( get_permalink( $cid ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a>
                    </h3>
                    <div class="mf-course-card__meta">
                        <?php if ( $meta['mf_instructor_name'] ) : ?>
                            <span><?php echo esc_html( $meta['mf_instructor_name'] ); ?></span>
                        <?php endif; ?>
                        <?php if ( $meta['mf_total_hours_text'] ) : ?>
                            <span><?php echo esc_html( $meta['mf_total_hours_text'] ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ( $seats['total'] > 0 ) : ?>
                        <div class="mf-seats-bar">
                            <div class="mf-seats-bar__track">
                                <div class="mf-seats-bar__fill" data-pct="<?php echo esc_attr( $seats['pct'] ); ?>"></div>
                            </div>
                            <div class="mf-seats-bar__label">
                                <?php printf( esc_html__( '%1$d / %2$d مقعد', 'molkfilm' ), $seats['taken'], $seats['total'] ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mf-course-card__footer">
                    <div class="mf-course-card__price-row">
                        <?php if ( $price_html ) echo '<div class="mf-course-card__price">' . wp_kses_post( $price_html ) . '</div>'; ?>
                        <a href="<?php echo esc_url( get_permalink( $cid ) ); ?>" class="btn-primary mf-card-cta">
                            <?php esc_html_e( 'سجّل الآن', 'molkfilm' ); ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
            <p class="mf-no-courses"><?php esc_html_e( 'قريباً — ترقبوا دوراتنا القادمة!', 'molkfilm' ); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- ── 4. How it works ────────────────────────────────────────────────────── -->
<section class="mf-how" id="how-it-works" aria-labelledby="how-heading">
    <div class="mf-container">
        <h2 class="mf-section-title" id="how-heading" style="text-align:center;display:block;">
            <?php esc_html_e( 'كيف تبدأ رحلتك؟', 'molkfilm' ); ?>
        </h2>
        <p style="text-align:center;color:var(--muted);margin-bottom:48px;">
            <?php esc_html_e( 'أربع خطوات بسيطة تفصلك عن احتراف صناعة الأفلام', 'molkfilm' ); ?>
        </p>
        <div class="mf-steps">
            <?php
            $steps = [
                [ 'num' => '١', 'title' => 'اختر دورتك',        'desc' => 'تصفّح دوراتنا واختر ما يناسب مستواك وهدفك.' ],
                [ 'num' => '٢', 'title' => 'سجّل وادفع',         'desc' => 'أكمل التسجيل بأمان عبر Paymob أو PayTabs أو PayPal.' ],
                [ 'num' => '٣', 'title' => 'ابدأ التعلّم',       'desc' => 'اشترك في الجلسات المباشرة وشاهد التسجيلات متى شئت.' ],
                [ 'num' => '٤', 'title' => 'احصل على شهادتك',   'desc' => 'أنهِ الدورة وأنت تحمل شهادة معتمدة من ملك فيلم.' ],
            ];
            foreach ( $steps as $i => $step ) :
            ?>
            <div class="mf-step">
                <div class="mf-step__num" aria-hidden="true"><?php echo esc_html( $step['num'] ); ?></div>
                <h3 class="mf-step__title"><?php echo esc_html( $step['title'] ); ?></h3>
                <p class="mf-step__desc"><?php echo esc_html( $step['desc'] ); ?></p>
                <?php if ( $i < count( $steps ) - 1 ) : ?>
                    <div class="mf-step__arrow" aria-hidden="true">&larr;</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── 5. CTA banner ──────────────────────────────────────────────────────── -->
<section class="mf-cta-banner" aria-labelledby="cta-heading">
    <div class="mf-container">
        <div class="mf-cta-banner__inner">
            <div>
                <h2 id="cta-heading"><?php esc_html_e( 'هل أنت مستعد لبدء رحلتك السينمائية؟', 'molkfilm' ); ?></h2>
                <p><?php esc_html_e( 'انضم الآن إلى مئات الطلاب الذين يحتلّون الشاشات الكبرى.', 'molkfilm' ); ?></p>
            </div>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>"
               class="btn-primary" style="flex-shrink:0;font-size:1.1rem;padding:16px 36px !important;">
                <?php esc_html_e( 'سجّل الآن', 'molkfilm' ); ?>
            </a>
        </div>
    </div>
</section>

<!-- ── 6. Categories strip ────────────────────────────────────────────────── -->
<section class="mf-hp-cats" aria-labelledby="cats-heading">
    <div class="mf-container">
        <h2 class="mf-section-title" id="cats-heading"><?php esc_html_e( 'استكشف حسب التخصص', 'molkfilm' ); ?></h2>
        <div class="mf-cat-cards">
            <?php
            $terms = get_terms( [ 'taxonomy' => 'course-category', 'hide_empty' => false ] );
            $cat_icons = [ 'filmmaking' => '&#127916;', 'directing' => '&#127909;', 'editing' => '&#9986;', 'screenwriting' => '&#128221;' ];
            if ( ! is_wp_error( $terms ) && $terms ) :
                foreach ( $terms as $term ) :
                    $icon = $cat_icons[ $term->slug ] ?? '&#128218;';
            ?>
            <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="mf-cat-card">
                <span class="mf-cat-card__icon" aria-hidden="true"><?php echo $icon; ?></span>
                <span class="mf-cat-card__name"><?php echo esc_html( $term->name ); ?></span>
                <span class="mf-cat-card__count">
                    <?php printf( esc_html__( '%d دورة', 'molkfilm' ), $term->count ); ?>
                </span>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<style>
/* ── Homepage-specific styles ── */
.mf-hp-hero { padding: 80px 0 96px; }
.mf-hp-hero__title {
    font-size: clamp(2.25rem, 6vw, 4rem);
    font-weight: 900;
    color: var(--brand-yellow);
    margin: 16px 0 20px;
    line-height: 1.15;
}
.mf-hp-hero__subtitle { font-size: clamp(1rem, 2.5vw, 1.3rem); opacity: .9; max-width: 600px; margin: 0 auto 36px; }
.mf-hp-hero__cta { display: flex; flex-wrap: wrap; gap: 16px; justify-content: center; }
.mf-btn-hero { font-size: 1.15rem !important; padding: 16px 40px !important; }
.mf-btn-ghost {
    padding: 14px 32px;
    border: 2px solid rgba(255,255,255,.5);
    border-radius: var(--radius);
    color: var(--white);
    text-decoration: none;
    font-weight: 700;
    transition: border-color var(--transition), background var(--transition);
}
.mf-btn-ghost:hover { border-color: var(--brand-yellow); background: rgba(255,216,68,.1); }

/* Decorative circles */
.mf-hero-deco { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
.mf-deco-circle {
    position: absolute;
    border-radius: 50%;
    border: 2px solid rgba(255,216,68,.12);
}
.mf-deco-circle--1 { width: 400px; height: 400px; top: -100px; left: -100px; }
.mf-deco-circle--2 { width: 250px; height: 250px; bottom: -60px; right: 10%; }
.mf-deco-circle--3 { width: 120px; height: 120px; top: 40px; right: 30%; background: rgba(255,216,68,.04); }

/* Stats bar */
.mf-hp-stats {
    background: var(--brand-green-dark);
    padding: 32px 0;
    direction: rtl;
}
.mf-hp-stats__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
}
.mf-hp-stat {
    text-align: center;
    padding: 16px;
    border-left: 1px solid rgba(255,255,255,.1);
}
.mf-hp-stat:last-child { border-left: none; }
.mf-hp-stat__icon { font-size: 1.8rem; display: block; margin-bottom: 6px; }
.mf-hp-stat__value { display: block; font-size: 2rem; font-weight: 900; color: var(--brand-yellow); line-height: 1; }
.mf-hp-stat__label { font-size: .85rem; color: rgba(255,255,255,.7); margin-top: 4px; display: block; }

/* Section head row */
.mf-hp-courses { padding: 72px 0; }
.mf-section-head-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 36px; direction: rtl; }
.mf-link-all { color: var(--brand-green); font-weight: 700; text-decoration: none; font-size: .95rem; }
.mf-link-all:hover { text-decoration: underline; }

/* How it works */
.mf-how { background: #f7f9f7; padding: 72px 0; direction: rtl; }
.mf-steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    position: relative;
    align-items: start;
}
.mf-step {
    text-align: center;
    padding: 24px 16px;
    position: relative;
}
.mf-step__num {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: var(--brand-green);
    color: var(--brand-yellow);
    font-size: 1.5rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.mf-step__title { font-size: 1.05rem; font-weight: 800; margin-bottom: 8px; color: var(--text-dark); }
.mf-step__desc  { font-size: .88rem; color: var(--muted); line-height: 1.6; }
.mf-step__arrow {
    position: absolute;
    top: 44px;
    left: -12px;
    font-size: 1.5rem;
    color: var(--brand-green);
    opacity: .4;
}

/* CTA Banner */
.mf-cta-banner {
    background: var(--brand-green);
    background-image: url('<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/waves-bg.svg' ); ?>');
    background-size: cover;
    padding: 64px 0;
    direction: rtl;
}
.mf-cta-banner__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    flex-wrap: wrap;
}
.mf-cta-banner h2 { color: var(--brand-yellow); font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 800; margin: 0 0 8px; }
.mf-cta-banner p  { color: rgba(255,255,255,.85); margin: 0; }

/* Categories */
.mf-hp-cats { padding: 72px 0; }
.mf-cat-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 32px;
    direction: rtl;
}
.mf-cat-card {
    background: var(--white);
    border: 2px solid transparent;
    border-radius: var(--radius-lg);
    padding: 28px 20px;
    text-align: center;
    text-decoration: none;
    box-shadow: var(--shadow-card);
    transition: border-color var(--transition), transform var(--transition);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.mf-cat-card:hover { border-color: var(--brand-green); transform: translateY(-3px); }
.mf-cat-card__icon { font-size: 2.25rem; }
.mf-cat-card__name { font-size: 1.05rem; font-weight: 700; color: var(--text-dark); }
.mf-cat-card__count { font-size: .82rem; color: var(--muted); }

/* Responsive */
@media (max-width: 900px) {
    .mf-hp-stats__grid { grid-template-columns: repeat(2, 1fr); }
    .mf-steps { grid-template-columns: repeat(2, 1fr); }
    .mf-step__arrow { display: none; }
}
@media (max-width: 600px) {
    .mf-hp-stats__grid { grid-template-columns: repeat(2, 1fr); }
    .mf-steps { grid-template-columns: 1fr; }
    .mf-cta-banner__inner { flex-direction: column; text-align: center; }
    .mf-section-head-row { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>

<?php get_footer(); ?>
