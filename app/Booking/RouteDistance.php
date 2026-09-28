<?php
namespace MeTransfers\Booking;

class RouteDistance {
    public static function calculate( $origin, $destination ) {
        $origin = sanitize_text_field( $origin );
        $destination = sanitize_text_field( $destination );
        if ( '' === $origin || '' === $destination ) {
            return array( 'error' => 'Origen o destino no válidos.' );
        }

        $filtered = apply_filters( 'mt_server_route_distance', null, $origin, $destination );
        if ( is_array( $filtered ) && ! empty( $filtered['distance_km'] ) ) {
            return self::normalize_result( $filtered );
        }

        $cache_key = 'mt_route_' . hash( 'sha256', strtolower( $origin . '|' . $destination ) );
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) && ! empty( $cached['distance_km'] ) ) {
            return self::normalize_result( $cached );
        }

        if ( ! self::consume_rate_limit() ) {
            return array( 'error' => 'Demasiadas consultas de ruta. Inténtalo de nuevo en un minuto.' );
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
        if ( 'key_missing' === $response['status'] ) {
            return array( 'error' => 'El cálculo de rutas del servidor no está configurado.' );
        }
        if ( 'transport_error' === $response['status'] ) {
            return array( 'error' => 'No se pudo consultar la ruta.' );
        }

        $element = $response['payload']['rows'][0]['elements'][0] ?? null;
        if ( ! $response['ok'] || ! is_array( $element ) ) {
            return array( 'error' => 'El proveedor no pudo calcular la ruta.' );
        }

        $meters = isset( $element['distance']['value'] ) ? (int) $element['distance']['value'] : 0;
        $seconds = isset( $element['duration']['value'] ) ? (int) $element['duration']['value'] : 0;
        if ( $meters <= 0 ) {
            return array( 'error' => 'La distancia calculada no es válida.' );
        }

        $result = array(
            'distance_km'     => round( $meters / 1000, 2 ),
            'duration_minutes' => $seconds > 0 ? (int) ceil( $seconds / 60 ) : 0,
        );
        set_transient( $cache_key, $result, 6 * HOUR_IN_SECONDS );

        return $result;
    }

    private static function normalize_result( $result ) {
        $distance_km = (float) $result['distance_km'];
        if ( $distance_km <= 0 ) {
            return array( 'error' => 'La distancia calculada no es válida.' );
        }

        return array(
            'distance_km'      => round( $distance_km, 2 ),
            'duration_minutes' => isset( $result['duration_minutes'] ) ? absint( $result['duration_minutes'] ) : 0,
        );
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

