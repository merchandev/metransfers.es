<?php

class HQP_Loader {

    public function run() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        if (!class_exists('FPDF')) { require_once HQP_PLUGIN_DIR . 'includes/fpdf.php'; }
        require_once HQP_PLUGIN_DIR . 'admin/class-hqp-admin.php';
        require_once HQP_PLUGIN_DIR . 'public/class-hqp-public.php';
    }

    private function define_admin_hooks() {
        $plugin_admin = new HQP_Admin();
        
        // CPT & Meta Boxes
        add_action( 'init', array( $plugin_admin, 'register_hotel_cpt' ) );
        add_action( 'add_meta_boxes', array( $plugin_admin, 'add_hotel_meta_boxes' ) );
        add_action( 'save_post', array( $plugin_admin, 'save_hotel_meta' ) );
        
        // Admin Menu
        add_action( 'admin_init', array( $plugin_admin, 'create_booking_page' ) );
        add_action( 'admin_menu', array( $plugin_admin, 'add_hotel_submenu' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_admin_scripts' ) );
        
        // Admin Columns
        add_filter( 'manage_hotel_partner_posts_columns', array( $plugin_admin, 'add_custom_columns' ) );
        add_action( 'manage_hotel_partner_posts_custom_column', array( $plugin_admin, 'render_custom_columns' ), 10, 2 );

        // Custom Actions
        add_action( 'admin_post_hqp_download_qr', array( $plugin_admin, 'download_qr_code' ) );
        add_action( 'admin_post_hqp_download_flyer', array( $plugin_admin, 'download_flyer_pdf' ) );
    }

    private function define_public_hooks() {
        $plugin_public = new HQP_Public();

        // Cookie & Logic
        add_action( 'init', array( $plugin_public, 'check_url_token' ) );
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
        
        // Shortcodes and Content
        add_action( 'init', array( $plugin_public, 'register_shortcodes' ) );
        add_action( 'template_redirect', array( $plugin_public, 'check_url_token' ) );

        // Specialized HQP Booking AJAX
        add_action( 'wp_ajax_hqp_get_fixed_pricing', array( $plugin_public, 'ajax_get_fixed_pricing' ) );
        add_action( 'wp_ajax_nopriv_hqp_get_fixed_pricing', array( $plugin_public, 'ajax_get_fixed_pricing' ) );

        add_action( 'wp_ajax_hqp_create_booking', array( $plugin_public, 'ajax_create_booking' ) );
        add_action( 'wp_ajax_nopriv_hqp_create_booking', array( $plugin_public, 'ajax_create_booking' ) );

        // Interception (Server Side)
        // Priority 5 to run before the main plugin's priority 10 (if it uses default)
        // Actually, main plugin uses 'wp_ajax_wptb_save_booking', so we hook into it too.
        // We want to run BEFORE the main plugin handler to modify inputs? 
        // No, in WP AJAX, only one action fires. But we can't easily override unless we remove the other hook.
        // STRATEGY: We will hook into the same action with High Priority (1) to validate/modify $_POST 
        // before the main plugin sees it? 
        // $_POST is global, so yes.
        add_action( 'wp_ajax_wptb_save_booking', array( $plugin_public, 'intercept_booking_submission' ), 1 );
        add_action( 'wp_ajax_nopriv_wptb_save_booking', array( $plugin_public, 'intercept_booking_submission' ), 1 );

        // NEW: Filter for server-side price calculation in Stripe intent
        add_filter( 'wptb_booking_price', array( $plugin_public, 'apply_booking_discount' ) );
    }
}

