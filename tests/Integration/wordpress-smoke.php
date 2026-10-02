<?php
/**
 * Behavioral smoke test executed by WP-CLI after a real WordPress bootstrap.
 */

function mt_wp_integration_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAILED: {$message}\n" );
		exit( 1 );
	}
}

global $wpdb, $wp_version;

mt_wp_integration_assert( version_compare( $wp_version, '6.8', '>=' ), 'WordPress 6.8 or newer must be running.' );
mt_wp_integration_assert( 'metransfers' === get_stylesheet(), 'The integrated MeTransfers theme must be active.' );
mt_wp_integration_assert( defined( 'MT_PLATFORM_VERSION' ), 'The platform bootstrap must define its application version.' );
mt_wp_integration_assert( class_exists( '\MeTransfers\Core\Application' ), 'The modern application must load.' );
mt_wp_integration_assert( class_exists( 'WPTB_Public' ) && class_exists( 'HQP_Public' ), 'Legacy booking adapters must load through the theme.' );

foreach ( array( 'wptb_destination', 'hotel_partner', 'ruta' ) as $post_type ) {
	mt_wp_integration_assert( post_type_exists( $post_type ), "Post type {$post_type} must be registered." );
}

foreach ( array( 'wptb_booking_form', 'wptb_vehicle_selection', 'wptb_booking_details', 'wptb_checkout' ) as $shortcode ) {
	mt_wp_integration_assert( shortcode_exists( $shortcode ), "Shortcode {$shortcode} must be registered." );
}

$administrator = get_role( 'administrator' );
mt_wp_integration_assert( $administrator && $administrator->has_cap( 'mt_manage_integrations' ), 'Administrators must receive integration capabilities.' );
$operator = get_role( 'metransfers_operator' );
mt_wp_integration_assert( $operator && $operator->has_cap( 'mt_manage_bookings' ), 'The operations role must be installed.' );
mt_wp_integration_assert( ! $operator->has_cap( 'manage_options' ), 'The operations role must remain least-privilege.' );

$migrations = new \MeTransfers\Core\Migrations();
mt_wp_integration_assert( true === $migrations->maybe_run(), 'The migration orchestrator must be idempotent.' );
mt_wp_integration_assert( MT_PLATFORM_DB_VERSION === get_option( 'mt_platform_db_version' ), 'The schema version must reach the application target.' );

$tables = array(
	'wptb_bookings',
	'wptb_backups',
	'wptb_vehicle_types',
	'wptb_vehicles',
	'wptb_vehicle_images',
	'wptb_hotel_vehicles',
	'mt_schema_migrations',
	'mt_analytics_outbox',
	'mt_outbox',
	'mt_booking_drafts',
	'mt_admin_audit',
	'mt_addresses',
	'mt_routes',
);
foreach ( $tables as $suffix ) {
	$table = $wpdb->prefix . $suffix;
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	mt_wp_integration_assert( $table === $found, "Database table {$table} must exist." );
}

$journal   = $wpdb->prefix . 'mt_schema_migrations';
$succeeded = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM %i WHERE status = 'succeeded'",
		$journal
	)
);
mt_wp_integration_assert( 13 === $succeeded, 'All thirteen discrete migrations must be journaled as succeeded.' );
foreach ( array( '20260928_001_purge_retired_language_data', '20260929_001_single_maps_key', '20260929_002_address_cache_schema' ) as $migration_id ) {
	$status = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT status FROM %i WHERE migration_id = %s',
			$journal,
			$migration_id
		)
	);
	mt_wp_integration_assert( 'succeeded' === $status, "Migration {$migration_id} must run on a real database." );
}
mt_wp_integration_assert( false === get_option( 'wptb_google_maps_server_api_key' ), 'The retired server Maps key option must not survive the migration.' );
mt_wp_integration_assert( has_action( \MeTransfers\Core\Outbox::CRON_HOOK ), 'The durable outbox worker must be registered.' );
mt_wp_integration_assert( false !== wp_next_scheduled( \MeTransfers\Core\Outbox::CRON_HOOK ), 'The durable outbox worker must be scheduled.' );

