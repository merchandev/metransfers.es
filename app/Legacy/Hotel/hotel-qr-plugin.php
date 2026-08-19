<?php
/**
 * Module: Hotel QR Discounts
 * Description: Gestión de hoteles asociados y descuentos mediante códigos QR. Integrado con Reservas Metransfers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HQP_VERSION', '1.0.0' );
define( 'HQP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HQP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include Loader
require_once HQP_PLUGIN_DIR . 'includes/class-hqp-loader.php';

// Run
if (!function_exists('run_hotel_qr_plugin')) {
function run_hotel_qr_plugin() {
    $plugin = new HQP_Loader();
    $plugin->run();
}
} 
run_hotel_qr_plugin();

