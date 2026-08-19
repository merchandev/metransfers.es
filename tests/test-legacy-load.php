<?php
/**
 * Minimum Load Test for Legacy Import
 * Run via CLI: php test-legacy-load.php
 */

// Mock WordPress environment
define('ABSPATH', __DIR__ . '/../');
function get_template_directory() { return __DIR__ . '/..'; }
function get_template_directory_uri() { return 'http://localhost/wp-content/themes/metransfers'; }
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {}
function add_shortcode($tag, $callback) {}
function register_activation_hook($file, $function) {}
function register_deactivation_hook($file, $function) {}
function plugin_dir_path($file) { return dirname($file) . '/'; }


class WPDB_Mock {
    public $prefix = 'wp_';
    public function get_charset_collate() { return ''; }
    public function get_var() { return null; }
    public function query() { return null; }
    public function insert() { return null; }
    public function update() { return null; }
    public function get_results() { return []; }
    public function get_row() { return null; }

}
global $wpdb;
$wpdb = new WPDB_Mock();


function get_page_by_path($path) { return null; }
function wp_insert_post($post) { return 1; }

function is_admin() { return false; }
function plugin_dir_url($file) { return get_template_directory_uri() . '/app/Legacy/Hotel/'; }
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_localize_script() {}
function get_option($option, $default = false) { return $default; }
function update_option() {}
function __($text, $domain = 'default') { return $text; }
function esc_html__($text, $domain = 'default') { return $text; }
function add_menu_page() {}
function add_submenu_page() {}
function register_post_type() {}
function register_setting() {}
function get_permalink() { return ''; }

echo "Testing legacy module loading...\n";

try {
    require_once __DIR__ . '/../app/bootstrap.php';
    \MeTransfers\Core\Application::boot();
    
    // Check if key classes exist
    $classes_to_check = [
        'WPTB_Activator',
        'WPTB_Public',
        'WPTB_Admin',
        'WPTB_Pricing',
        'HQP_Public',
        'MeTransfers\\Core\\Assets',
        'MeTransfers\\Admin\\Capabilities',
        'MeTransfers\\Admin\\AuditLog',
        'MeTransfers\\Core\\Migrations',
        'MeTransfers\\Core\\Schema',
        'MeTransfers\\Core\\DataMigrations',
        'MeTransfers\\Core\\Seeds',
        'MeTransfers\\Core\\ReleaseGate',
        'MeTransfers\\Core\\Outbox',
        'MeTransfers\\Core\\OutboxHandler',
        'MeTransfers\\Booking\\I18n',
        'MeTransfers\\I18n\\Language',
        'MeTransfers\\I18n\\Router',
        'MeTransfers\\I18n\\Translation',
        'MeTransfers\\I18n\\Switcher',
        'MeTransfers\\I18n\\Seo',
        'MeTransfers\\I18n\\Admin',
        'MeTransfers\\Booking\\RouteDistance',
        'MeTransfers\\Booking\\ServiceAreaPolicy',
        'MeTransfers\\Booking\\BookingDatePolicy',
        'MeTransfers\\Booking\\RouteContext',
        'MeTransfers\\Booking\\QuoteService',
        'MeTransfers\\Booking\\VehicleCapacityPolicy',
        'MeTransfers\\Booking\\BookingDraftService',
        'MeTransfers\\Booking\\BookingEvents',
        'MeTransfers\\Booking\\ReceiptService',
        'MeTransfers\\Booking\\ReceiptController',
        'MeTransfers\\Pricing\\Money',
        'MeTransfers\\Notifications\\NotificationService',
        'MeTransfers\\Analytics\\PurchaseOutbox',
        'MeTransfers\\Security\\RequestRateLimiter',
        'MeTransfers\\Security\\PathGuard',
        'MeTransfers\\Payments\\Redsys\\Gateway'
    ];
    
    $all_loaded = true;
    foreach ($classes_to_check as $class) {
        if (!class_exists($class)) {
            echo "X FAILED: Class $class not loaded.\n";
            $all_loaded = false;
        } else {
            echo "SUCCESS: Class $class loaded.\n";
        }
    }
    
    if ($all_loaded) {
        echo "\nLegacy modules successfully bootstrapped!\n";
    } else {
        exit(1);
    }
} catch (Exception $e) {
    echo "X FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "X FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
