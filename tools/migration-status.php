<?php
/**
 * MeTransfers Platform - Migration Status Monitor
 * Safe for production (no secrets exposed)
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Only allow booting via CLI or WP load
    require_once dirname( __DIR__ ) . '/../wp-load.php';
}

if ( ! current_user_can( 'manage_options' ) && php_sapi_name() !== 'cli' ) {
    wp_die( 'Access denied' );
}

echo "<h2>MeTransfers - System & DB Status</h2>";

global $wpdb;

// 1. Check Tables
$tables = [
    'wptb_bookings',
    'wptb_vehicles',
    'wptb_vehicle_types',
    'wptb_vehicle_images'
];

echo "<h3>Database Tables</h3><ul>";
foreach ( $tables as $table ) {
    $full_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $full_name ) ) === $full_name;
    
    if ( $exists ) {
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $full_name" );
        echo "<li>✅ <strong>$full_name</strong>: $count records</li>";
    } else {
        echo "<li>❌ <strong>$full_name</strong>: MISSING</li>";
    }
}
echo "</ul>";

// 2. Check Object Cache
echo "<h3>Object Cache Status</h3>";
$cache_enabled = wp_using_ext_object_cache();
if ( $cache_enabled ) {
    echo "<p>✅ Persistent Object Cache is <strong>ACTIVE</strong> (Memcached/Redis).</p>";
} else {
    echo "<p>⚠️ Persistent Object Cache is <strong>INACTIVE</strong>. WordPress is using non-persistent memory cache.</p>";
}

// 3. Legacy Module Status
echo "<h3>Legacy Integration Status</h3>";
$legacy_active = class_exists( 'WPTB_Activator' ) && class_exists( 'HQP_Public' );
if ( $legacy_active ) {
    echo "<p>✅ Legacy Modules (WPTB & Hotel) loaded successfully via App Bootstrap.</p>";
} else {
    echo "<p>❌ Legacy Modules failed to load.</p>";
}
