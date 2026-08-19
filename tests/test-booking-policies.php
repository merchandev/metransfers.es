<?php

function sanitize_text_field( $value ) { return trim( (string) $value ); }
function absint( $value ) { return abs( (int) $value ); }
function remove_accents( $value ) { return str_replace( array( 'ñ', 'á' ), array( 'n', 'a' ), $value ); }
function mt_lang() { return 'es'; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function get_option( $key, $default = false ) { return $default; }
function get_transient() { return false; }
function set_transient() { return true; }

function apply_filters( $hook, $value, ...$args ) {
    if ( 'mt_service_area_geocode' === $hook ) {
        $places = array(
            'Barcelona' => array( 'valid' => true, 'country_code' => 'ES', 'administrative_1' => 'Catalunya', 'administrative_2' => 'Barcelona' ),
            'Girona'    => array( 'valid' => true, 'country_code' => 'ES', 'administrative_1' => 'Catalunya', 'administrative_2' => 'Girona' ),
            'Paris'     => array( 'valid' => true, 'country_code' => 'FR', 'administrative_1' => 'Île-de-France' ),
            'Lisbon'    => array( 'valid' => true, 'country_code' => 'PT', 'administrative_1' => 'Lisboa' ),
            'Madrid'    => array( 'valid' => true, 'country_code' => 'ES', 'administrative_1' => 'Comunidad de Madrid' ),
            'Tangier'   => array( 'valid' => true, 'country_code' => 'MA', 'administrative_1' => 'Tanger' ),
        );
        return $places[ $args[0] ] ?? array( 'valid' => false );
    }
    if ( 'mt_server_route_distance' === $hook ) {
        $routes = array(
            'Barcelona|Paris' => array( 'distance_km' => 100, 'duration_minutes' => 120 ),
            'Paris|Barcelona' => array( 'distance_km' => 120, 'duration_minutes' => 120 ),
        );
        return $routes[ $args[0] . '|' . $args[1] ] ?? $value;
    }
    if ( 'mt_min_booking_lead_minutes' === $hook ) {
        return 120;
    }
    return $value;
}

class WPTB_Vehicle_Manager {
    public static function get_vehicle( $id ) {
        if ( 7 !== (int) $id ) return null;
        return (object) array(
            'id' => 7, 'name' => 'Van', 'type_name' => 'Premium', 'initial_fee' => 10,
            'min_transfer_price' => 50, 'min_oneway_price' => 40, 'min_roundtrip_price' => 100,
            'price_per_km_oneway' => 2, 'price_per_km_roundtrip' => 1.5, 'price_per_hour' => 60,
        );
    }
}

require_once __DIR__ . '/../app/Core/Settings.php';
require_once __DIR__ . '/../app/Booking/I18n.php';
require_once __DIR__ . '/../app/Booking/ServiceAreaPolicy.php';
require_once __DIR__ . '/../app/Booking/BookingDatePolicy.php';
require_once __DIR__ . '/../app/Booking/RouteDistance.php';
require_once __DIR__ . '/../app/Pricing/Calculator.php';
require_once __DIR__ . '/../app/Booking/QuoteService.php';

function assert_policy( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

assert_policy( \MeTransfers\Booking\ServiceAreaPolicy::validateRoute( 'Barcelona', 'Paris' )['valid'], 'A covered route from Catalonia must be accepted.' );
assert_policy( \MeTransfers\Booking\ServiceAreaPolicy::validateRoute( 'Paris', 'Girona' )['valid'], 'A covered route to Catalonia must be accepted.' );
assert_policy( ! \MeTransfers\Booking\ServiceAreaPolicy::validateRoute( 'Madrid', 'Lisbon' )['valid'], 'One endpoint must be in the operating hub.' );
assert_policy( ! \MeTransfers\Booking\ServiceAreaPolicy::validateRoute( 'Barcelona', 'Tangier' )['valid'], 'Unsupported destination countries must be rejected.' );

$now = new DateTimeImmutable( '2026-08-19 10:00:00', new DateTimeZone( 'Europe/Madrid' ) );
assert_policy( \MeTransfers\Booking\BookingDatePolicy::validate( '2026-08-19', '12:01', '', '', $now )['valid'], 'A booking beyond the lead time must pass.' );
assert_policy( ! \MeTransfers\Booking\BookingDatePolicy::validate( '2026-08-19', '11:59', '', '', $now )['valid'], 'Lead time must be enforced.' );
assert_policy( ! \MeTransfers\Booking\BookingDatePolicy::validate( '2026-08-20', '10:00', '2026-08-20', '09:59', $now )['valid'], 'Return must be later than outbound.' );

$quote = \MeTransfers\Booking\QuoteService::create( array(
    'language' => 'en', 'date' => '2099-08-20', 'time' => '10:00',
    'origin' => 'Barcelona', 'destination' => 'Paris', 'vehicle_id' => 7,
    'trip_type' => 'round_trip', 'return_date' => '2099-08-21', 'return_time' => '10:00',
    'return_origin' => 'Paris', 'return_destination' => 'Barcelona',
) );
assert_policy( ! empty( $quote['valid'] ), 'The authoritative round-trip quote must succeed.' );
assert_policy( 340.0 === $quote['price'], 'The quote must use both real legs, initial fee and server pricing.' );
assert_policy( 220.0 === $quote['total_distance_km'], 'The quote must retain total route distance.' );
assert_policy( 'en' === $quote['booking_locale'], 'The normalized booking locale must accompany the quote.' );

echo "Booking policy tests passed.\n";
