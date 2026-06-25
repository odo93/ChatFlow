<?php
/**
 * 404.php — branded not-found page
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>

<section class="mf-404" dir="rtl">
    <div class="mf-container" style="text-align:center;padding:96px 0;">
        <div style="font-size:6rem;line-height:1;margin-bottom:24px;" aria-hidden="true">&#127909;</div>
        <h1 style="font-size:clamp(2rem,5vw,3.5rem);color:var(--brand-green-dark);">
            <?php esc_html_e( '٤٠٤ — الصفحة غير موجودة', 'molkfilm' ); ?>
        </h1>
        <p style="font-size:1.1rem;color:var(--muted);max-width:480px;margin:16px auto 32px;">
            <?php esc_html_e( 'يبدو أن هذه الصفحة ذهبت خلف الكاميرا ولم تعد! ابحث عن ما تريد أو عد للرئيسية.', 'molkfilm' ); ?>
        </p>
        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo esc_url( home_url() ); ?>" class="btn-primary" style="padding:12px 28px !important;">
                <?php esc_html_e( 'العودة للرئيسية', 'molkfilm' ); ?>
            </a>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>"
               class="mf-btn-ghost" style="padding:12px 28px;border:2px solid var(--brand-green);border-radius:var(--radius);color:var(--brand-green);text-decoration:none;font-weight:700;">
                <?php esc_html_e( 'استعرض الدورات', 'molkfilm' ); ?>
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
