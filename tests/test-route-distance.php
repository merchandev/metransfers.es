<?php

function sanitize_text_field( $value ) {
    return trim( (string) $value );
}

function absint( $value ) {
    return abs( (int) $value );
}

function apply_filters( $hook, $value, $origin, $destination ) {
    if ( 'mt_server_route_distance' === $hook && 'Barcelona' === $origin && 'Sitges' === $destination ) {
        return array( 'distance_km' => 41.237, 'duration_minutes' => 52 );
    }
    return $value;
}

function get_option( $key, $default = false ) {
    return $default;
}

require_once __DIR__ . '/../app/Core/Settings.php';
require_once __DIR__ . '/../app/Booking/RouteDistance.php';

$route = \MeTransfers\Booking\RouteDistance::calculate( ' Barcelona ', 'Sitges' );
if ( 41.24 !== $route['distance_km'] || 52 !== $route['duration_minutes'] ) {
    fwrite( STDERR, "FAILED: filtered server route was not normalized correctly.\n" );
    exit( 1 );
}

$invalid = \MeTransfers\Booking\RouteDistance::calculate( '', 'Sitges' );
if ( empty( $invalid['error'] ) ) {
    fwrite( STDERR, "FAILED: invalid route input must return an error.\n" );
    exit( 1 );
}

echo "Route distance tests passed.\n";
