<?php
namespace MeTransfers\Core;

class Application {
    public static function boot() {
        if (!defined('MT_PLATFORM_VERSION')) {
            define('MT_PLATFORM_VERSION', '5.0.0');
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
        $shortcodes = new \MeTransfers\Booking\Shortcodes();
        $shortcodes->register();
    }

    private static function loadLegacyModules() {
        if (file_exists(WPTB_PLUGIN_DIR . 'wp-booking-plugin.php')) {
            require_once WPTB_PLUGIN_DIR . 'wp-booking-plugin.php';
        }

        if (file_exists(HQP_PLUGIN_DIR . 'hotel-qr-plugin.php')) {
            require_once HQP_PLUGIN_DIR . 'hotel-qr-plugin.php';
        }

        if (file_exists(get_template_directory() . '/app/Legacy/class-unified-integration.php')) {
            require_once get_template_directory() . '/app/Legacy/class-unified-integration.php';
            
            if (class_exists('Unified_Integration')) {
                $unified_integration = new \Unified_Integration();
                $unified_integration->run();
            }
        }
    }
}