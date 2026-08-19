<?php
/**
 * Module Name:       Reservas Metransfers
 * Description:       Sistema avanzado de reservas y traslados con gestión de vehículos, precios dinámicos, restricción geográfica europea e integración con Google Maps y WooCommerce.
 * Version:           5.0.0
 * Author:            Merchan.Dev
 * Text Domain:       wp-transfer-booking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define Plugin Constants
if ( ! defined( 'WPTB_VERSION' ) ) {
    define( 'WPTB_VERSION', '5.0.2' );
}
if ( ! defined( 'WPTB_PLUGIN_DIR' ) ) {
    define( 'WPTB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPTB_PLUGIN_URL' ) ) {
    define( 'WPTB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Include necessary files
require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-activator.php';
require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-loader.php';
require_once WPTB_PLUGIN_DIR . 'includes/cpt-destinations.php';
require_once WPTB_PLUGIN_DIR . 'includes/shortcode-transfers-search.php'; // Premium Transfers Search

// Include admin classes
if (is_admin()) {
    require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-bookings-admin.php';
}

// Initialize the plugin
if (!function_exists('run_wp_transfer_booking')) {
function run_wp_transfer_booking() {
    $plugin = new WPTB_Loader();
    $plugin->run();
}
} 
run_wp_transfer_booking();
