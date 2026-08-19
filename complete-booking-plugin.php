<?php
/**
 * Plugin Name:       Sistema de Reservas - Metransfers (Renovado)
 * Plugin URI:        https://merchan.dev/plugin-para-wordpress
 * Description:       Sistema de reservas avanzado para transfers, con integración nativa de Redsys "GETNET" de Banco Santander y Google Maps. Desarrollado por Merchan.Dev.
 * Version:           5.0.2
 * Author:            Merchan.Dev
 * Text Domain:       wptb
 */

function cbp_catch_fatal_errors() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('Complete Booking Plugin Fatal Error: ' . print_r($error, true));
    }
}
register_shutdown_function('cbp_catch_fatal_errors');

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WPTB_VERSION' ) ) {
    define( 'WPTB_VERSION', '5.0.2' );
}
define( 'CBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );



function cbp_activate_metransfers_booking() {
    error_log('Activation started...');
    
    $activator_path = CBP_PLUGIN_DIR . 'modules/booking/includes/class-wptb-activator.php';

    if ( file_exists( $activator_path ) ) {
        require_once $activator_path;
    }

    if ( class_exists( 'WPTB_Activator' ) ) {
        WPTB_Activator::activate();
    }

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cbp_activate_metransfers_booking' );

function cbp_deactivate_metransfers_booking() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cbp_deactivate_metransfers_booking' );

// Fallback key for Google Maps when option storage fails in WP settings.
if ( ! defined( 'WPTB_GOOGLE_MAPS_API_KEY' ) ) {
    define( 'WPTB_GOOGLE_MAPS_API_KEY', 'AIzaSyDCm7DW_8al5QQZwX-ZGTLj7mpwQ9NGYtI' );
}

// 1. Load Modules
// We load the original plugin files. They will define their own constants.
// Since we are traversing directories, their __FILE__ calls will work correctly.

// Load Booking Plugin
if ( file_exists( CBP_PLUGIN_DIR . 'modules/booking/wp-booking-plugin.php' ) ) {
    require_once CBP_PLUGIN_DIR . 'modules/booking/wp-booking-plugin.php';
}

// Load Hotel QR Plugin
if ( file_exists( CBP_PLUGIN_DIR . 'modules/hotel/hotel-qr-plugin.php' ) ) {
    require_once CBP_PLUGIN_DIR . 'modules/hotel/hotel-qr-plugin.php';
}

// 2. Load Unified Integration
require_once CBP_PLUGIN_DIR . 'includes/class-unified-integration.php';

// 3. Initialize
function run_complete_booking_plugin() {
    $plugin = new Unified_Integration();
    $plugin->run();
}
run_complete_booking_plugin();

// --- SCRIPT TEMPORAL PARA ARREGLAR LA RESERVA 10001 ---
add_action('admin_init', function() {
    if ( isset($_GET['fix_10001']) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wptb_bookings';
        $wpdb->query( "UPDATE {$table} SET hotel_token = NULL WHERE id = 10001" );
        die('<h1>¡Éxito!</h1><p>La reserva 10001 ha sido desligada del hotel correctamente en la base de datos activa.</p><p><a href="' . admin_url('admin.php?page=wptb-reservas') . '">Volver a las reservas</a></p>');
    }
});
// --------------------------------------------------------
