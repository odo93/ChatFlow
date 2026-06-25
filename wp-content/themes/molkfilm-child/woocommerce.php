<?php
/**
 * woocommerce.php — wrapper for WooCommerce archive / single-product pages.
 * Astra handles WC natively; this file overrides the container style only.
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>

<div class="mf-wc-page" dir="rtl">
    <div class="mf-container">
        <?php woocommerce_content(); ?>
    </div>
</div>

<style>
.mf-wc-page { padding: 48px 0; direction: rtl; }

/* Cart + checkout RTL fixes */
.woocommerce-cart table.cart,
.woocommerce-checkout #order_review { direction: rtl; }
.woocommerce form .form-row { direction: rtl; text-align: right; }
.woocommerce-billing-fields h3,
.woocommerce-shipping-fields h3 { color: var(--brand-green-dark); }

/* Order review */
.woocommerce-checkout #payment { background: var(--white); border-radius: var(--radius); }
.woocommerce-checkout #payment .payment_methods li label { direction: rtl; text-align: right; }

/* My account */
.woocommerce-MyAccount-navigation ul { list-style: none; padding: 0; }
.woocommerce-MyAccount-navigation ul li a {
    display: block; padding: 10px 16px;
    border-radius: var(--radius);
    color: var(--brand-green);
    text-decoration: none;
    font-weight: 600;
    transition: background var(--transition);
}
.woocommerce-MyAccount-navigation ul li a:hover,
.woocommerce-MyAccount-navigation ul li.is-active a {
    background: var(--brand-green);
    color: var(--white);
}

/* Product single (course add-to-cart fallback) */
.woocommerce div.product .woocommerce-tabs ul.tabs li a { direction: rtl; }
</style>

<?php get_footer(); ?>
