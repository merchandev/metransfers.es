<?php

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['mt_test_options'] = array();
$GLOBALS['mt_test_http'] = null;
$GLOBALS['mt_test_urls'] = array();

function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function remove_accents( $value ) { return $value; }
function mt_lang() { return 'es'; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function apply_filters( $hook, $value ) { return $value; }
function get_transient() { return false; }
function set_transient() { return true; }
ini_set( 'error_log', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null' );
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function get_option( $key, $default = false ) {
    return array_key_exists( $key, $GLOBALS['mt_test_options'] ) ? $GLOBALS['mt_test_options'][ $key ] : $default;
}
function update_option( $key, $value ) {
    $GLOBALS['mt_test_options'][ $key ] = $value;
    return true;
}
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function wp_remote_get( $url ) {
    $GLOBALS['mt_test_urls'][] = $url;
    return $GLOBALS['mt_test_http'];
}
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }

class WP_Error {
    public function get_error_message() { return 'cURL error 28: Operation timed out'; }
}

require_once __DIR__ . '/../app/Core/Settings.php';
require_once __DIR__ . '/../app/Booking/I18n.php';
require_once __DIR__ . '/../app/Booking/MapsProvider.php';
require_once __DIR__ . '/../app/Booking/ServiceAreaPolicy.php';
require_once __DIR__ . '/../app/Booking/RouteDistance.php';

use MeTransfers\Booking\MapsProvider;

function assert_maps( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function google_replies( array $payload, $code = 200 ) {
    $GLOBALS['mt_test_http'] = array( 'code' => $code, 'body' => json_encode( $payload ) );
}

// 1. No server key: fail closed, record the outage, explain where to set it.
$missing = MapsProvider::request( MapsProvider::GEOCODING, array( 'address' => 'Barcelona' ) );
assert_maps( ! $missing['ok'] && $missing['outage'] && 'key_missing' === $missing['status'], 'A missing server key must be an outage.' );
assert_maps( array() === $GLOBALS['mt_test_urls'], 'No provider request may be sent without a server key.' );
$failures = MapsProvider::failures();
assert_maps( 'key_missing' === $failures['geocoding']['status'], 'A missing key must be recorded for wp-admin.' );
assert_maps( false !== strpos( MapsProvider::hint( 'key_missing', '' ), 'MT_GOOGLE_MAPS_SERVER_API_KEY' ), 'The hint must say where the key is configured.' );
assert_maps( 'none' === \MeTransfers\Core\Settings::source( 'google_maps_server_api_key' )['type'], 'No key source must be reported.' );

// 2. Referrer-restricted key: Google's REQUEST_DENIED text is kept (redacted) and explained.
$GLOBALS['mt_test_options']['wptb_google_maps_server_api_key'] = 'AIzaSyTESTKEY0123456789abcdefghijklmnop';
google_replies( array(
    'status'        => 'REQUEST_DENIED',
    'error_message' => 'API keys with referer restrictions cannot be used with this API. key=AIzaSyTESTKEY0123456789abcdefghijklmnop',
    'results'       => array(),
) );
$denied = \MeTransfers\Booking\ServiceAreaPolicy::validateRoute( 'Aeropuerto BCN', 'H10 Casanova' );
assert_maps( empty( $denied['valid'] ), 'A denied geocoding request must keep failing closed.' );
$failures = MapsProvider::failures();
assert_maps( 'REQUEST_DENIED' === $failures['geocoding']['status'], 'The provider status must be recorded.' );
assert_maps( false === strpos( $failures['geocoding']['detail'], 'AIza' ), 'The stored provider detail must never contain the key.' );
assert_maps( false !== strpos( MapsProvider::hint( 'REQUEST_DENIED', $failures['geocoding']['detail'] ), 'IP' ), 'A referrer-restricted key must be explained as needing an IP restriction.' );
assert_maps( 'option' === \MeTransfers\Core\Settings::source( 'google_maps_server_api_key' )['type'], 'An option-backed key must be reported as such.' );
foreach ( $GLOBALS['mt_test_urls'] as $url ) {
    assert_maps( false !== strpos( $url, 'maps.googleapis.com/maps/api/geocode/json' ), 'Geocoding must call the Geocoding endpoint.' );
}

// Other known REQUEST_DENIED causes map to distinct, actionable hints.
$ip_hint = MapsProvider::hint( 'REQUEST_DENIED', 'This IP, site or mobile application is not authorized to use this API key. Request received from IP address 35.1.2.3, with empty referer' );
$api_hint = MapsProvider::hint( 'REQUEST_DENIED', 'This API project is not authorized to use this API.' );
$legacy_hint = MapsProvider::hint( 'REQUEST_DENIED', "You're calling a legacy API, which is not enabled for your project." );
$billing_hint = MapsProvider::hint( 'REQUEST_DENIED', 'You must enable Billing on the Google Cloud Project.' );
assert_maps( count( array_unique( array( $ip_hint, $api_hint, $legacy_hint, $billing_hint ) ) ) === 4, 'IP, disabled API, legacy API and billing failures need different hints.' );
assert_maps( false !== strpos( $api_hint, 'Distance Matrix API' ), 'The disabled-API hint must name both server APIs.' );

// 3. An unknown address is not an outage and clears the recorded failure.
google_replies( array( 'status' => 'ZERO_RESULTS', 'results' => array() ) );
$unknown = MapsProvider::request( MapsProvider::GEOCODING, array( 'address' => 'Nowhere 123' ) );
assert_maps( ! $unknown['ok'] && ! $unknown['outage'], 'ZERO_RESULTS must not be reported as an outage.' );
assert_maps( ! isset( MapsProvider::failures()['geocoding'] ), 'A healthy provider answer must clear the outage notice.' );

// 4. Distance Matrix element status is honoured; transport errors are outages.
google_replies( array( 'status' => 'OK', 'rows' => array( array( 'elements' => array( array( 'status' => 'NOT_FOUND' ) ) ) ) ) );
$route = MapsProvider::request( MapsProvider::DISTANCE_MATRIX, array( 'origins' => 'A', 'destinations' => 'B' ) );
assert_maps( ! $route['ok'] && ! $route['outage'] && 'NOT_FOUND' === $route['status'], 'An unroutable pair is an address problem, not an outage.' );

$GLOBALS['mt_test_http'] = new WP_Error();
$offline = \MeTransfers\Booking\RouteDistance::calculate( 'Barcelona', 'Sitges' );
assert_maps( ! empty( $offline['error'] ), 'A transport failure must stop the route calculation.' );
assert_maps( 'transport_error' === MapsProvider::failures()['distance_matrix']['status'], 'A transport failure must be recorded for wp-admin.' );

// 5. A successful answer clears the outage and returns the payload to callers.
google_replies( array(
    'status' => 'OK',
    'rows'   => array( array( 'elements' => array( array( 'status' => 'OK', 'distance' => array( 'value' => 12400 ), 'duration' => array( 'value' => 1500 ) ) ) ) ),
) );
$ok = \MeTransfers\Booking\RouteDistance::calculate( 'Barcelona Airport', 'Plaça de Catalunya' );
assert_maps( 12.4 === $ok['distance_km'] && 25 === $ok['duration_minutes'], 'A healthy Distance Matrix answer must still produce the route.' );
assert_maps( array() === MapsProvider::failures(), 'All outages must clear once both services answer.' );

echo "Maps provider tests passed.\n";
