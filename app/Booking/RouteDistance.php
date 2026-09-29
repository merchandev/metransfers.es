<?php
namespace MeTransfers\Booking;

class RouteDistance {
    public static function calculate( $origin, $destination, $language = '' ) {
        $origin = sanitize_text_field( $origin );
        $destination = sanitize_text_field( $destination );
        if ( '' === $origin || '' === $destination ) {
            return self::error( 'invalid_booking_request', $language );
        }

        $filtered = apply_filters( 'mt_server_route_distance', null, $origin, $destination );
        if ( is_array( $filtered ) && ! empty( $filtered['distance_km'] ) ) {
            return self::normalize_result( $filtered, $language );
        }

        $stored = AddressCache::route( $origin, $destination );
        if ( $stored && $stored['fresh'] ) {
            AddressCache::rememberRoute( $origin, $destination, null, true );
            return self::normalize_result( $stored, $language );
        }

        if ( ! self::consume_rate_limit() ) {
            return self::error( 'quote_rate_limited', $language );
        }

        $response = MapsProvider::request(
            MapsProvider::DISTANCE_MATRIX,
            array(
                'origins'      => $origin,
                'destinations' => $destination,
                'units'        => 'metric',
                'language'     => 'es',
            )
        );
        $element = $response['payload']['rows'][0]['elements'][0] ?? null;
        if ( ! $response['ok'] || ! is_array( $element ) ) {
            AddressCache::rememberRoute( $origin, $destination );
            // While Google is down, a measure younger than MAX_DAYS keeps quoting.
            if ( $response['outage'] && $stored ) {
                return self::normalize_result( $stored, $language );
            }
            return self::error( $response['outage'] ? 'quote_service_unavailable' : 'route_not_found', $language );
        }

        $meters = isset( $element['distance']['value'] ) ? (int) $element['distance']['value'] : 0;
        $seconds = isset( $element['duration']['value'] ) ? (int) $element['duration']['value'] : 0;
        if ( $meters <= 0 ) {
            AddressCache::rememberRoute( $origin, $destination );
            return self::error( 'route_error', $language );
        }

        AddressCache::rememberRoute( $origin, $destination, array( 'distance_meters' => $meters, 'duration_seconds' => $seconds ) );

        return array(
            'distance_km'     => round( $meters / 1000, 2 ),
            'duration_minutes' => $seconds > 0 ? (int) ceil( $seconds / 60 ) : 0,
        );
    }

    private static function normalize_result( $result, $language ) {
        $distance_km = (float) $result['distance_km'];
        if ( $distance_km <= 0 ) {
            return self::error( 'route_error', $language );
        }

        return array(
            'distance_km'      => round( $distance_km, 2 ),
            'duration_minutes' => isset( $result['duration_minutes'] ) ? absint( $result['duration_minutes'] ) : 0,
        );
    }

    private static function error( $key, $language ) {
        return array( 'code' => $key, 'error' => I18n::text( $key, $language ) );
    }

    private static function consume_rate_limit() {
        $remote_address = isset( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : 'unknown';
        $key = 'mt_route_rate_' . md5( $remote_address );
        $count = (int) get_transient( $key );
        if ( $count >= 20 ) {
            return false;
        }

        set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
        return true;
    }
}

