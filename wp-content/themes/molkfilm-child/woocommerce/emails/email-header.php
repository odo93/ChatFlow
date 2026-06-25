<?php
/**
 * WooCommerce email header override — Molk Film branded.
 * Place: {theme}/woocommerce/emails/email-header.php
 * WooCommerce will automatically use this instead of its default.
 *
 * @var string $email_heading
 */

defined( 'ABSPATH' ) || exit;

$site_name = get_option( 'molkfilm_site_name', 'ملك فيلم' );
$tagline   = get_option( 'molkfilm_tagline',   'أكاديمية صناعة الأفلام' );
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $email_heading ); ?></title>
<style type="text/css">
  @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap');
  body { margin: 0; padding: 0; background: #F4F7F5; font-family: Tajawal, Arial, sans-serif; direction: rtl; }
  .email-wrap { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(21,85,65,.12); }
  .email-header { background: #155541; padding: 32px; text-align: center; }
  .email-header__logo { color: #FFD844; font-size: 1.8rem; font-weight: 900; margin: 0; }
  .email-header__tagline { color: rgba(255,255,255,.7); font-size: .85rem; margin: 6px 0 0; }
  .email-heading-bar { background: #184133; padding: 20px 32px; text-align: center; }
  .email-heading-bar h1 { color: #FFD844; font-size: 1.25rem; font-weight: 800; margin: 0; }
  .email-body { padding: 32px; color: #1A2E26; font-size: .95rem; line-height: 1.7; }
  .email-footer { background: #184133; padding: 20px 32px; text-align: center; color: rgba(255,255,255,.6); font-size: .8rem; }
  a { color: #155541; }
  .btn { display: inline-block; background: #FFD844; color: #184133 !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 800; margin: 16px 0; }
  table.order-details { width: 100%; border-collapse: collapse; margin: 20px 0; direction: rtl; }
  table.order-details th { background: #F4F7F5; padding: 10px 14px; text-align: right; font-weight: 700; border-bottom: 2px solid rgba(21,85,65,.12); }
  table.order-details td { padding: 10px 14px; border-bottom: 1px solid rgba(21,85,65,.08); }
</style>
</head>
<body>
<div class="email-wrap">
  <div class="email-header">
    <h1 class="email-header__logo"><?php echo esc_html( $site_name ); ?></h1>
    <p class="email-header__tagline"><?php echo esc_html( $tagline ); ?></p>
  </div>
  <?php if ( $email_heading ) : ?>
  <div class="email-heading-bar">
    <h1><?php echo esc_html( $email_heading ); ?></h1>
  </div>
  <?php endif; ?>
  <div class="email-body">
