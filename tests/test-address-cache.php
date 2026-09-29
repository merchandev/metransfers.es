<?php
/**
 * Quoted addresses and routes are answered from the database, refreshed
 * after a week, kept quoting through a Google outage for up to 30 days, and
 * the Google data is purged after 30 days (Google Maps Platform terms).
 */

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ARRAY_A', 'ARRAY_A' );

$GLOBALS['mt_test_options']    = array();
$GLOBALS['mt_test_transients'] = array();
$GLOBALS['mt_test_urls']       = array();
$GLOBALS['mt_test_offline']    = false;
$GLOBALS['mt_test_geocode']    = null;

function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function remove_accents( $value ) { return $value; }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function mt_lang() { return 'es'; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function apply_filters( $hook, $value ) { return $value; }
function get_transient( $key ) { return $GLOBALS['mt_test_transients'][ $key ] ?? false; }
function set_transient( $key, $value ) { $GLOBALS['mt_test_transients'][ $key ] = $value; return true; }
ini_set( 'error_log', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null' );
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function get_option( $key, $default = false ) {
    return array_key_exists( $key, $GLOBALS['mt_test_options'] ) ? $GLOBALS['mt_test_options'][ $key ] : $default;
}
function update_option( $key, $value ) {
    $GLOBALS['mt_test_options'][ $key ] = $value;
    return true;
}
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function wp_remote_get( $url ) {
    $GLOBALS['mt_test_urls'][] = $url;
    if ( $GLOBALS['mt_test_offline'] ) {
        return new WP_Error();
    }
    $payload = false !== strpos( $url, '/geocode/' )
        ? $GLOBALS['mt_test_geocode']
        : array(
            'status' => 'OK',
            'rows'   => array( array( 'elements' => array( array( 'status' => 'OK', 'distance' => array( 'value' => 12400 ), 'duration' => array( 'value' => 1500 ) ) ) ) ),
        );
    return array( 'code' => 200, 'body' => json_encode( $payload ) );
}
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }

class WP_Error {
    public function get_error_message() { return 'cURL error 28: Operation timed out'; }
}

/**
 * In-memory stand-in for the two tables. It follows the statements
 * AddressCache sends; the real SQL runs against MariaDB in
 * tests/Integration/wordpress-smoke.php.
 */
class Fake_Address_Db {
    public $prefix  = 'wp_';
    public $tables  = array( 'wp_mt_addresses' => array(), 'wp_mt_routes' => array() );
    public $queries = array();

    const GOOGLE_COLUMNS = array(
        'wp_mt_addresses' => array( 'place_id', 'formatted_address', 'country_code', 'administrative_1', 'administrative_2', 'lat', 'lng', 'geocoded_at' ),
        'wp_mt_routes'    => array( 'distance_meters', 'duration_seconds', 'measured_at' ),
    );

    public function prepare( $sql, ...$args ) {
        return array( 'sql' => $sql, 'args' => $args );
    }

    public function get_row( $query, $output ) {
        $sql  = $query['sql'];
        $args = $query['args'];
        if ( 0 === strpos( $sql, 'SELECT (SELECT COUNT(*)' ) ) {
            $saved = 0;
            foreach ( $this->tables as $rows ) {
                foreach ( $rows as $row ) {
                    $saved += $row['hits'];
                }
            }
            return array( 'addresses' => count( $this->tables['wp_mt_addresses'] ), 'routes' => count( $this->tables['wp_mt_routes'] ), 'saved' => $saved );
        }

        $column = false !== strpos( $sql, 'address_hash' ) ? 'geocoded_at' : 'measured_at';
        $row    = $this->tables[ $args[0] ][ $args[1] ] ?? null;
        return $row && null !== $row[ $column ] && $row[ $column ] >= $args[2] ? $row : null;
    }

    public function query( $query ) {
        $this->queries[] = $query;
        $sql   = $query['sql'];
        $args  = $query['args'];
        $table = array_shift( $args );

        if ( 0 === strpos( $sql, 'INSERT' ) ) {
            preg_match( '/\((.*?)\) VALUES \((.*?)\) ON DUPLICATE/', $sql, $match );
            $columns = explode( ', ', $match[1] );
            $values  = array();
            foreach ( explode( ', ', $match[2] ) as $i => $placeholder ) {
                $values[ $columns[ $i ] ] = 'NULL' === $placeholder ? null : array_shift( $args );
            }
            $key = $values['address_hash'] ?? $values['route_hash'];
            if ( ! isset( $this->tables[ $table ][ $key ] ) ) {
                $this->tables[ $table ][ $key ] = $values + array_fill_keys( self::GOOGLE_COLUMNS[ $table ], null );
                return 1;
            }

            $row = &$this->tables[ $table ][ $key ];
            $row['lookups']     += 1;
            $row['hits']        += $values['hits'];
            $row['last_seen_at'] = $values['last_seen_at'];
            foreach ( self::GOOGLE_COLUMNS[ $table ] as $column ) {
                if ( false !== strpos( $sql, "$column = VALUES($column)" ) ) {
                    $row[ $column ] = $values[ $column ];
                } elseif ( false !== strpos( $sql, "$column = COALESCE(VALUES($column), $column)" ) && null !== $values[ $column ] ) {
                    $row[ $column ] = $values[ $column ];
                }
            }
            return 2;
        }

        if ( 0 === strpos( $sql, 'UPDATE' ) ) {
            preg_match( '/SET (.*) WHERE (\w+) < %s/', $sql, $match );
            $changed = 0;
            foreach ( $this->tables[ $table ] as &$row ) {
                if ( null !== $row[ $match[2] ] && $row[ $match[2] ] < $args[0] ) {
                    foreach ( explode( ', ', $match[1] ) as $assignment ) {
                        $row[ strtok( $assignment, ' ' ) ] = null;
                    }
                    ++$changed;
                }
            }
            return $changed;
        }

        if ( 0 === strpos( $sql, 'DELETE' ) ) {
            $before                 = count( $this->tables[ $table ] );
            $this->tables[ $table ] = array_filter( $this->tables[ $table ], static function ( $row ) use ( $args ) {
                return $row['last_seen_at'] >= $args[0];
            } );
            return $before - count( $this->tables[ $table ] );
        }

        return false;
    }
}

require_once __DIR__ . '/../app/Core/Settings.php';
require_once __DIR__ . '/../app/Booking/I18n.php';
require_once __DIR__ . '/../app/Booking/MapsProvider.php';
require_once __DIR__ . '/../app/Booking/AddressCache.php';
require_once __DIR__ . '/../app/Booking/ServiceAreaPolicy.php';
require_once __DIR__ . '/../app/Booking/RouteDistance.php';

use MeTransfers\Booking\AddressCache;
use MeTransfers\Booking\RouteDistance;
use MeTransfers\Booking\ServiceAreaPolicy;

function assert_cache( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function geocode_reply( $with_geometry = true ) {
    $result = array(
        'formatted_address'  => 'Aeropuerto de Barcelona-El Prat, 08820 El Prat de Llobregat, Barcelona, Spain',
        'place_id'           => 'ChIJ-test-bcn',
        'address_components' => array(
            array( 'short_name' => 'ES', 'long_name' => 'Spain', 'types' => array( 'country', 'political' ) ),
            array( 'short_name' => 'CT', 'long_name' => 'Catalonia', 'types' => array( 'administrative_area_level_1', 'political' ) ),
            array( 'short_name' => 'B', 'long_name' => 'Barcelona', 'types' => array( 'administrative_area_level_2', 'political' ) ),
        ),
    );
    if ( $with_geometry ) {
        $result['geometry'] = array( 'location' => array( 'lat' => 41.2974, 'lng' => 2.0833 ) );
    }
    $GLOBALS['mt_test_geocode'] = array( 'status' => 'OK', 'results' => array( $result ) );
}

// Each quote is a new PHP request: forget what the previous one resolved.
function new_request() {
    $GLOBALS['mt_test_urls']       = array();
    $GLOBALS['mt_test_transients'] = array();
    $resolved = new ReflectionProperty( ServiceAreaPolicy::class, 'resolved' );
    $resolved->setAccessible( true );
    $resolved->setValue( null, array() );
}

function age( $table, $hash, $column, $days ) {
    global $wpdb;
    $wpdb->tables[ $table ][ $hash ][ $column ] = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS - 60 );
}

$GLOBALS['mt_test_options']['wptb_google_maps_api_key'] = 'test-key';
$GLOBALS['wpdb'] = new Fake_Address_Db();
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
$airport = AddressCache::key( 'Aeropuerto BCN' );

assert_cache( AddressCache::key( '  aeropuerto   BCN ' ) === $airport, 'Case and spacing variants must share one address row.' );

// 1. First quote: Google is asked and both addresses are stored with its answer.
geocode_reply();
new_request();
$route = ServiceAreaPolicy::validateRoute( 'Aeropuerto BCN', 'H10 Casanova' );
assert_cache( ! empty( $route['valid'] ), 'A route inside Catalonia must be accepted.' );
assert_cache( 2 === count( $GLOBALS['mt_test_urls'] ), 'The first quote must geocode both addresses with Google.' );
$row = $wpdb->tables['wp_mt_addresses'][ $airport ];
assert_cache( 'Aeropuerto BCN' === $row['address'] && 'ES' === $row['country_code'] && 'Catalonia' === $row['administrative_1'], 'The submitted address and Google\'s answer must be stored.' );
assert_cache( 'ChIJ-test-bcn' === $row['place_id'] && '41.2974000' === $row['lat'] && '2.0833000' === $row['lng'], 'The place ID and coordinates must be stored.' );
assert_cache( 1 === $row['lookups'] && 0 === $row['hits'] && null !== $row['geocoded_at'], 'A Google answer is a lookup, not a hit.' );

// 2. Same addresses on the next quote: answered from the database.
new_request();
$route = ServiceAreaPolicy::validateRoute( 'aeropuerto bcn', 'H10 Casanova' );
assert_cache( ! empty( $route['valid'] ) && array() === $GLOBALS['mt_test_urls'], 'Known addresses must not call Google again.' );
$row = $wpdb->tables['wp_mt_addresses'][ $airport ];
assert_cache( 2 === $row['lookups'] && 1 === $row['hits'], 'A database answer must count as a saved Google call.' );

// 3. A round trip resolves each address once per request.
new_request();
ServiceAreaPolicy::validateRoute( 'Aeropuerto BCN', 'H10 Casanova' );
ServiceAreaPolicy::validateRoute( 'H10 Casanova', 'Aeropuerto BCN' );
assert_cache( 3 === $wpdb->tables['wp_mt_addresses'][ $airport ]['lookups'], 'The return leg must reuse the addresses already resolved in this request.' );

// 4. After REFRESH_DAYS Google is asked again and the answer replaces the old one.
age( 'wp_mt_addresses', $airport, 'geocoded_at', AddressCache::REFRESH_DAYS + 1 );
$old = $wpdb->tables['wp_mt_addresses'][ $airport ]['geocoded_at'];
new_request();
ServiceAreaPolicy::validateRoute( 'Aeropuerto BCN', 'H10 Casanova' );
assert_cache( 1 === count( $GLOBALS['mt_test_urls'] ), 'Only the stale address must be refreshed.' );
assert_cache( $wpdb->tables['wp_mt_addresses'][ $airport ]['geocoded_at'] > $old, 'A refresh must store the new Google answer.' );

// 5. Stale but younger than MAX_DAYS and Google is down: keep quoting.
age( 'wp_mt_addresses', $airport, 'geocoded_at', AddressCache::REFRESH_DAYS + 1 );
$GLOBALS['mt_test_offline'] = true;
new_request();
$route = ServiceAreaPolicy::validateRoute( 'Aeropuerto BCN', 'H10 Casanova' );
assert_cache( ! empty( $route['valid'] ), 'A Google outage must not stop quotes for addresses stored in the last 30 days.' );
assert_cache( 1 === count( $GLOBALS['mt_test_urls'] ), 'The stale address must still try Google first.' );

// 6. Older than MAX_DAYS: never reused, even during an outage.
age( 'wp_mt_addresses', $airport, 'geocoded_at', AddressCache::MAX_DAYS + 1 );
new_request();
$route = ServiceAreaPolicy::validateRoute( 'Aeropuerto BCN', 'H10 Casanova' );
assert_cache( empty( $route['valid'] ) && 'quote_service_unavailable' === $route['code'], 'Google data older than 30 days must not be used.' );
$GLOBALS['mt_test_offline'] = false;

// 7. An address Google cannot find is still recorded, without Google data.
$GLOBALS['mt_test_geocode'] = array( 'status' => 'ZERO_RESULTS', 'results' => array() );
new_request();
ServiceAreaPolicy::validateRoute( 'Calle Inventada 123', 'H10 Casanova' );
$unknown = $wpdb->tables['wp_mt_addresses'][ AddressCache::key( 'Calle Inventada 123' ) ];
assert_cache( 'Calle Inventada 123' === $unknown['address'] && null === $unknown['geocoded_at'] && null === $unknown['country_code'], 'Unknown addresses must be recorded without Google data.' );

// 8. Missing coordinates are stored as NULL, never as '' (strict decimal columns).
geocode_reply( false );
new_request();
ServiceAreaPolicy::validateRoute( 'Estación de Sants', 'H10 Casanova' );
$insert = null;
foreach ( $wpdb->queries as $query ) {
    if ( in_array( 'Estación de Sants', $query['args'], true ) ) {
        $insert = $query;
    }
}
assert_cache( null === $wpdb->tables['wp_mt_addresses'][ AddressCache::key( 'Estación de Sants' ) ]['lat'], 'Missing coordinates must stay NULL.' );
assert_cache( false !== strpos( $insert['sql'], 'NULL' ) && ! in_array( '', $insert['args'], true ), 'NULL must be sent as SQL NULL, not as an empty string.' );

// 9. Routes: Google once, then the database; outage fallback until MAX_DAYS.
$trip = hash( 'sha256', AddressCache::key( 'Aeropuerto BCN' ) . '>' . AddressCache::key( 'H10 Casanova' ) );
new_request();
$first = RouteDistance::calculate( 'Aeropuerto BCN', 'H10 Casanova' );
assert_cache( 12.4 === $first['distance_km'] && 25 === $first['duration_minutes'] && 1 === count( $GLOBALS['mt_test_urls'] ), 'The first route must be measured by Google.' );
assert_cache( 12400 === $wpdb->tables['wp_mt_routes'][ $trip ]['distance_meters'] && 'H10 Casanova' === $wpdb->tables['wp_mt_routes'][ $trip ]['destination'], 'The route and both addresses must be stored.' );
new_request();
$second = RouteDistance::calculate( 'Aeropuerto BCN', 'H10 Casanova' );
assert_cache( $first === $second && array() === $GLOBALS['mt_test_urls'], 'A known route must be answered from the database with the same figures.' );
assert_cache( 1 === $wpdb->tables['wp_mt_routes'][ $trip ]['hits'], 'A database route must count as a saved Google call.' );
new_request();
RouteDistance::calculate( 'H10 Casanova', 'Aeropuerto BCN' );
assert_cache( 1 === count( $GLOBALS['mt_test_urls'] ), 'The opposite direction is a different route.' );

age( 'wp_mt_routes', $trip, 'measured_at', AddressCache::REFRESH_DAYS + 1 );
$GLOBALS['mt_test_offline'] = true;
new_request();
$fallback = RouteDistance::calculate( 'Aeropuerto BCN', 'H10 Casanova' );
assert_cache( 12.4 === $fallback['distance_km'], 'A Google outage must not stop quotes for routes measured in the last 30 days.' );
age( 'wp_mt_routes', $trip, 'measured_at', AddressCache::MAX_DAYS + 1 );
new_request();
$expired = RouteDistance::calculate( 'Aeropuerto BCN', 'H10 Casanova' );
assert_cache( 'quote_service_unavailable' === ( $expired['code'] ?? '' ), 'Route measures older than 30 days must not be used.' );
$GLOBALS['mt_test_offline'] = false;

// 10. Daily purge: Google data goes after MAX_DAYS; unused rows after RETENTION_DAYS.
$stats = AddressCache::stats();
assert_cache( 4 === $stats['addresses'] && 2 === $stats['routes'] && $stats['saved'] >= 4, 'The admin panel must report stored addresses, routes and saved calls.' );
age( 'wp_mt_addresses', $airport, 'geocoded_at', AddressCache::MAX_DAYS + 1 );
age( 'wp_mt_addresses', AddressCache::key( 'Calle Inventada 123' ), 'last_seen_at', AddressCache::RETENTION_DAYS + 1 );
assert_cache( true === AddressCache::purge(), 'The purge must succeed.' );
$row = $wpdb->tables['wp_mt_addresses'][ $airport ];
assert_cache( null === $row['lat'] && null === $row['country_code'] && null === $row['formatted_address'] && null === $row['geocoded_at'], 'Google data older than 30 days must be deleted.' );
assert_cache( 'Aeropuerto BCN' === $row['address'] && 'ChIJ-test-bcn' === $row['place_id'] && $row['lookups'] > 0, 'The submitted address, place ID and counters must survive the purge.' );
assert_cache( null === $wpdb->tables['wp_mt_routes'][ $trip ]['distance_meters'], 'Route measures older than 30 days must be deleted.' );
assert_cache( ! isset( $wpdb->tables['wp_mt_addresses'][ AddressCache::key( 'Calle Inventada 123' ) ] ), 'Addresses nobody quoted for 13 months must be forgotten.' );
assert_cache( null !== $wpdb->tables['wp_mt_addresses'][ AddressCache::key( 'H10 Casanova' ) ]['geocoded_at'], 'Recent Google data must survive the purge.' );

// 11. Without a database (tests, early bootstrap) quoting still works.
$GLOBALS['wpdb'] = null;
geocode_reply();
new_request();
assert_cache( ! empty( ServiceAreaPolicy::validateRoute( 'Aeropuerto BCN', 'H10 Casanova' )['valid'] ) && null === AddressCache::stats(), 'Quotes must not depend on the cache tables.' );

echo "Address cache tests passed.\n";
