<?php

class WPTB_Loader {

    protected $admin;
    protected $public;

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-admin.php';
        require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-public.php';
        require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-vehicle-manager.php';
        require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-pricing.php';
        require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-vehicles-admin.php';
        require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-dashboard.php';
    }

    public function run() {
        // Admin Hooks
        $plugin_admin = new WPTB_Admin();
        add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
        add_action( 'admin_init', array( $plugin_admin, 'register_settings' ) );
        
        // Dashboard Widgets
        $dashboard = new WPTB_Dashboard();
        $dashboard->init();
        
        // Vehicles Admin Hooks
        $vehicles_admin = new WPTB_Vehicles_Admin();
        add_action( 'admin_menu', array( $vehicles_admin, 'add_vehicles_menu' ) );

        // Public Hooks
        $plugin_public = new WPTB_Public();
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
        add_action( 'init', array( $plugin_public, 'register_shortcodes' ) );
        add_action( 'wp_ajax_wptb_save_booking', array( $plugin_public, 'save_booking' ) );
        add_action( 'wp_ajax_nopriv_wptb_save_booking', array( $plugin_public, 'save_booking' ) );
        
        // New AJAX endpoints for vehicles
        add_action( 'wp_ajax_wptb_get_vehicles', array( $plugin_public, 'ajax_get_vehicles' ) );
        add_action( 'wp_ajax_nopriv_wptb_get_vehicles', array( $plugin_public, 'ajax_get_vehicles' ) );
        add_action( 'wp_ajax_wptb_calculate_price', array( $plugin_public, 'ajax_calculate_price' ) );
        add_action( 'wp_ajax_nopriv_wptb_calculate_price', array( $plugin_public, 'ajax_calculate_price' ) );
        
        // Stripe payment endpoints are handled by Unified_Integration when available.
        if ( method_exists( $plugin_public, 'create_payment_intent' ) ) {
            add_action( 'wp_ajax_wptb_create_payment_intent', array( $plugin_public, 'create_payment_intent' ) );
            add_action( 'wp_ajax_nopriv_wptb_create_payment_intent', array( $plugin_public, 'create_payment_intent' ) );
        }

        if ( method_exists( $plugin_public, 'confirm_payment' ) ) {
            add_action( 'wp_ajax_wptb_confirm_payment', array( $plugin_public, 'confirm_payment' ) );
            add_action( 'wp_ajax_nopriv_wptb_confirm_payment', array( $plugin_public, 'confirm_payment' ) );
        }
    }
}
