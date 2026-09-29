<?php
/**
 * WP-CLI: wp eval-file tools/maps-check.php
 *
 * Sends one live Geocoding API request and one Distance Matrix API request
 * with the configured Google Maps key and prints Google's status for each,
 * plus what to fix. This is the same check as the "Probar conexión ahora"
 * button in wp-admin. It bypasses caches and test filters, never prints the
 * key, creates no booking and consumes two provider requests.
 *
 * tools/ is excluded from the release ZIP. On a server that only has the
 * deployed theme, the equivalent is:
 *   wp eval 'print_r( \MeTransfers\Booking\MapsProvider::runCheck() );'
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'WP-CLI required.' );
}

$source = \MeTransfers\Core\Settings::source( 'google_maps_api_key' );
WP_CLI::log( 'Google Maps key source: ' . ( 'none' === $source['type'] ? 'none' : $source['type'] . ' ' . $source['name'] ) );

$failed = false;
foreach ( \MeTransfers\Booking\MapsProvider::runCheck() as $service => $result ) {
	WP_CLI::log( sprintf( '%s: %s', $service, $result['status'] ) );
	if ( '' !== $result['detail'] ) {
		WP_CLI::log( '  Google: ' . $result['detail'] );
	}
	if ( ! $result['ok'] ) {
		WP_CLI::log( '  Fix: ' . $result['hint'] );
		$failed = true;
	}
}

if ( $failed ) {
	WP_CLI::error( 'Server-side Google Maps check failed: online quotes cannot work until it passes.' );
}
WP_CLI::success( 'Server-side Google Maps check passed.' );
