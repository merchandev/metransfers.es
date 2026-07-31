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

// Upgrade routine for independent hotel vehicles table
add_action( 'admin_init', function() {
    if ( get_option( 'wptb_version_453_hotel_vehicles_v2' ) !== 'done' ) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        $table_hotel_vehicles = $wpdb->prefix . 'wptb_hotel_vehicles';
        $sql = "CREATE TABLE $table_hotel_vehicles (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            description text,
            capacity int NOT NULL DEFAULT 4,
            image_url varchar(500),
            display_order int DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        dbDelta( $sql );
        
        // Also remove the old columns from wptb_vehicles to clean up if we want, but let's just mark it done.
        update_option( 'wptb_version_453_hotel_vehicles_v2', 'done' );
    }
});

