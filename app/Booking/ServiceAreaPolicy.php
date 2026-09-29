<?php
namespace MeTransfers\Booking;

final class ServiceAreaPolicy {
    private const DEFAULT_ALLOWED_COUNTRIES = array(
        'ES', 'PT', 'FR', 'CH', 'BE', 'DE', 'IT', 'NL', 'AT', 'HR', 'SI', 'PL', 'LU', 'AD',
    );

    // Round trips resolve the same two addresses twice in one request.
    private static $resolved = array();

    public static function validateRoute( $origin, $destination, $language = '' ) {
        $origin_result = self::geocode( $origin );
        if ( empty( $origin_result['valid'] ) ) {
            return self::geocodeFailure( $origin_result, 'origin_policy_error', $language );
        }

        $destination_result = self::geocode( $destination );
        if ( empty( $destination_result['valid'] ) ) {
            return self::geocodeFailure( $destination_result, 'destination_policy_error', $language );
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
                'code'  => 'route_outside_service_area',
                'error' => I18n::text( 'route_outside_service_area', $language ),
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

        $key = AddressCache::key( $address );
        if ( isset( self::$resolved[ $key ] ) ) {
            return self::$resolved[ $key ];
        }

        $result = self::lookup( $address );
        if ( ! empty( $result['valid'] ) ) {
            self::$resolved[ $key ] = $result;
        }
        return $result;
    }

    private static function lookup( $address ) {
        $stored = AddressCache::address( $address );
        if ( $stored && $stored['fresh'] ) {
            AddressCache::rememberAddress( $address, null, true );
            return self::normalize( $stored );
        }

        // Provider/configuration failures are logged and surfaced in wp-admin by
        // MapsProvider; only the address-level outcome is logged here.
        $response = MapsProvider::request( MapsProvider::GEOCODING, array( 'address' => $address, 'language' => 'en' ) );
        $first = isset( $response['payload']['results'][0] ) && is_array( $response['payload']['results'][0] )
            ? $response['payload']['results'][0]
            : null;
        if ( ! $response['ok'] || ! $first ) {
            AddressCache::rememberAddress( $address );
            // While Google is down, an answer younger than MAX_DAYS keeps quoting.
            if ( $response['outage'] && $stored ) {
                return self::normalize( $stored );
            }
            if ( ! $response['outage'] ) {
                error_log( 'MeTransfers ServiceAreaPolicy: Google could not geocode "' . $address . '" (status=' . $response['status'] . ').' );
            }
            return array( 'valid' => false, 'outage' => $response['outage'] );
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
        AddressCache::rememberAddress(
            $address,
            empty( $result['valid'] ) ? null : $result + array(
                'place_id' => (string) ( $first['place_id'] ?? '' ),
                'lat'      => $first['geometry']['location']['lat'] ?? null,
                'lng'      => $first['geometry']['location']['lng'] ?? null,
            )
        );
        return $result;
    }

    // A provider outage is our failure, not the visitor's address: telling
    // them the origin "could not be verified" sends them to retype it forever.
    private static function geocodeFailure( array $geocode, $key, $language ) {
        $key = ! empty( $geocode['outage'] ) ? 'quote_service_unavailable' : $key;
        return array( 'valid' => false, 'code' => $key, 'error' => I18n::text( $key, $language ) );
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