// Address cache: the real SQL (upsert, NULL coordinates, 30-day purge, retention) on MariaDB.
$address_table = $wpdb->prefix . 'mt_addresses';
$route_table   = $wpdb->prefix . 'mt_routes';
$cache_address = 'Integration Address ' . wp_generate_password( 8, false );
$cache_hash    = \MeTransfers\Booking\AddressCache::key( $cache_address );
$geocode       = array( 'place_id' => 'ChIJ-integration', 'formatted_address' => 'Integration, Spain', 'country_code' => 'ES', 'administrative_1' => 'Catalonia', 'administrative_2' => 'Barcelona', 'lat' => null, 'lng' => 2.0833 );
mt_wp_integration_assert( false !== wp_next_scheduled( \MeTransfers\Booking\AddressCache::CRON_HOOK ), 'The daily address cache purge must be scheduled.' );
mt_wp_integration_assert( \MeTransfers\Booking\AddressCache::rememberAddress( $cache_address, $geocode ), 'A Google answer must be stored.' );
$stored = \MeTransfers\Booking\AddressCache::address( strtoupper( $cache_address ) );
mt_wp_integration_assert( $stored && 'ES' === $stored['country_code'] && 'Catalonia' === $stored['administrative_1'] && $stored['fresh'], 'A stored address must be read back fresh, whatever its case.' );
\MeTransfers\Booking\AddressCache::rememberAddress( $cache_address, null, true );
\MeTransfers\Booking\AddressCache::rememberAddress( $cache_address, array( 'place_id' => '' ) + $geocode );
$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE address_hash = %s', $address_table, $cache_hash ), ARRAY_A );
mt_wp_integration_assert( 3 === (int) $row['lookups'] && 1 === (int) $row['hits'], 'Lookups and saved Google calls must be counted.' );
mt_wp_integration_assert( null === $row['lat'] && '2.0833000' === $row['lng'] && 'ChIJ-integration' === $row['place_id'], 'Missing coordinates must be NULL and a refresh without a place ID must keep the stored one.' );
mt_wp_integration_assert( \MeTransfers\Booking\AddressCache::rememberRoute( $cache_address, 'Integration Destination', array( 'distance_meters' => 12400, 'duration_seconds' => 1500 ) ), 'A route measure must be stored.' );
$measure = \MeTransfers\Booking\AddressCache::route( $cache_address, 'Integration Destination' );
mt_wp_integration_assert( $measure && 12.4 === $measure['distance_km'] && 25 === $measure['duration_minutes'] && $measure['fresh'], 'A stored route must be read back.' );
mt_wp_integration_assert( null === \MeTransfers\Booking\AddressCache::route( 'Integration Destination', $cache_address ), 'The opposite direction is a different route.' );
$expired = gmdate( 'Y-m-d H:i:s', time() - ( \MeTransfers\Booking\AddressCache::MAX_DAYS + 1 ) * DAY_IN_SECONDS );
$wpdb->query( $wpdb->prepare( 'UPDATE %i SET geocoded_at = %s WHERE address_hash = %s', $address_table, $expired, $cache_hash ) );
$wpdb->query( $wpdb->prepare( 'UPDATE %i SET measured_at = %s WHERE origin = %s', $route_table, $expired, $cache_address ) );
mt_wp_integration_assert( null === \MeTransfers\Booking\AddressCache::address( $cache_address ), 'Google data older than 30 days must never be read.' );
mt_wp_integration_assert( \MeTransfers\Booking\AddressCache::purge(), 'The daily purge must run on a real database.' );
$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE address_hash = %s', $address_table, $cache_hash ), ARRAY_A );
mt_wp_integration_assert( null === $row['country_code'] && null === $row['lng'] && null === $row['geocoded_at'] && $cache_address === $row['address'] && 'ChIJ-integration' === $row['place_id'], 'The purge must delete Google data but keep the address and place ID.' );
mt_wp_integration_assert( null === $wpdb->get_var( $wpdb->prepare( 'SELECT distance_meters FROM %i WHERE origin = %s', $route_table, $cache_address ) ), 'The purge must delete route measures older than 30 days.' );
$forgotten = gmdate( 'Y-m-d H:i:s', time() - ( \MeTransfers\Booking\AddressCache::RETENTION_DAYS + 1 ) * DAY_IN_SECONDS );
$wpdb->query( $wpdb->prepare( 'UPDATE %i SET last_seen_at = %s WHERE address_hash = %s', $address_table, $forgotten, $cache_hash ) );
\MeTransfers\Booking\AddressCache::purge();
mt_wp_integration_assert( null === $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE address_hash = %s', $address_table, $cache_hash ) ), 'Addresses nobody quoted for 13 months must be deleted.' );
mt_wp_integration_assert( is_array( \MeTransfers\Booking\AddressCache::stats() ), 'The admin statistics query must run.' );

// Hotel bookings Excel export: real queries, hotel matching and a readable file.
require_once ABSPATH . 'wp-admin/includes/file.php';
$export_hotel = wp_insert_post( array( 'post_type' => 'hotel_partner', 'post_status' => 'publish', 'post_title' => 'Integration Hotel' ) );
update_post_meta( $export_hotel, '_hqp_token', 'HOTEL-INTEGRATION-TOKEN' );
$bookings_table = $wpdb->prefix . 'wptb_bookings';
$export_ids     = array();
foreach ( array( array( 'hotel_id' => $export_hotel ), array( 'hotel_token' => 'HOTEL-INTEGRATION-TOKEN' ) ) as $link ) {
	$wpdb->insert( $bookings_table, $link + array( 'booking_date' => '2026-10-04', 'booking_time' => '12:00:00', 'origin' => 'Aeropuerto BCN', 'destination' => 'Integration Hotel', 'price' => 50, 'status' => 'confirmed', 'source' => 'Hotel QR' ) );
	$export_ids[] = (int) $wpdb->insert_id;
}
$export = \MeTransfers\Admin\HotelBookingsExport::collect();
$linked = array();
foreach ( $export['bookings'] as $row ) {
	if ( in_array( (int) $row['id'], $export_ids, true ) ) {
		$linked[] = (int) $row['export_hotel_id'];
		mt_wp_integration_assert( ! array_key_exists( 'hotel_token', $row ), 'The hotel token must not be exported.' );
	}
}
mt_wp_integration_assert( array( $export_hotel, $export_hotel ) === $linked, 'Hotel bookings must be matched by hotel_id and by the legacy QR token on a real database.' );
$export_file = wp_tempnam( 'integration.xlsx' );
\MeTransfers\Admin\HotelBookingsExport::build( $export['hotels'], $export['bookings'], current_datetime() )->save( $export_file );
$export_zip = new ZipArchive();
mt_wp_integration_assert( true === $export_zip->open( $export_file ) && false !== strpos( (string) $export_zip->getFromName( 'xl/workbook.xml' ), 'name="Integration Hotel"' ), 'The export must produce an .xlsx with one sheet per hotel.' );
$export_zip->close();
wp_delete_file( $export_file );
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE id IN (%d, %d)', $bookings_table, $export_ids[0], $export_ids[1] ) );
wp_delete_post( $export_hotel, true );

$public_query_vars = apply_filters( 'query_vars', array() );
mt_wp_integration_assert( in_array( 'mt_lang', $public_query_vars, true ), 'The language query variable must be public.' );
mt_wp_integration_assert( in_array( 'mt_page', $public_query_vars, true ), 'The translated page query variable must be public.' );

$rules           = get_option( 'rewrite_rules', array() );
$translated_rule = false;
foreach ( array_keys( (array) $rules ) as $rule ) {
	if ( false !== strpos( $rule, '^(en)' ) ) {
		$translated_rule = true;
		break;
	}
}
mt_wp_integration_assert( $translated_rule, 'English rewrite rules must be generated.' );

foreach ( array( 'fr', 'de', 'it', 'pt', 'ca', 'ru', 'zh', 'ja', 'ar' ) as $retired ) {
	foreach ( array_keys( (array) $rules ) as $rule ) {
		mt_wp_integration_assert( false === strpos( $rule, $retired . '|' ) && false === strpos( $rule, '|' . $retired ), "Retired language {$retired} must not remain in active rewrite rules." );
	}
}

// Blog migration: real database, dry run, quoted content and guarded rollback.
$blog_id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => 'mt-editorial-old-topic', 'post_title' => 'Editorial fixture', 'post_content' => '<p>Driver\'s guide <a href="https://sales.example/">Sales</a></p>', 'post_excerpt' => 'Old unrelated excerpt' ) );
$blog_original = get_post( $blog_id );
$blog_old_url = get_permalink( $blog_id );
update_post_meta( $blog_id, '_yoast_wpseo_canonical', $blog_old_url );
$blog_version = 'integration-editorial-' . $blog_id;
$blog_manifest_file = wp_tempnam( 'blog-repair.json' );
file_put_contents( $blog_manifest_file, wp_json_encode( array( 'version' => $blog_version, 'posts' => array( array( 'id' => $blog_id, 'old_slug' => $blog_original->post_name, 'title' => $blog_original->post_title, 'expected_modified' => $blog_original->post_modified, 'new_slug' => 'mt-editorial-transfer-guide', 'new_excerpt' => 'Driver\'s transfer guide' ) ) ) ) );
$blog_tools = get_template_directory() . '/tools/';
$args = array( $blog_manifest_file );
include $blog_tools . 'fix-blog-slugs.php';
mt_wp_integration_assert( 'mt-editorial-old-topic' === get_post( $blog_id )->post_name, 'Dry run must not mutate blog data.' );
$args = array( $blog_manifest_file, '--apply' );
include $blog_tools . 'fix-blog-slugs.php';
$blog_applied = get_post( $blog_id );
mt_wp_integration_assert( 'mt-editorial-transfer-guide' === $blog_applied->post_name && "Driver's transfer guide" === $blog_applied->post_excerpt, 'Slug and independent excerpt must update on the same post.' );
mt_wp_integration_assert( $blog_original->post_content === $blog_applied->post_content && $blog_original->post_title === $blog_applied->post_title, 'Body, title and sales links must remain unchanged.' );
mt_wp_integration_assert( get_permalink( $blog_id ) === get_post_meta( $blog_id, '_yoast_wpseo_canonical', true ), 'Explicit self-canonical must follow the renamed post.' );
mt_wp_integration_assert( '/mt-editorial-transfer-guide/?utm_source=test' === \MeTransfers\SEO\BlogSlugRedirects::targetForRequest( '/mt-editorial-old-topic/?utm_source=test', get_option( \MeTransfers\SEO\BlogSlugRedirects::OPTION ) ), 'Applied migration must preserve old inbound links and query parameters.' );
mt_wp_integration_assert( '/en/mt-editorial-transfer-guide/?utm_source=test' === \MeTransfers\SEO\BlogSlugRedirects::nativeEnglishTargetForRequest( '/en/mt-editorial-old-topic/?utm_source=test' ), 'Native WordPress slug history must also preserve English links without a migration map.' );
include $blog_tools . 'restore-blog-slugs.php';
$blog_restored = get_post( $blog_id );
mt_wp_integration_assert( $blog_original->post_name === $blog_restored->post_name && $blog_original->post_excerpt === $blog_restored->post_excerpt, 'Rollback must restore original URL and excerpt.' );
mt_wp_integration_assert( $blog_old_url === get_post_meta( $blog_id, '_yoast_wpseo_canonical', true ), 'Rollback must restore explicit self-canonical.' );
mt_wp_integration_assert( ! isset( get_option( \MeTransfers\SEO\BlogSlugRedirects::OPTION, array() )[ $blog_original->post_name ] ), 'Rollback must remove only its migration redirect.' );
mt_wp_integration_assert( null === \MeTransfers\SEO\BlogSlugRedirects::nativeEnglishTargetForRequest( '/en/mt-editorial-old-topic/' ), 'A restored current slug must not redirect through native slug history.' );
wp_delete_post( $blog_id, true );
wp_delete_file( $blog_manifest_file );
delete_option( 'mt_blog_slugs_backup_' . $blog_version );
delete_option( 'mt_blog_slugs_backup_' . $blog_version . '_redirects' );

echo "WordPress {$wp_version} integration smoke passed.\n";
