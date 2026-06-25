<?php
/**
 * page.php — generic page template (Contact, About, Dashboard, etc.)
 * Wrapped in the brand container with a subtle page header.
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>

<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

    <!-- Page header bar -->
    <div class="mf-page-header">
        <div class="mf-container">
            <h1 class="mf-page-header__title"><?php the_title(); ?></h1>
            <?php
            // Breadcrumb
            if ( function_exists( 'astra_breadcrumb' ) ) {
                astra_breadcrumb();
            }
            ?>
        </div>
    </div>

    <!-- Page content -->
    <main class="mf-page-main" id="main" tabindex="-1">
        <div class="mf-container">
            <div class="mf-page-content">
                <?php the_content(); ?>
                <?php
                wp_link_pages( [
                    'before' => '<div class="page-links">' . esc_html__( 'الصفحات:', 'molkfilm' ),
                    'after'  => '</div>',
                ] );
                ?>
            </div>
        </div>
    </main>

<?php endwhile; endif; ?>

<style>
.mf-page-header {
    background: var(--brand-green);
    padding: 40px 0 32px;
    direction: rtl;
}
.mf-page-header__title {
    color: var(--white);
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    margin: 0;
}
.mf-page-main { padding: 56px 0; direction: rtl; }
.mf-page-content { max-width: 860px; line-height: 1.8; }
.mf-page-content p { margin-bottom: 1.2em; }
</style>

<?php get_footer(); ?>
