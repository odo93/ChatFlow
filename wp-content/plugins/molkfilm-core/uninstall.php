<?php
/**
 * Molk Film Core — uninstall.php
 *
 * Runs when the user deletes the plugin from wp-admin → Plugins.
 * Removes all wp_options created by this plugin.
 * Does NOT delete course posts, orders, or users — those belong to the site.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$options = [
    // Setup flags
    'molkfilm_sample_seeded',
    'molkfilm_sample_course_id',
    'molkfilm_sample_product_id',
    'molkfilm_page_ids',

    // Site identity
    'molkfilm_site_name',
    'molkfilm_tagline',
    'molkfilm_hero_title',
    'molkfilm_hero_subtitle',
    'molkfilm_logo_url',

    // Contact + social
    'molkfilm_contact_email',
    'molkfilm_contact_phone',
    'molkfilm_facebook',
    'molkfilm_instagram',
    'molkfilm_youtube',

    // Commerce
    'molkfilm_currency',
    'molkfilm_language',

    // SEO
    'molkfilm_ga_id',
    'molkfilm_seo_title',
    'molkfilm_seo_description',
    'molkfilm_sitemap_enabled',

    // Payment gateways
    'molkfilm_paymob_api_key',
    'molkfilm_paymob_integration_id',
    'molkfilm_paymob_iframe_id',
    'molkfilm_paymob_hmac_secret',
    'molkfilm_paytabs_profile_id',
    'molkfilm_paytabs_server_key',
    'molkfilm_paypal_client_id',
    'molkfilm_paypal_secret',
    'molkfilm_paypal_mode',
];

foreach ( $options as $opt ) {
    delete_option( $opt );
}

// Remove rewrite rule added for sitemap
delete_option( 'rewrite_rules' );
