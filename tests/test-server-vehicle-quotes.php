<?php

$GLOBALS['mt_vehicle_quote_route_calls'] = 0;

function assert_vehicle_quote( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function sanitize_text_field( $value ) { return trim( (string) $value ); }
function wp_kses_post( $value ) { return strip_tags( (string) $value, '<p><strong><em>' ); }
function absint( $value ) { return abs( (int) $value ); }
function remove_accents( $value ) { return $value; }
function mt_lang() { return 'es'; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function get_option( $key, $default = false ) { return $default; }
function get_transient() { return false; }
function set_transient() { return true; }

function apply_filters( $hook, $value, ...$args ) {
    if ( 'mt_service_area_geocode' === $hook ) {
        $places = array(
            'Barcelona' => array( 'valid' => true, 'country_code' => 'ES', 'administrative_1' => 'Catalunya', 'administrative_2' => 'Barcelona' ),
            'Paris'     => array( 'valid' => true, 'country_code' => 'FR', 'administrative_1' => 'Ile-de-France' ),
        );
        return isset( $places[ $args[0] ] ) ? $places[ $args[0] ] : array( 'valid' => false );
    }
    if ( 'mt_server_route_distance' === $hook ) {
        $GLOBALS['mt_vehicle_quote_route_calls']++;
        return array( 'distance_km' => 100, 'duration_minutes' => 120 );
    }
    if ( 'mt_min_booking_lead_minutes' === $hook ) {
        return 0;
    }
    return $value;
}

class WPTB_Vehicle_Manager {
    public static $vehicles = array();

    public static function get_active_vehicles() {
        return self::$vehicles;
    }

    public static function get_vehicle( $id ) {
        foreach ( self::$vehicles as $vehicle ) {
            if ( (int) $vehicle->id === (int) $id ) {
                return $vehicle;
            }
        }
        return null;
    }

    public static function get_primary_image( $id ) {
        return 'https://example.test/vehicle-' . (int) $id . '.jpg';
    }
}

WPTB_Vehicle_Manager::$vehicles = array(
    (object) array(
        'id' => 7, 'name' => 'Sedan', 'type_name' => 'Standard', 'description' => '',
        'capacity' => 4, 'luggage_capacity' => 3, 'initial_fee' => 10,
        'min_transfer_price' => 50, 'min_oneway_price' => 40, 'min_roundtrip_price' => 100,
        'price_per_km_oneway' => 2, 'price_per_km_roundtrip' => 1.5, 'price_per_hour' => 60,
    ),
    (object) array(
        'id' => 8, 'name' => 'Van', 'type_name' => 'Premium', 'description' => '',
        'capacity' => 8, 'luggage_capacity' => 8, 'initial_fee' => 0,
        'min_transfer_price' => 100, 'min_oneway_price' => 100, 'min_roundtrip_price' => 180,
        'price_per_km_oneway' => 3, 'price_per_km_roundtrip' => 2, 'price_per_hour' => 0,
    ),
);

require_once __DIR__ . '/../app/Core/Settings.php';
require_once __DIR__ . '/../app/Booking/I18n.php';
require_once __DIR__ . '/../app/Booking/ServiceAreaPolicy.php';
require_once __DIR__ . '/../app/Booking/BookingDatePolicy.php';
require_once __DIR__ . '/../app/Booking/RouteDistance.php';
require_once __DIR__ . '/../app/Booking/RouteContext.php';
require_once __DIR__ . '/../app/Booking/VehicleCapacityPolicy.php';
require_once __DIR__ . '/../app/Pricing/Money.php';
require_once __DIR__ . '/../app/Pricing/Calculator.php';
require_once __DIR__ . '/../app/Booking/QuoteService.php';

$input = array(
    'language' => 'es',
    'date' => '2099-09-01',
    'time' => '10:00',
    'origin' => 'Barcelona',
    'destination' => 'Paris',
    'trip_type' => 'one_way',
);
$list = \MeTransfers\Booking\QuoteService::createVehicleList( $input );
assert_vehicle_quote( ! empty( $list['valid'] ) && 2 === count( $list['vehicles'] ), 'All active vehicles must receive a server quote.' );
assert_vehicle_quote( 1 === $GLOBALS['mt_vehicle_quote_route_calls'], 'All vehicle prices must share one resolved route.' );
assert_vehicle_quote( 210.0 === $list['vehicles'][0]['price'], 'The server must price the first vehicle.' );
assert_vehicle_quote( 21000 === $list['vehicles'][0]['price_cents'], 'Vehicle quotes must expose exact integer cents.' );
assert_vehicle_quote( 300.0 === $list['vehicles'][1]['price'], 'The server must price the second vehicle.' );
assert_vehicle_quote( 100.0 === $list['route']['total_distance_km'], 'The response must carry the server route context.' );

$public_json = json_encode( $list );
foreach ( array( 'pricing', 'price_per_km', 'min_oneway', 'min_roundtrip', 'min_transfer', 'price_per_hour' ) as $private_coefficient ) {
    assert_vehicle_quote( false === strpos( $public_json, $private_coefficient ), "Public vehicle quotes must omit $private_coefficient." );
}

$selected = \MeTransfers\Booking\QuoteService::create( $input + array( 'vehicle_id' => 7 ) );
assert_vehicle_quote( $list['vehicles'][0]['price'] === $selected['price'], 'The UI vehicle price must equal the payment quote for the same route.' );

$capacity_ok = \MeTransfers\Booking\VehicleCapacityPolicy::validate( WPTB_Vehicle_Manager::$vehicles[0], 4, 2, 1 );
$too_many_people = \MeTransfers\Booking\VehicleCapacityPolicy::validate( WPTB_Vehicle_Manager::$vehicles[0], 5, 0, 0 );
$too_much_luggage = \MeTransfers\Booking\VehicleCapacityPolicy::validate( WPTB_Vehicle_Manager::$vehicles[0], 2, 3, 1 );
assert_vehicle_quote( ! empty( $capacity_ok['valid'] ), 'Passenger and combined luggage limits must accept a valid request.' );
assert_vehicle_quote( empty( $too_many_people['valid'] ), 'Passenger capacity must use the shared policy.' );
assert_vehicle_quote( empty( $too_much_luggage['valid'] ), 'Suitcases and carry-ons must share the configured luggage limit.' );

$root = dirname( __DIR__ );
$public = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-public.php' );
$booking_js = file_get_contents( $root . '/app/Legacy/WPTB/assets/js/booking-app.js' );
$search_js = file_get_contents( $root . '/app/Legacy/WPTB/assets/js/transfers-search.js' );
foreach ( array( $booking_js, $search_js ) as $browser_source ) {
    assert_vehicle_quote( false === strpos( $browser_source, 'price_per_km' ), 'The browser must not contain tariff formulas.' );
    assert_vehicle_quote( false === strpos( $browser_source, 'vehicle.pricing' ), 'The browser must consume the server price directly.' );
}
assert_vehicle_quote( false === strpos( $public, "'pricing' => array(" ), 'The vehicle endpoint must not expose tariff coefficients.' );
assert_vehicle_quote( false !== strpos( $public, "unset( \$result['breakdown'] )" ), 'The public single-quote response must omit its tariff breakdown.' );

echo "Server vehicle quote tests passed.\n";
