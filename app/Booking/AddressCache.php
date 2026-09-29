<?php
namespace MeTransfers\Booking;

/**
 * Database record of the origins, destinations and routes visitors quote.
 * Repeated addresses (airport, port, stations, hotels) are answered from here
 * instead of calling Google again, and known routes keep quoting through a
 * Google outage.
 *
 * Google Maps Platform terms only allow caching coordinates for 30 days and
 * place IDs indefinitely. Everything Google returned is therefore refreshed
 * after REFRESH_DAYS, reused during an outage until MAX_DAYS, and deleted by
 * the daily purge after MAX_DAYS. The address the visitor submitted, the place
 * ID and the usage counters stay for RETENTION_DAYS so demand can be queried.
 */
final class AddressCache {
    public const REFRESH_DAYS   = 7;
    public const MAX_DAYS       = 30;
    public const RETENTION_DAYS = 395;
    public const CRON_HOOK      = 'mt_purge_address_cache';

    private const TEXT_LIMIT = 500;

    public function register() {
        add_action( self::CRON_HOOK, array( __CLASS__, 'purge' ) );
        if ( function_exists( 'wp_next_scheduled' )
            && function_exists( 'wp_schedule_event' )
            && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    /**
     * Case and whitespace variants of the same address share one row.
     */
    public static function key( $text ) {
        $text = trim( (string) preg_replace( '/\s+/u', ' ', (string) $text ) );
        $text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
        return hash( 'sha256', $text );
    }

    /**
     * @return array{valid: true, country_code: string, administrative_1: string, administrative_2: string, formatted_address: string, fresh: bool}|null
     *         Null when Google data for this address is missing or older than MAX_DAYS.
     */
    public static function address( $text ) {
        $row = self::row(
            'SELECT formatted_address, country_code, administrative_1, administrative_2, geocoded_at FROM %i WHERE address_hash = %s AND geocoded_at >= %s',
            self::table( 'addresses' ),
            self::key( $text ),
            self::cutoff( self::MAX_DAYS )
        );
        if ( ! $row ) {
            return null;
        }

        return array(
            'valid'             => true,
            'country_code'      => (string) $row['country_code'],
            'administrative_1'  => (string) $row['administrative_1'],
            'administrative_2'  => (string) $row['administrative_2'],
            'formatted_address' => (string) $row['formatted_address'],
            'fresh'             => (string) $row['geocoded_at'] >= self::cutoff( self::REFRESH_DAYS ),
        );
    }

    /**
     * @return array{distance_km: float, duration_minutes: int, fresh: bool}|null
     *         Null when the route was never measured or its measure is older than MAX_DAYS.
     */
    public static function route( $origin, $destination ) {
        $row = self::row(
            'SELECT distance_meters, duration_seconds, measured_at FROM %i WHERE route_hash = %s AND measured_at >= %s',
            self::table( 'routes' ),
            self::routeKey( $origin, $destination ),
            self::cutoff( self::MAX_DAYS )
        );
        if ( ! $row || (int) $row['distance_meters'] <= 0 ) {
            return null;
        }

        $seconds = (int) $row['duration_seconds'];
        return array(
            'distance_km'      => round( (int) $row['distance_meters'] / 1000, 2 ),
            'duration_minutes' => $seconds > 0 ? (int) ceil( $seconds / 60 ) : 0,
            'fresh'            => (string) $row['measured_at'] >= self::cutoff( self::REFRESH_DAYS ),
        );
    }

    /**
     * Counts one lookup of the address and, when Google just answered, stores
     * that answer.
     *
     * @param array|null $geocode    place_id, formatted_address, country_code, administrative_1, administrative_2, lat, lng.
     * @param bool       $from_cache True when the stored answer was used instead of calling Google.
     */
    public static function rememberAddress( $text, $geocode = null, $from_cache = false ) {
        $values = array(
            'address_hash' => self::key( $text ),
            'address'      => self::clip( $text ),
        );
        $refresh = '';
        if ( is_array( $geocode ) ) {
            $values += array(
                'place_id'          => self::clip( $geocode['place_id'] ?? '', 255 ),
                'formatted_address' => self::clip( $geocode['formatted_address'] ?? '' ),
                'country_code'      => strtoupper( substr( (string) ( $geocode['country_code'] ?? '' ), 0, 2 ) ),
                'administrative_1'  => self::clip( $geocode['administrative_1'] ?? '', 191 ),
                'administrative_2'  => self::clip( $geocode['administrative_2'] ?? '', 191 ),
                'lat'               => self::coordinate( $geocode['lat'] ?? null ),
                'lng'               => self::coordinate( $geocode['lng'] ?? null ),
                'geocoded_at'       => gmdate( 'Y-m-d H:i:s' ),
            );
            if ( '' === $values['place_id'] ) {
                $values['place_id'] = null;
            }
            // A place ID may be kept indefinitely, so a refresh without one keeps the old one.
            $refresh = ', place_id = COALESCE(VALUES(place_id), place_id), formatted_address = VALUES(formatted_address),'
                . ' country_code = VALUES(country_code), administrative_1 = VALUES(administrative_1),'
                . ' administrative_2 = VALUES(administrative_2), lat = VALUES(lat), lng = VALUES(lng), geocoded_at = VALUES(geocoded_at)';
        }

        return self::upsert( self::table( 'addresses' ), $values, $from_cache, $refresh );
    }

    /**
     * @param array|null $metrics    distance_meters and duration_seconds straight from Google.
     * @param bool       $from_cache True when the stored measure was used instead of calling Google.
     */
    public static function rememberRoute( $origin, $destination, $metrics = null, $from_cache = false ) {
        $values = array(
            'route_hash'  => self::routeKey( $origin, $destination ),
            'origin'      => self::clip( $origin ),
            'destination' => self::clip( $destination ),
        );
        $refresh = '';
        if ( is_array( $metrics ) ) {
            $values += array(
                'distance_meters'  => max( 0, (int) ( $metrics['distance_meters'] ?? 0 ) ),
                'duration_seconds' => max( 0, (int) ( $metrics['duration_seconds'] ?? 0 ) ),
                'measured_at'      => gmdate( 'Y-m-d H:i:s' ),
            );
            $refresh = ', distance_meters = VALUES(distance_meters), duration_seconds = VALUES(duration_seconds), measured_at = VALUES(measured_at)';
        }

        return self::upsert( self::table( 'routes' ), $values, $from_cache, $refresh );
    }

    /**
     * Daily: drops Google data older than MAX_DAYS (keeping the submitted
     * address, the place ID and the counters) and forgets addresses and
     * routes nobody has quoted for RETENTION_DAYS.
     */
    public static function purge() {
        $db = self::db();
        if ( ! $db ) {
            return false;
        }

        $expired   = self::cutoff( self::MAX_DAYS );
        $forgotten = self::cutoff( self::RETENTION_DAYS );
        $addresses = self::table( 'addresses' );
        $routes    = self::table( 'routes' );
        $results   = array(
            $db->query( $db->prepare( 'UPDATE %i SET formatted_address = NULL, country_code = NULL, administrative_1 = NULL, administrative_2 = NULL, lat = NULL, lng = NULL, geocoded_at = NULL WHERE geocoded_at < %s', $addresses, $expired ) ),
            $db->query( $db->prepare( 'UPDATE %i SET distance_meters = NULL, duration_seconds = NULL, measured_at = NULL WHERE measured_at < %s', $routes, $expired ) ),
            $db->query( $db->prepare( 'DELETE FROM %i WHERE last_seen_at < %s', $addresses, $forgotten ) ),
            $db->query( $db->prepare( 'DELETE FROM %i WHERE last_seen_at < %s', $routes, $forgotten ) ),
        );
        return ! in_array( false, $results, true );
    }

    /**
     * @return array{addresses: int, routes: int, saved: int}|null Null when the tables cannot be read.
     */
    public static function stats() {
        $db = self::db();
        if ( ! $db ) {
            return null;
        }

        $row = $db->get_row(
            $db->prepare(
                'SELECT (SELECT COUNT(*) FROM %i) AS addresses, (SELECT COUNT(*) FROM %i) AS routes,'
                . ' (SELECT COALESCE(SUM(hits), 0) FROM %i) + (SELECT COALESCE(SUM(hits), 0) FROM %i) AS saved',
                self::table( 'addresses' ),
                self::table( 'routes' ),
                self::table( 'addresses' ),
                self::table( 'routes' )
            ),
            ARRAY_A
        );
        if ( ! is_array( $row ) ) {
            return null;
        }

        return array(
            'addresses' => (int) $row['addresses'],
            'routes'    => (int) $row['routes'],
            'saved'     => (int) $row['saved'],
        );
    }

    private static function upsert( $table, array $values, $from_cache, $refresh ) {
        $db = self::db();
        if ( ! $db ) {
            return false;
        }

        $now                     = gmdate( 'Y-m-d H:i:s' );
        $values['lookups']       = 1;
        $values['hits']          = $from_cache ? 1 : 0;
        $values['first_seen_at'] = $now;
        $values['last_seen_at']  = $now;

        // wpdb::prepare() turns null into '', which a strict decimal column rejects.
        $placeholders = array();
        $args         = array( $table );
        foreach ( $values as $value ) {
            if ( null === $value ) {
                $placeholders[] = 'NULL';
                continue;
            }
            $placeholders[] = is_int( $value ) ? '%d' : '%s';
            $args[]         = $value;
        }

        $sql = 'INSERT INTO %i (' . implode( ', ', array_keys( $values ) ) . ') VALUES (' . implode( ', ', $placeholders ) . ')'
            . ' ON DUPLICATE KEY UPDATE lookups = lookups + 1, hits = hits + VALUES(hits), last_seen_at = VALUES(last_seen_at)' . $refresh;
        return false !== $db->query( $db->prepare( $sql, ...$args ) );
    }

    private static function row( $sql, ...$args ) {
        $db = self::db();
        if ( ! $db ) {
            return null;
        }

        $row = $db->get_row( $db->prepare( $sql, ...$args ), ARRAY_A );
        return is_array( $row ) ? $row : null;
    }

    private static function db() {
        global $wpdb;
        return is_object( $wpdb ) ? $wpdb : null;
    }

    private static function table( $name ) {
        global $wpdb;
        return ( is_object( $wpdb ) ? $wpdb->prefix : 'wp_' ) . 'mt_' . $name;
    }

    private static function routeKey( $origin, $destination ) {
        return hash( 'sha256', self::key( $origin ) . '>' . self::key( $destination ) );
    }

    private static function cutoff( $days ) {
        return gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
    }

    private static function clip( $text, $limit = self::TEXT_LIMIT ) {
        $text = (string) $text;
        return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $limit, 'UTF-8' ) : substr( $text, 0, $limit );
    }

    private static function coordinate( $value ) {
        return is_numeric( $value ) ? sprintf( '%.7F', (float) $value ) : null;
    }
}
