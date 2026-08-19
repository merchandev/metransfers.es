<?php

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['mt_test_options'] = array(
    'wptb_google_maps_api_key' => 'browser-only-key',
);
$GLOBALS['mt_test_transients'] = array();

function assert_phase_one( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function get_option( $key, $default = false ) {
    return array_key_exists( $key, $GLOBALS['mt_test_options'] )
        ? $GLOBALS['mt_test_options'][ $key ]
        : $default;
}

function get_transient( $key ) {
    return isset( $GLOBALS['mt_test_transients'][ $key ] )
        ? $GLOBALS['mt_test_transients'][ $key ]
        : false;
}

function set_transient( $key, $value, $expiration ) {
    $GLOBALS['mt_test_transients'][ $key ] = $value;
    return true;
}

function sanitize_key( $value ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function sanitize_text_field( $value ) {
    return trim( (string) $value );
}

function wp_unslash( $value ) {
    return $value;
}

function is_user_logged_in() {
    return false;
}

require_once __DIR__ . '/../app/Core/Settings.php';
require_once __DIR__ . '/../app/Security/RequestRateLimiter.php';
require_once __DIR__ . '/../app/Security/PathGuard.php';

$missing_server_key = false;
try {
    \MeTransfers\Core\Settings::requireServerMapsKey();
} catch ( RuntimeException $exception ) {
    $missing_server_key = true;
}
assert_phase_one( $missing_server_key, 'A browser Maps key must never satisfy server-side configuration.' );

$GLOBALS['mt_test_options']['wptb_google_maps_server_api_key'] = 'server-key';
assert_phase_one(
    'server-key' === \MeTransfers\Core\Settings::requireServerMapsKey(),
    'The dedicated server Maps key must be returned when configured.'
);

$_SERVER['REMOTE_ADDR'] = '192.0.2.10';
assert_phase_one(
    \MeTransfers\Security\RequestRateLimiter::consume( 'booking_quote', 2, MINUTE_IN_SECONDS ),
    'The first quote request must be allowed.'
);
assert_phase_one(
    \MeTransfers\Security\RequestRateLimiter::consume( 'booking_quote', 2, MINUTE_IN_SECONDS ),
    'The second quote request must be allowed.'
);
assert_phase_one(
    ! \MeTransfers\Security\RequestRateLimiter::consume( 'booking_quote', 2, MINUTE_IN_SECONDS ),
    'Requests above the quote limit must be rejected.'
);

$_SERVER['REMOTE_ADDR'] = '192.0.2.11';
assert_phase_one(
    \MeTransfers\Security\RequestRateLimiter::consume( 'booking_quote', 2, MINUTE_IN_SECONDS ),
    'Rate-limit counters must be isolated by client identifier.'
);

$root = dirname( __DIR__ );
assert_phase_one(
    \MeTransfers\Security\PathGuard::containsFile( $root . '/app', $root . '/app/Core/Application.php' ),
    'A regular file inside the approved root must pass containment.'
);
assert_phase_one(
    ! \MeTransfers\Security\PathGuard::containsFile( $root . '/app', $root . '/README.md' ),
    'A sibling file outside the approved root must fail containment.'
);

$service_area = file_get_contents( $root . '/app/Booking/ServiceAreaPolicy.php' );
$route_distance = file_get_contents( $root . '/app/Booking/RouteDistance.php' );
$loader = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-loader.php' );
$public_controller = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-public.php' );
$booking_js = file_get_contents( $root . '/app/Legacy/WPTB/assets/js/booking-app.js' );
$booking_details = file_get_contents( $root . '/app/Legacy/WPTB/templates/booking-details.php' );
$admin_controller = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-admin.php' );

assert_phase_one(
    false === strpos( $service_area, "Settings::get( 'google_maps_api_key'" )
        && false === strpos( $route_distance, "Settings::get( 'google_maps_api_key'" ),
    'Server-side Maps services must not fall back to the browser key.'
);
assert_phase_one(
    false === strpos( $loader, 'wptb_calculate_price' )
        && false === strpos( $public_controller, 'function ajax_calculate_price' ),
    'The legacy public pricing endpoint must be retired.'
);
assert_phase_one(
    false === strpos( $booking_js, 'distance_km = 50' )
        && false === strpos( $booking_js, 'safe default' ),
    'The browser must not invent a 50 km route after provider failure.'
);
assert_phase_one(
    false !== strpos( $public_controller, 'RequestRateLimiter::consume' )
        && false !== strpos( $public_controller, "'quote_rate_limited'" ),
    'Quote entry points must enforce the global request limiter.'
);
assert_phase_one(
    false !== strpos( $booking_details, 'validate_confirmation_request' ),
    'Both OK and KO return views must use the shared HMAC request validator.'
);
assert_phase_one(
    false !== strpos( $admin_controller, 'PathGuard::containsFile' )
        && false !== strpos( $admin_controller, 'wp_delete_file( $backup_path )' ),
    'Backup deletion must resolve containment before deleting a file.'
);

echo "Phase 1 hardening tests passed.\n";
