<?php
/**
 * DIAGNOSTIC SCRIPT FOR METRANSFERS BOOKING PLUGIN
 * Place this file in the plugin root and access via: /wp-content/plugins/PLUGIN BOOKING/diagnostic.php
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access Denied');
}

echo "<h1>Metransfers Booking Plugin - Diagnostic Report</h1>";
echo "<style>body{font-family:monospace;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// 1. Check if plugin is active
echo "<h2>1. Plugin Status</h2>";
if (is_plugin_active('PLUGIN BOOKING/wp-booking-plugin.php')) {
    echo "<p class='ok'>✓ Plugin is ACTIVE</p>";
} else {
    echo "<p class='error'>✗ Plugin is INACTIVE</p>";
}

// 2. Check if page exists
echo "<h2>2. Page Check</h2>";
$page = get_page_by_path('reservas-metransfers');
if ($page) {
    echo "<p class='ok'>✓ Page '/reservas-metransfers/' EXISTS (ID: {$page->ID})</p>";
    echo "<p>Status: {$page->post_status}</p>";
    echo "<p>Content: <code>" . esc_html($page->post_content) . "</code></p>";
} else {
    echo "<p class='error'>✗ Page '/reservas-metransfers/' DOES NOT EXIST</p>";
    echo "<p class='warning'>Solution: Deactivate and Reactivate the plugin</p>";
}

// 3. Check database tables
echo "<h2>3. Database Tables</h2>";
global $wpdb;
$tables = [
    'wptb_vehicles',
    'wptb_vehicle_types',
    'wptb_vehicle_images',
    'wptb_bookings'
];

foreach ($tables as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
        echo "<p class='ok'>✓ Table `{$full_table}` exists ({$count} rows)</p>";
    } else {
        echo "<p class='error'>✗ Table `{$full_table}` MISSING</p>";
    }
}

// 4. Check vehicles specifically
echo "<h2>4. Vehicle Data</h2>";
$table_vehicles = $wpdb->prefix . 'wptb_vehicles';
$vehicles = $wpdb->get_results("SELECT * FROM $table_vehicles");
if ($vehicles) {
    echo "<p class='ok'>✓ Found " . count($vehicles) . " vehicle(s) in database:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Active</th><th>Min Price</th></tr>";
    foreach ($vehicles as $v) {
        $active_class = $v->is_active ? 'ok' : 'warning';
        echo "<tr class='$active_class'>";
        echo "<td>{$v->id}</td>";
        echo "<td>{$v->name}</td>";
        echo "<td>" . ($v->is_active ? 'YES' : 'NO') . "</td>";
        echo "<td>€{$v->min_oneway_price}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ NO VEHICLES FOUND IN DATABASE</p>";
    echo "<p class='warning'>Solution: Add vehicles via Admin Panel</p>";
}

// 5. Check shortcodes registered
echo "<h2>5. Shortcode Registration</h2>";
global $shortcode_tags;
if (isset($shortcode_tags['wptb_booking_form'])) {
    echo "<p class='ok'>✓ Shortcode [wptb_booking_form] is registered</p>";
} else {
    echo "<p class='error'>✗ Shortcode [wptb_booking_form] NOT registered</p>";
}

if (isset($shortcode_tags['wptb_booking_details'])) {
    echo "<p class='ok'>✓ Shortcode [wptb_booking_details] is registered</p>";
} else {
    echo "<p class='error'>✗ Shortcode [wptb_booking_details] NOT registered</p>";
}

// 6. Check Google Maps API Key
echo "<h2>6. Google Maps API</h2>";
$api_key = get_option('wptb_google_maps_api_key');
if ($api_key) {
    echo "<p class='ok'>✓ Google Maps API Key is SET</p>";
    echo "<p>Key: " . substr($api_key, 0, 20) . "...</p>";
} else {
    echo "<p class='error'>✗ Google Maps API Key is MISSING</p>";
}

// 7. Test AJAX endpoint
echo "<h2>7. AJAX Endpoint Test</h2>";
echo "<p>Testing wp_ajax_wptb_get_vehicles...</p>";
$ajax_url = admin_url('admin-ajax.php');
echo "<p>URL: <code>$ajax_url?action=wptb_get_vehicles</code></p>";
echo "<p><a href='$ajax_url?action=wptb_get_vehicles' target='_blank'>Click to test manually</a></p>";

// 8. File checks
echo "<h2>8. Critical Files</h2>";
$files = [
    'templates/booking-form.php',
    'templates/booking-details.php',
    'assets/js/booking-app.js',
    'includes/class-wptb-public.php',
    'includes/class-wptb-vehicle-manager.php'
];

foreach ($files as $file) {
    $path = WPTB_PLUGIN_DIR . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "<p class='ok'>✓ {$file} exists ({$size} bytes)</p>";
    } else {
        echo "<p class='error'>✗ {$file} MISSING</p>";
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If you see RED errors above, those need to be fixed first.</p>";
echo "<p><strong>Most Common Issues:</strong></p>";
echo "<ul>";
echo "<li>Page not created → Deactivate/Reactivate plugin</li>";
echo "<li>No vehicles in DB → Add vehicles in admin panel</li>";
echo "<li>Shortcodes not registered → Check class-wptb-loader.php</li>";
echo "</ul>";
