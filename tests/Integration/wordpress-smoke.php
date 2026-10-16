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
	'mt_newsletter_subscribers',
	'mt_newsletter_campaigns',
	'mt_newsletter_deliveries',
	'mt_newsletter_manual_queue',
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
mt_wp_integration_assert( 15 === $succeeded, 'All fifteen discrete migrations must be journaled as succeeded.' );
foreach ( array( '20260928_001_purge_retired_language_data', '20260929_001_single_maps_key', '20260929_002_address_cache_schema', '20261016_001_newsletter_schema', '20261016_002_newsletter_manual_queue' ) as $migration_id ) {
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

// Newsletter: signup, double opt-in, campaigns, unsubscribe, import, retries and erasure on a real database.
$nl_smtp_fixture = array( 'wptb_smtp_host' => 'smtp.example.test', 'wptb_smtp_user' => 'newsletter@example.test', 'wptb_smtp_password' => 'test-only-password', 'wptb_smtp_from' => 'newsletter@example.test', 'wptb_smtp_port' => 587, 'wptb_smtp_encryption' => 'tls' );
$nl_smtp_filter = static function ( $value, $option ) use ( $nl_smtp_fixture ) { return $nl_smtp_fixture[ $option ] ?? $value; };
foreach ( array_keys( $nl_smtp_fixture ) as $nl_smtp_option ) {
	add_filter( 'pre_option_' . $nl_smtp_option, $nl_smtp_filter, 10, 2 );
}
// The CI web fixture uses HTTP; mail URLs must exercise the production HTTPS policy.
$nl_https_url = static function ( $url ) { return set_url_scheme( $url, 'https' ); };
add_filter( 'home_url', $nl_https_url );
add_filter( 'admin_url', $nl_https_url );
$nl_subscribers           = \MeTransfers\Newsletter\Subscribers::class;
$nl_campaigns             = \MeTransfers\Newsletter\Campaigns::class;
$nl_deliveries            = $wpdb->prefix . 'mt_newsletter_deliveries';
$GLOBALS['mt_nl_mails']   = array();
$GLOBALS['mt_nl_mail_ok'] = true;
$nl_capture               = static function ( $short_circuit, $atts ) {
	$GLOBALS['mt_nl_mails'][] = $atts;
	return $GLOBALS['mt_nl_mail_ok'];
};
add_filter( 'pre_wp_mail', $nl_capture, 10, 2 );
$nl_mails_to = static function ( $email ) {
	return array_values( array_filter( $GLOBALS['mt_nl_mails'], static fn( $mail ) => in_array( $email, (array) $mail['to'], true ) ) );
};
mt_wp_integration_assert( false !== wp_next_scheduled( $nl_campaigns::CRON_HOOK ), 'The newsletter sending worker must be scheduled.' );
$nl_slot_ip = '198.51.100.' . wp_rand( 1, 254 );
mt_wp_integration_assert( \MeTransfers\Newsletter\PublicController::claimSlot( $nl_slot_ip ) && ! \MeTransfers\Newsletter\PublicController::claimSlot( $nl_slot_ip ), 'The popup must be allowed once per IP per 24 hours on a real WordPress.' );
delete_transient( 'mt_nl_slot_' . substr( hash_hmac( 'sha256', $nl_slot_ip, wp_salt( 'nonce' ) ), 0, 40 ) );
mt_wp_integration_assert( $administrator->has_cap( \MeTransfers\Admin\Capabilities::MANAGE_NEWSLETTER ), 'Administrators must be able to manage the newsletter.' );
mt_wp_integration_assert( ! get_role( 'check_hoteles' )->has_cap( \MeTransfers\Admin\Capabilities::MANAGE_NEWSLETTER ), 'Hotel users must never manage the newsletter.' );

$nl_tag   = strtolower( wp_generate_password( 6, false, false ) );
$nl_email = 'nl-' . $nl_tag . '@example.test';
$signup   = $nl_subscribers::subscribe(
	array(
		'email'        => strtoupper( $nl_email ),
		'name'         => 'Ana',
		'language'     => 'es',
		'signup_page'  => home_url( '/traslados-aeropuerto/?utm_source=newsletter' ),
		'referrer'     => 'https://www.google.com/',
		'pages'        => wp_json_encode(
			array(
				array( 'u' => '/', 't' => 'Inicio', 'at' => '2026-10-16T10:00:00Z' ),
				array( 'u' => 'https://evil.example/phish', 't' => 'Otra web', 'at' => '2026-10-16T10:01:00Z' ),
				array( 'u' => '/traslados-aeropuerto/?utm_source=newsletter', 't' => 'Traslados', 'at' => '2026-10-16T10:02:00Z' ),
			)
		),
		'consent_text' => 'Acepto recibir comunicaciones comerciales.',
	)
);
$nl_id = (int) $signup['subscriber']['id'];
mt_wp_integration_assert( 'pending' === $signup['result'] && $signup['send_confirmation'] && $nl_email === $signup['subscriber']['email'], 'A popup signup must wait for email confirmation (double opt-in), stored in lowercase.' );
mt_wp_integration_assert( '/traslados-aeropuerto/?utm_source=newsletter' === $signup['subscriber']['signup_page'] && 2 === count( $nl_subscribers::pages( $signup['subscriber'] ) ), 'The signup page and the visited pages of this site (no other domains) must be stored.' );
mt_wp_integration_assert( \MeTransfers\Newsletter\PublicController::sendConfirmation( $signup['subscriber'] ) && 1 === count( $nl_mails_to( $nl_email ) ), 'The confirmation email must be sent.' );
$confirm_mail = $nl_mails_to( $nl_email )[0];
mt_wp_integration_assert( false !== strpos( $confirm_mail['message'], esc_url( $nl_subscribers::confirmUrl( $signup['subscriber'] ) ) ), 'The confirmation email must carry its link.' );
mt_wp_integration_assert( 'invalid' === $nl_subscribers::confirm( $nl_id, str_repeat( '0', 64 ) ), 'A forged confirmation token must be rejected.' );
mt_wp_integration_assert( 'confirmed' === $nl_subscribers::confirm( $nl_id, $nl_subscribers::token( 'confirm', $signup['subscriber'] ) ) && 'subscribed' === $nl_subscribers::find( $nl_id )['status'], 'The confirmation link must subscribe.' );
$again = $nl_subscribers::subscribe( array( 'email' => $nl_email, 'pages' => wp_json_encode( array( array( 'u' => '/flota/', 't' => 'Flota', 'at' => '2026-10-16T11:00:00Z' ) ) ) ) );
mt_wp_integration_assert( 'subscribed' === $again['result'] && ! $again['send_confirmation'] && 3 === count( $nl_subscribers::pages( $again['subscriber'] ) ), 'Signing up again must not resend confirmation and must add the new pages.' );

$report = \MeTransfers\Newsletter\Importer::import(
	array(
		array( 'Correo electrónico', 'Nombre', 'Idioma' ),
		array( $nl_email, 'Ana María', 'es' ),
		array( 'import-' . $nl_tag . '@example.test', 'Bob', 'English' ),
		array( 'no-es-un-correo', 'X', '' ),
		array( 'IMPORT-' . $nl_tag . '@example.test', 'Repetido', '' ),
	),
	'pending',
	'es',
	true,
	'Importado en la prueba de integración'
);
mt_wp_integration_assert( 1 === $report['added'] && 1 === $report['updated'] && 1 === $report['invalid'] && 1 === $report['duplicates'], 'Import must add, update names, reject invalid addresses and skip duplicates: ' . wp_json_encode( $report ) );
$imported = $nl_subscribers::findByEmail( 'import-' . $nl_tag . '@example.test' );
mt_wp_integration_assert( 'pending' === $imported['status'] && 'en' === $imported['language'] && 'import' === $imported['source'], 'Imported contacts must keep the chosen status, their language and the source.' );

$campaign_id = $nl_campaigns::save(
	array(
		'subject'         => 'Hola {{nombre}}',
		'preheader'       => 'Solo para suscriptores',
		'body'            => '<p>Oferta para {{nombre}}.</p><script>alert(1)</script>',
		'audience_status' => 'subscribed',
	)
);
mt_wp_integration_assert( false === strpos( $nl_campaigns::find( $campaign_id )['body'], '<script' ), 'Campaign content must be filtered with wp_kses_post.' );
$audience               = $nl_campaigns::audienceCount( 'subscribed', '' );
$GLOBALS['mt_nl_mails'] = array();
mt_wp_integration_assert( $audience === $nl_campaigns::queue( $campaign_id ) && false === $nl_campaigns::queue( $campaign_id ), 'Queuing must freeze the audience once, even with a double click.' );
$nl_campaigns::dispatch();
$campaign_mail = $nl_mails_to( $nl_email );
mt_wp_integration_assert( 1 === count( $campaign_mail ) && 'Hola Ana María' === $campaign_mail[0]['subject'] && false !== strpos( $campaign_mail[0]['message'], 'Oferta para Ana María.' ), 'The campaign must reach the subscriber, personalised. Mailer: ' . \MeTransfers\Newsletter\Mailer::lastError() );
mt_wp_integration_assert( false !== strpos( implode( "\n", (array) $campaign_mail[0]['headers'] ), 'List-Unsubscribe-Post: List-Unsubscribe=One-Click' ) && false !== strpos( $campaign_mail[0]['message'], esc_url( $nl_subscribers::unsubscribeUrl( $nl_subscribers::find( $nl_id ) ) ) ), 'Every campaign email must carry the one-click unsubscribe header and link.' );
mt_wp_integration_assert( ! $nl_mails_to( 'import-' . $nl_tag . '@example.test' ), 'Pending contacts must not receive a campaign for subscribers.' );
mt_wp_integration_assert( 'sent' === $nl_campaigns::find( $campaign_id )['status'] && $audience === (int) $nl_campaigns::find( $campaign_id )['sent'], 'The campaign must close as sent with its counters.' );

$second = $nl_campaigns::save(
	array(
		'subject' => 'Segunda',
		'body'    => '<p>Texto</p>',
	)
);
$nl_campaigns::queue( $second );
mt_wp_integration_assert( 'unsubscribed' === $nl_subscribers::unsubscribe( $nl_id, $nl_subscribers::token( 'unsubscribe', $nl_subscribers::find( $nl_id ) ) ), 'The unsubscribe link must unsubscribe.' );
$GLOBALS['mt_nl_mails'] = array();
$nl_campaigns::dispatch();
mt_wp_integration_assert( ! $nl_mails_to( $nl_email ) && $nl_campaigns::deliveryCounts( $second )['skipped'] >= 1, 'Someone who unsubscribed during a campaign must not receive it.' );
mt_wp_integration_assert( 'sent' === $nl_campaigns::find( $second )['status'], 'A campaign whose remaining recipients all left must still close instead of staying in «Enviando».' );
$blocked = \MeTransfers\Newsletter\Importer::import( array( array( $nl_email ) ), 'subscribed', 'es', true, 'test' );
mt_wp_integration_assert( 1 === $blocked['blocked'] && 'unsubscribed' === $nl_subscribers::find( $nl_id )['status'], 'An import must never re-subscribe someone who unsubscribed.' );
$back = $nl_subscribers::subscribe(
	array(
		'email'        => $nl_email,
		'consent_text' => 'Acepto',
	)
);
mt_wp_integration_assert( 'pending' === $back['result'] && $back['send_confirmation'], 'Signing up again on the website after unsubscribing must ask for confirmation again.' );

// Temporary failure: retried, then failed after MAX_ATTEMPTS; an interrupted send is never repeated.
$third = $nl_campaigns::save(
	array(
		'subject'           => 'Third',
		'body'              => '<p>Text</p>',
		'audience_status'   => 'pending',
		'audience_language' => 'en',
	)
);
$nl_campaigns::queue( $third );
$GLOBALS['mt_nl_mail_ok'] = false;
$bob_delivery             = static fn() => $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE campaign_id = %d AND subscriber_id = %d', $nl_deliveries, $third, (int) $imported['id'] ), ARRAY_A );
for ( $attempt = 1; $attempt <= $nl_campaigns::MAX_ATTEMPTS; $attempt++ ) {
	$wpdb->query( $wpdb->prepare( 'UPDATE %i SET available_at = %s WHERE campaign_id = %d', $nl_deliveries, gmdate( 'Y-m-d H:i:s', time() - 60 ), $third ) );
	$nl_campaigns::dispatch();
}
$failed = $bob_delivery();
mt_wp_integration_assert( 'failed' === $failed['status'] && $nl_campaigns::MAX_ATTEMPTS === (int) $failed['attempts'] && 'wp_mail_failed' === $failed['last_error'], 'A failing address must be retried and then marked failed with its reason.' );
$GLOBALS['mt_nl_mail_ok'] = true;
$wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'sending', locked_at = %s WHERE id = %d", $nl_deliveries, gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ), (int) $failed['id'] ) );
$GLOBALS['mt_nl_mails'] = array();
$nl_campaigns::dispatch();
mt_wp_integration_assert( 'interrupted' === $bob_delivery()['last_error'] && ! $nl_mails_to( 'import-' . $nl_tag . '@example.test' ), 'A send interrupted mid-way must not be repeated.' );

// A mail server outage on signup leaves the confirmation in the outbox for a retry.
$GLOBALS['mt_nl_mail_ok'] = false;
$outage                   = $nl_subscribers::subscribe(
	array(
		'email'        => 'outage-' . $nl_tag . '@example.test',
		'consent_text' => 'Acepto',
	)
);
mt_wp_integration_assert( false === \MeTransfers\Newsletter\PublicController::sendConfirmation( $outage['subscriber'] ), 'A failed confirmation must be reported.' );
mt_wp_integration_assert( '1' === (string) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE event_type = 'newsletter.confirm' AND aggregate_id = %d", $wpdb->prefix . 'mt_outbox', (int) $outage['subscriber']['id'] ) ), 'The confirmation must be queued for a retry.' );
$GLOBALS['mt_nl_mail_ok'] = true;
mt_wp_integration_assert( \MeTransfers\Core\OutboxHandler::handle( 'newsletter.confirm', (int) $outage['subscriber']['id'], array() ) && null !== $nl_subscribers::find( (int) $outage['subscriber']['id'] )['confirmation_sent_at'], 'The outbox must deliver the confirmation once the mail server is back.' );

// The footer signup block renders on a real WordPress and can be switched off (before any admin screen is set).
$nl_footer = \MeTransfers\Newsletter\PublicController::footerForm();
mt_wp_integration_assert( false !== strpos( $nl_footer, 'data-mt-newsletter-footer' ) && false !== strpos( $nl_footer, 'name="consent"' ) && false !== strpos( $nl_footer, 'method="post"' ), 'The footer signup block must render.' );
update_option( \MeTransfers\Newsletter\Settings::OPTION, array( 'footer_enabled' => false ) );
mt_wp_integration_assert( '' === \MeTransfers\Newsletter\PublicController::footerForm(), 'The footer signup block must disappear when switched off.' );
delete_option( \MeTransfers\Newsletter\Settings::OPTION );

// wp-admin screens render on a real WordPress.
require_once ABSPATH . 'wp-admin/includes/admin.php';
wp_set_current_user( 1 );
set_current_screen( 'toplevel_page_mt-newsletter' );
foreach ( array( 'renderSubscribers' => 'Newsletter · Suscriptores', 'renderCampaigns' => 'Newsletter · Campañas', 'renderImport' => 'Importar contactos', 'renderSettings' => 'Newsletter · Ajustes' ) as $nl_screen => $nl_title ) {
	ob_start();
	\MeTransfers\Newsletter\Admin::$nl_screen();
	mt_wp_integration_assert( false !== strpos( (string) ob_get_clean(), $nl_title ), "The admin screen $nl_screen must render." );
}
$_GET['subscriber'] = (string) $nl_id;
ob_start();
\MeTransfers\Newsletter\Admin::renderSubscribers();
mt_wp_integration_assert( false !== strpos( (string) ob_get_clean(), '/flota/' ), 'The subscriber screen must list the pages visited before subscribing.' );
unset( $_GET['subscriber'] );
$_GET['new'] = '1';
ob_start();
\MeTransfers\Newsletter\Admin::renderCampaigns();
mt_wp_integration_assert( false !== strpos( (string) ob_get_clean(), 'name="body"' ), 'The campaign editor must render.' );
unset( $_GET['new'] );

// Right to erasure: the subscriber and their delivery history are gone.

// Manual additions wait six hours, admit a random bounded batch and never delay popup signups.
$manual_queue = \MeTransfers\Newsletter\ManualQueue::class;
$manual_rows = array( array( 'email', 'name' ) );
for ( $manual_index = 0; $manual_index < 110; ++$manual_index ) {
	$manual_rows[] = array( 'manual-' . $manual_index . '-' . $nl_tag . '@example.test', 'Manual ' . $manual_index );
}
$manual_report = \MeTransfers\Newsletter\Importer::import( $manual_rows, 'subscribed', 'es', false, 'Consent confirmed in fixture', true );
mt_wp_integration_assert( 110 === $manual_report['queued'] && 110 === $manual_queue::count() && 0 === $manual_queue::dispatch(), 'File imports must enter the durable manual queue without an immediate release.' );
mt_wp_integration_assert( null === $nl_subscribers::findByEmail( $manual_rows[1][0] ) && wp_next_scheduled( $manual_queue::HOOK ) >= time() + 6 * HOUR_IN_SECONDS - 10, 'The first batch must be scheduled six hours after the first enqueue.' );
$manual_popup = $nl_subscribers::subscribe( array( 'email' => 'manual-organic-' . $nl_tag . '@example.test', 'source' => 'popup', 'consent_text' => 'Organic signup' ) );
mt_wp_integration_assert( 'pending' === $manual_popup['result'] && $manual_popup['subscriber'] && 110 === $manual_queue::count(), 'Organic signup must remain immediate and independent of the manual backlog.' );
update_option( $manual_queue::NEXT, time() - DAY_IN_SECONDS, false );
$manual_released = $manual_queue::dispatch();
mt_wp_integration_assert( $manual_released >= 12 && $manual_released <= 98 && 110 - $manual_released === $manual_queue::count(), 'One delayed cron must release only one FIFO batch of 12 to 98 contacts.' );
mt_wp_integration_assert( 0 === $manual_queue::dispatch() && 'subscribed' === $nl_subscribers::findByEmail( $manual_rows[1][0] )['status'], 'A second worker invocation cannot release another batch in the same six-hour window.' );
$manual_first = $nl_subscribers::findByEmail( $manual_rows[1][0] );
mt_wp_integration_assert( 1 === $nl_subscribers::delete( array( $manual_first['id'] ) ) && null === $nl_subscribers::findByEmail( $manual_rows[1][0] ), 'An administrator can actually delete an admitted manual subscriber on WordPress/MariaDB.' );
$manual_waiting = 'manual-waiting-' . $nl_tag . '@example.test';
$manual_queue::enqueue( $manual_waiting, '', 'es', 'pending', 'admin', 'Consent request' );
mt_wp_integration_assert( count( \MeTransfers\Newsletter\Privacy::export( $manual_waiting )['data'] ) && \MeTransfers\Newsletter\Privacy::erase( $manual_waiting )['items_removed'], 'Privacy tools must export and erase contacts waiting in the manual queue.' );
mt_wp_integration_assert( $manual_queue::count() === $manual_queue::cancel() && 0 === $manual_queue::count() && ! wp_next_scheduled( $manual_queue::HOOK ), 'Cancelling the manual backlog must clear its schedule without affecting admitted subscribers.' );
$manual_pending_email = 'manual-pending-' . $nl_tag . '@example.test';
$manual_queue::enqueue( $manual_pending_email, '', 'es', 'pending', 'admin', 'Confirmation requested' );
update_option( $manual_queue::NEXT, time() - 1, false );
mt_wp_integration_assert( 1 === $manual_queue::dispatch() && 0 === $manual_queue::count(), 'A pending manual subscriber must be admitted transactionally.' );
$manual_pending = $nl_subscribers::findByEmail( $manual_pending_email );
mt_wp_integration_assert( 'pending' === $manual_pending['status'] && '1' === (string) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE event_type = 'newsletter.confirm' AND aggregate_id = %d", $wpdb->prefix . 'mt_outbox', $manual_pending['id'] ) ), 'Manual pending admission must persist exactly one confirmation in the outbox.' );

$erased = \MeTransfers\Newsletter\Privacy::erase( $nl_email );
mt_wp_integration_assert( $erased['items_removed'] && null === $nl_subscribers::findByEmail( $nl_email ) && '0' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE subscriber_id = %d', $nl_deliveries, $nl_id ) ), 'Erasing personal data must delete the subscriber and their deliveries.' );

remove_filter( 'pre_wp_mail', $nl_capture, 10 );
remove_filter( 'home_url', $nl_https_url );
remove_filter( 'admin_url', $nl_https_url );
foreach ( array_keys( $nl_smtp_fixture ) as $nl_smtp_option ) {
	remove_filter( 'pre_option_' . $nl_smtp_option, $nl_smtp_filter, 10 );
}
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE email LIKE %s', $wpdb->prefix . 'mt_newsletter_subscribers', '%' . $wpdb->esc_like( $nl_tag ) . '@example.test' ) );
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE campaign_id IN (%d, %d, %d)', $nl_deliveries, $campaign_id, $second, $third ) );
$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE id IN (%d, %d, %d)', $wpdb->prefix . 'mt_newsletter_campaigns', $campaign_id, $second, $third ) );

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
