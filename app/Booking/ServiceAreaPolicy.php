<?php
namespace MeTransfers\Booking;

use MeTransfers\Core\Settings;

final class ServiceAreaPolicy {
    private const DEFAULT_ALLOWED_COUNTRIES = array(
        'ES', 'PT', 'FR', 'CH', 'BE', 'DE', 'IT', 'NL', 'AT', 'HR', 'SI', 'PL', 'LU', 'AD',
    );

    public static function validateRoute( $origin, $destination ) {
        $origin_result = self::geocode( $origin );
        if ( empty( $origin_result['valid'] ) ) {
            return array( 'valid' => false, 'error' => I18n::text( 'origin_policy_error' ) );
        }

        $destination_result = self::geocode( $destination );
        if ( empty( $destination_result['valid'] ) ) {
            return array( 'valid' => false, 'error' => I18n::text( 'destination_policy_error' ) );
        }

        $allowed_countries = (array) apply_filters(
            'mt_service_area_allowed_countries',
            self::DEFAULT_ALLOWED_COUNTRIES
        );
        $allowed_countries = array_map( 'strtoupper', $allowed_countries );

        $origin_is_hub = self::isServiceArea( $origin_result );
        $destination_is_hub = self::isServiceArea( $destination_result );
        $origin_is_allowed = in_array( $origin_result['country_code'], $allowed_countries, true );
        $destination_is_allowed = in_array( $destination_result['country_code'], $allowed_countries, true );

        // Commercial routes may start or finish in Catalunya, but one endpoint
        // must always be inside the operating hub and the other in coverage.
        $valid = ( $origin_is_hub && $destination_is_allowed )
            || ( $destination_is_hub && $origin_is_allowed );

        if ( ! $valid ) {
            return array(
                'valid' => false,
                'error' => I18n::text( 'route_outside_service_area' ),
            );
        }

        return array(
            'valid'       => true,
            'origin'      => $origin_result,
            'destination' => $destination_result,
        );
    }

    public static function validateOrigin( $origin ) {
        $result = self::geocode( $origin );
        return ! empty( $result['valid'] ) && self::isServiceArea( $result );
    }

    public static function validateDestination( $destination ) {
        $result = self::geocode( $destination );
        if ( empty( $result['valid'] ) ) {
            return false;
        }

        $allowed = (array) apply_filters(
            'mt_service_area_allowed_countries',
            self::DEFAULT_ALLOWED_COUNTRIES
        );
        return in_array( $result['country_code'], array_map( 'strtoupper', $allowed ), true );
    }

    private static function geocode( $address ) {
        $address = sanitize_text_field( (string) $address );
        if ( '' === $address ) {
            return array( 'valid' => false );
        }

        $filtered = apply_filters( 'mt_service_area_geocode', null, $address );
        if ( is_array( $filtered ) ) {
            return self::normalize( $filtered );
        }

        $cache_key = 'mt_geocode_' . hash( 'sha256', strtolower( $address ) );
        $cached = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            return self::normalize( $cached );
        }

        try {
            $api_key = Settings::requireServerMapsKey();
        } catch ( \RuntimeException $exception ) {
            return array( 'valid' => false );
        }

        $url = add_query_arg(
            array(
                'address'  => $address,
                'key'      => $api_key,
                'language' => 'en',
            ),
            'https://maps.googleapis.com/maps/api/geocode/json'
        );
        $response = wp_remote_get( $url, array( 'timeout' => 8, 'headers' => array( 'Referer' => home_url( '/' ) ) ) );
        if ( is_wp_error( $response ) ) {
            return array( 'valid' => false );
        }

        $payload = json_decode( wp_remote_retrieve_body( $response ), true );
        $first = isset( $payload['results'][0] ) && is_array( $payload['results'][0] )
            ? $payload['results'][0]
            : null;
        if ( ! $first ) {
            return array( 'valid' => false );
        }

        $result = array(
            'valid'              => true,
            'country_code'       => '',
            'administrative_1'   => '',
            'administrative_2'   => '',
            'formatted_address'  => isset( $first['formatted_address'] ) ? $first['formatted_address'] : $address,
        );

        foreach ( (array) ( $first['address_components'] ?? array() ) as $component ) {
            $types = isset( $component['types'] ) ? (array) $component['types'] : array();
            if ( in_array( 'country', $types, true ) ) {
                $result['country_code'] = strtoupper( (string) ( $component['short_name'] ?? '' ) );
            } elseif ( in_array( 'administrative_area_level_1', $types, true ) ) {
                $result['administrative_1'] = (string) ( $component['long_name'] ?? $component['short_name'] ?? '' );
            } elseif ( in_array( 'administrative_area_level_2', $types, true ) ) {
                $result['administrative_2'] = (string) ( $component['long_name'] ?? $component['short_name'] ?? '' );
            }
        }

        $result = self::normalize( $result );
        if ( ! empty( $result['valid'] ) ) {
            set_transient( $cache_key, $result, 7 * DAY_IN_SECONDS );
        }
        return $result;
    }

    private static function normalize( $result ) {
        $country = strtoupper( sanitize_text_field( (string) ( $result['country_code'] ?? '' ) ) );
        return array(
            'valid'             => ! empty( $result['valid'] ) && 2 === strlen( $country ),
            'country_code'      => $country,
            'administrative_1'  => sanitize_text_field( (string) ( $result['administrative_1'] ?? '' ) ),
            'administrative_2'  => sanitize_text_field( (string) ( $result['administrative_2'] ?? '' ) ),
            'formatted_address' => sanitize_text_field( (string) ( $result['formatted_address'] ?? '' ) ),
        );
    }

    private static function isServiceArea( $geocode ) {
        if ( 'ES' !== ( $geocode['country_code'] ?? '' ) ) {
            return false;
        }

        $area = self::normalizeText(
            (string) ( $geocode['administrative_1'] ?? '' ) . ' '
            . (string) ( $geocode['administrative_2'] ?? '' )
        );
        return false !== strpos( $area, 'catalunya' )
            || false !== strpos( $area, 'catalonia' )
            || false !== strpos( $area, 'cataluna' )
            || false !== strpos( $area, 'barcelona' );
    }

    private static function normalizeText( $text ) {
        $text = function_exists( 'remove_accents' ) ? remove_accents( $text ) : $text;
        return strtolower( (string) $text );
    }
}


