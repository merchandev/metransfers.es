<?php

namespace MeTransfers\Booking;

/**
 * Resolves and validates a route once so every vehicle quote shares the same
 * distance, duration and service-area decision.
 */
final class RouteContext {
    public static function create( array $input, $allow_implicit_return = false ) {
        $language = I18n::normalizeLanguage( self::scalar( $input, 'language', 'es' ) );
        $origin = sanitize_text_field( self::scalar( $input, 'origin' ) );
        $destination = sanitize_text_field( self::scalar( $input, 'destination' ) );
        $date = self::scalar( $input, 'date' );
        $time = self::scalar( $input, 'time' );
        $trip_type = 'round_trip' === self::scalar( $input, 'trip_type' ) ? 'round_trip' : 'one_way';

        if ( '' === $origin || '' === $destination ) {
            return self::error( 'invalid_booking_request', $language );
        }

        $return_date = self::scalar( $input, 'return_date' );
        $return_time = self::scalar( $input, 'return_time' );
        $return_origin = sanitize_text_field( self::scalar( $input, 'return_origin' ) );
        $return_destination = sanitize_text_field( self::scalar( $input, 'return_destination' ) );

        if ( 'round_trip' === $trip_type ) {
            $has_return_route = '' !== $return_origin && '' !== $return_destination;
            $has_return_schedule = '' !== $return_date && '' !== $return_time;
            if ( ! $allow_implicit_return && ( ! $has_return_route || ! $has_return_schedule ) ) {
                return self::error( 'return_fields_required', $language );
            }

            if ( ! $has_return_route ) {
                $return_origin = $destination;
                $return_destination = $origin;
            }
        }

        $date_policy = BookingDatePolicy::validate(
            $date,
            $time,
            'round_trip' === $trip_type && '' !== $return_date && '' !== $return_time ? $return_date : '',
            'round_trip' === $trip_type && '' !== $return_date && '' !== $return_time ? $return_time : ''
        );
        if ( empty( $date_policy['valid'] ) ) {
            return array( 'valid' => false, 'error' => $date_policy['error'] );
        }

        $area = ServiceAreaPolicy::validateRoute( $origin, $destination );
        if ( empty( $area['valid'] ) ) {
            return array( 'valid' => false, 'error' => $area['error'] );
        }

        $outbound = RouteDistance::calculate( $origin, $destination );
        if ( isset( $outbound['error'] ) ) {
            return array( 'valid' => false, 'error' => $outbound['error'] );
        }

        $pricing_distance = (float) $outbound['distance_km'];
        $total_distance = $pricing_distance;
        $duration_minutes = (int) $outbound['duration_minutes'];
        $return_route = null;

        if ( 'round_trip' === $trip_type ) {
            $return_area = ServiceAreaPolicy::validateRoute( $return_origin, $return_destination );
            if ( empty( $return_area['valid'] ) ) {
                return array( 'valid' => false, 'error' => $return_area['error'] );
            }

            $return_route = RouteDistance::calculate( $return_origin, $return_destination );
            if ( isset( $return_route['error'] ) ) {
                return array( 'valid' => false, 'error' => $return_route['error'] );
            }

            $total_distance += (float) $return_route['distance_km'];
            $pricing_distance = $total_distance / 2;
            $duration_minutes += (int) $return_route['duration_minutes'];
        }

        return array(
            'valid'                  => true,
            'language'               => $language,
            'date'                   => sanitize_text_field( $date ),
            'time'                   => sanitize_text_field( $time ),
            'origin'                 => $origin,
            'destination'            => $destination,
            'trip_type'              => $trip_type,
            'return_date'            => sanitize_text_field( $return_date ),
            'return_time'            => sanitize_text_field( $return_time ),
            'return_origin'          => $return_origin,
            'return_destination'     => $return_destination,
            'pricing_distance_km'    => round( $pricing_distance, 2 ),
            'total_distance_km'      => round( $total_distance, 2 ),
            'duration_minutes'       => $duration_minutes,
            'outbound_distance_km'   => (float) $outbound['distance_km'],
            'return_distance_km'     => $return_route ? (float) $return_route['distance_km'] : 0,
        );
    }

    private static function scalar( array $input, $key, $default = '' ) {
        return isset( $input[ $key ] ) && is_scalar( $input[ $key ] )
            ? (string) $input[ $key ]
            : (string) $default;
    }

    private static function error( $key, $language ) {
        return array( 'valid' => false, 'error' => I18n::text( $key, $language ) );
    }
}
