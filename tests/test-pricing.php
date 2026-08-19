<?php

function absint( $value ) {
    return abs( (int) $value );
}

function apply_filters( $hook, $value ) {
    return $value;
}

class WPTB_Vehicle_Manager {
    public static $vehicle;

    public static function get_vehicle( $vehicle_id ) {
        return 7 === (int) $vehicle_id ? self::$vehicle : null;
    }
}

function assert_price( $expected, $actual, $message ) {
    if ( abs( $expected - $actual ) > 0.001 ) {
        fwrite( STDERR, "FAILED: $message. Expected $expected, got $actual.\n" );
        exit( 1 );
    }
}

WPTB_Vehicle_Manager::$vehicle = (object) array(
    'id'                       => 7,
    'name'                     => 'Sedán',
    'type_name'                => 'Standard',
    'initial_fee'              => 10,
    'min_transfer_price'       => 50,
    'min_oneway_price'         => 40,
    'min_roundtrip_price'      => 100,
    'price_per_km_oneway'      => 2,
    'price_per_km_roundtrip'   => 1.5,
    'price_per_hour'           => 60,
);

require_once __DIR__ . '/../app/Pricing/Calculator.php';

$invalid = \MeTransfers\Pricing\Calculator::calculate( 0, 10 );
if ( empty( $invalid['error'] ) ) {
    fwrite( STDERR, "FAILED: invalid input must return an error.\n" );
    exit( 1 );
}

$missing = \MeTransfers\Pricing\Calculator::calculate( 99, 10 );
if ( empty( $missing['error'] ) ) {
    fwrite( STDERR, "FAILED: missing vehicle must return an error.\n" );
    exit( 1 );
}

assert_price( 50, \MeTransfers\Pricing\Calculator::calculate( 7, 10 )['price'], 'general minimum' );
assert_price( 70, \MeTransfers\Pricing\Calculator::calculate( 7, 30 )['price'], 'one-way distance price' );
assert_price( 100, \MeTransfers\Pricing\Calculator::calculate( 7, 30, 'round_trip' )['price'], 'round-trip distance and minimum' );
assert_price( 120, \MeTransfers\Pricing\Calculator::calculate( 7, 30, 'one_way', 120 )['price'], 'hourly price override' );
assert_price( 71, \MeTransfers\Pricing\Calculator::calculate( 7, '30,5' )['price'], 'decimal comma normalization' );

echo "Pricing tests passed.\n";
