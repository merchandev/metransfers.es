<?php
namespace MeTransfers\Core;

class Application {
    private static $booted = false;

    public static function boot() {
        if ( self::$booted ) {
            return;
        }
        self::$booted = true;

        if (!defined('MT_PLATFORM_VERSION')) {
            define('MT_PLATFORM_VERSION', '5.0.0');
        }
        if (!defined('MT_PLATFORM_DB_VERSION')) {
            define('MT_PLATFORM_DB_VERSION', '5.1.0');
        }

        if (!defined('WPTB_PLUGIN_DIR')) {
            define('WPTB_PLUGIN_DIR', get_template_directory() . '/app/Legacy/WPTB/');
        }
        if (!defined('WPTB_PLUGIN_URL')) {
            define('WPTB_PLUGIN_URL', get_template_directory_uri() . '/app/Legacy/WPTB/');
        }
        if (!defined('WPTB_VERSION')) {
            define('WPTB_VERSION', MT_PLATFORM_VERSION);
        }
        
        if (!defined('HQP_PLUGIN_DIR')) {
            define('HQP_PLUGIN_DIR', get_template_directory() . '/app/Legacy/Hotel/');
        }
        if (!defined('HQP_PLUGIN_URL')) {
            define('HQP_PLUGIN_URL', get_template_directory_uri() . '/app/Legacy/Hotel/');
        }
        if (!defined('HQP_VERSION')) {
            define('HQP_VERSION', MT_PLATFORM_VERSION);
        }

        self::loadLegacyModules();

        // Boot modern components
        $postTypes = new \MeTransfers\Core\PostTypes();
        add_action('init', [$postTypes, 'register'], 5);
        $shortcodes = new \MeTransfers\Booking\Shortcodes();
        $shortcodes->register();

        $assets = new \MeTransfers\Core\Assets();
        $assets->register();

        $migrations = new \MeTransfers\Core\Migrations();
        $migrations->register();

        if ( is_admin() ) {
            $admin_menu = new \MeTransfers\Admin\Menu();
            add_action( 'admin_menu', array( $admin_menu, 'register' ) );
        }
    }

    private static function loadLegacyModules() {
        if (file_exists(WPTB_PLUGIN_DIR . 'wp-booking-plugin.php')) {
            require_once WPTB_PLUGIN_DIR . 'wp-booking-plugin.php';
        }

        if (file_exists(HQP_PLUGIN_DIR . 'hotel-qr-plugin.php')) {
            require_once HQP_PLUGIN_DIR . 'hotel-qr-plugin.php';
        }

        // The former Unified_Integration shim is intentionally not loaded. Its
        // responsibilities now live in the dedicated booking and hotel modules.
    }
}
