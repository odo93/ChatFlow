<?php
/**
 * Plugin Name:  Molk Film Core
 * Plugin URI:   https://molkfilm.com
 * Description:  All-in-one configuration plugin for Molk Film (ملك فيلم) academy.
 *               Handles: course setup, custom fields, SEO, payments, and admin dashboard.
 *               Zero manual clicking — everything runs in code.
 * Version:      1.0.0
 * Author:       Molk Film
 * Author URI:   https://molkfilm.com
 * Text Domain:  molkfilm
 * Domain Path:  /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * License:      GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ────────────────────────────────────────────────────────────────
define( 'MOLKFILM_VERSION',     '1.0.0' );
define( 'MOLKFILM_PLUGIN_FILE', __FILE__ );
define( 'MOLKFILM_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'MOLKFILM_PLUGIN_URI',  plugin_dir_url( __FILE__ ) );
define( 'MOLKFILM_TEXT_DOMAIN', 'molkfilm' );

// ── Autoloader ───────────────────────────────────────────────────────────────
spl_autoload_register( function ( $class ) {
    $prefix = 'MolkFilm\\';
    $len    = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    $relative = substr( $class, $len );
    $file     = MOLKFILM_PLUGIN_DIR . 'includes/class-' .
                strtolower( str_replace( [ '\\', '_' ], [ '/', '-' ], $relative ) ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// ── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'molkfilm_init', 0 );
function molkfilm_init() {
    load_plugin_textdomain( MOLKFILM_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Load all feature classes
    require_once MOLKFILM_PLUGIN_DIR . 'includes/class-setup.php';
    require_once MOLKFILM_PLUGIN_DIR . 'includes/class-course-fields.php';
    require_once MOLKFILM_PLUGIN_DIR . 'includes/class-seo.php';
    require_once MOLKFILM_PLUGIN_DIR . 'includes/class-payments.php';
    require_once MOLKFILM_PLUGIN_DIR . 'includes/class-admin-dashboard.php';

    MolkFilm_Course_Fields::init();
    MolkFilm_SEO::init();
    MolkFilm_Payments::init();
    MolkFilm_Admin_Dashboard::init();
}

// ── Activation hook ───────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'molkfilm_activate' );
function molkfilm_activate() {
    require_once MOLKFILM_PLUGIN_DIR . 'includes/class-setup.php';
    require_once MOLKFILM_PLUGIN_DIR . 'includes/class-payments.php'; // needed for molkfilm_after_setup
    MolkFilm_Setup::run();
    do_action( 'molkfilm_after_setup' ); // lets class-payments.php wire PayPal options
    flush_rewrite_rules();
}

// ── Deactivation hook ────────────────────────────────────────────────────────
register_deactivation_hook( __FILE__, 'molkfilm_deactivate' );
function molkfilm_deactivate() {
    flush_rewrite_rules();
}

// ── Uninstall: handled via uninstall.php (never from this file) ───────────────
