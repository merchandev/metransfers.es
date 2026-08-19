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
);
foreach ( $tables as $suffix ) {
	$table = $wpdb->prefix . $suffix;
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	mt_wp_integration_assert( $table === $found, "Database table {$table} must exist." );
}

$journal = $wpdb->prefix . 'mt_schema_migrations';
$succeeded = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM %i WHERE status = 'succeeded'",
		$journal
	)
);
mt_wp_integration_assert( 5 === $succeeded, 'All five discrete migrations must be journaled as succeeded.' );

mt_wp_integration_assert( has_action( \MeTransfers\Core\Outbox::CRON_HOOK ), 'The durable outbox worker must be registered.' );
mt_wp_integration_assert( false !== wp_next_scheduled( \MeTransfers\Core\Outbox::CRON_HOOK ), 'The durable outbox worker must be scheduled.' );

$public_query_vars = apply_filters( 'query_vars', array() );
mt_wp_integration_assert( in_array( 'mt_lang', $public_query_vars, true ), 'The language query variable must be public.' );
mt_wp_integration_assert( in_array( 'mt_page', $public_query_vars, true ), 'The translated page query variable must be public.' );

$rules = get_option( 'rewrite_rules', array() );
$translated_rule = false;
foreach ( array_keys( (array) $rules ) as $rule ) {
	if ( false !== strpos( $rule, '(en|fr|de|it|pt|ca|ru|zh|ja|ar)' ) ) {
		$translated_rule = true;
		break;
	}
}
mt_wp_integration_assert( $translated_rule, 'Translated rewrite rules must be generated.' );

echo "WordPress {$wp_version} integration smoke passed.\n";
