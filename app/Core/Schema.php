<?php
namespace MeTransfers\Core;

final class Schema {
	public static function installCurrent() {
		self::installMigrationJournal();
		self::installCoreTables();
		self::installEventTables();
		self::installFleetTables();
	}

	public static function installMigrationJournal() {
		global $wpdb;
		self::loadUpgradeApi();
		$table           = $wpdb->prefix . 'mt_schema_migrations';
		$charset_collate = $wpdb->get_charset_collate();
		self::apply(
			"CREATE TABLE $table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                migration_id varchar(191) NOT NULL,
                version varchar(32) NOT NULL,
                status varchar(20) NOT NULL,
                started_at datetime NOT NULL,
                finished_at datetime DEFAULT NULL,
                error_code varchar(32) DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY migration_id (migration_id),
                KEY status_started (status, started_at)
            ) $charset_collate;"
		);
	}

	public static function installCoreTables() {
		global $wpdb;
		self::loadUpgradeApi();
		$charset_collate = $wpdb->get_charset_collate();

		$table_bookings = $wpdb->prefix . 'wptb_bookings';
		self::apply(
			"CREATE TABLE $table_bookings (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                booking_date date NOT NULL,
                booking_time time NOT NULL,
                origin text NOT NULL,
                destination text NOT NULL,
                distance_km float,
                duration_minutes int,
                price decimal(10,2),
                price_cents bigint(20) unsigned DEFAULT NULL,
                customer_name varchar(150),
                customer_email varchar(150),
                customer_phone varchar(50),
                flight_number varchar(50),
                passengers int DEFAULT 1,
                suitcases int DEFAULT 0,
                carry_ons int DEFAULT 0,
                notes text,
                vehicle_id mediumint(9),
                trip_type varchar(20) DEFAULT 'one_way',
                return_pickup_address text,
                return_dropoff_address text,
                return_date date,
                return_time time,
                status varchar(20) DEFAULT 'pending',
                payment_method varchar(50),
                payment_intent_id varchar(255),
                payment_idempotency_key char(64) DEFAULT NULL,
                payment_status varchar(20) DEFAULT 'pending',
                booking_locale varchar(10) DEFAULT 'es',
                terms_accepted_at datetime DEFAULT NULL,
                terms_version varchar(50) DEFAULT NULL,
                analytics_client_id varchar(100) DEFAULT NULL,
                hotel_token varchar(255),
                hotel_id bigint(20) unsigned DEFAULT NULL,
                created_by_user_id bigint(20) unsigned DEFAULT NULL,
                source varchar(50) DEFAULT 'Metransfers',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY vehicle_id (vehicle_id),
                KEY booking_date (booking_date),
                KEY status (status),
                KEY status_booking_date (status, booking_date),
                KEY payment_status_created_at (payment_status, created_at),
                KEY vehicle_booking_date (vehicle_id, booking_date),
                KEY payment_intent_id (payment_intent_id),
                UNIQUE KEY payment_idempotency_key (payment_idempotency_key),
                KEY hotel_token (hotel_token),
                KEY hotel_id (hotel_id),
                KEY hotel_status_date (hotel_id, status, booking_date),
                KEY hotel_created_at (hotel_id, created_at),
                KEY created_by_user_id (created_by_user_id),
                KEY source (source),
                KEY origin (origin(50)),
                KEY destination (destination(50))
            ) $charset_collate;"
		);

		$table_backups = $wpdb->prefix . 'wptb_backups';
		self::apply(
			"CREATE TABLE $table_backups (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                filename varchar(255) NOT NULL,
                filepath text NOT NULL,
                type varchar(50) DEFAULT 'manual',
                status varchar(20) DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;"
		);
	}

	public static function installEventTables() {
		global $wpdb;
		self::loadUpgradeApi();
		$charset_collate = $wpdb->get_charset_collate();

		$table_analytics = $wpdb->prefix . 'mt_analytics_outbox';
		self::apply(
			"CREATE TABLE $table_analytics (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                event_name varchar(50) NOT NULL,
                event_key varchar(191) NOT NULL,
                payload longtext NOT NULL,
                attempts smallint unsigned NOT NULL DEFAULT 0,
                status varchar(20) NOT NULL DEFAULT 'pending',
                last_error text,
                locked_at datetime DEFAULT NULL,
                available_at datetime DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                sent_at datetime DEFAULT NULL,
                failed_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY event_key (event_key),
                KEY status_created_at (status, created_at),
                KEY status_available_at (status, available_at)
            ) $charset_collate;"
		);

		$table_outbox = $wpdb->prefix . 'mt_outbox';
		self::apply(
			"CREATE TABLE $table_outbox (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                event_key varchar(191) NOT NULL,
                event_type varchar(80) NOT NULL,
                aggregate_id bigint(20) unsigned NOT NULL,
                payload longtext NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                attempts smallint unsigned NOT NULL DEFAULT 0,
                available_at datetime NOT NULL,
                locked_at datetime DEFAULT NULL,
                last_error text,
                created_at datetime NOT NULL,
                processed_at datetime DEFAULT NULL,
                failed_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY event_key (event_key),
                KEY status_available (status, available_at)
            ) $charset_collate;"
		);

		$table_drafts = $wpdb->prefix . 'mt_booking_drafts';
		self::apply(
			"CREATE TABLE $table_drafts (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                token_hash char(64) NOT NULL,
                idempotency_key char(64) NOT NULL,
                payload longtext NOT NULL,
                payment_booking_id bigint(20) unsigned DEFAULT NULL,
                created_at datetime NOT NULL,
                expires_at datetime NOT NULL,
                consumed_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY token_hash (token_hash),
                UNIQUE KEY idempotency_key (idempotency_key),
                KEY expires_at (expires_at),
                KEY payment_booking_id (payment_booking_id)
            ) $charset_collate;"
		);

		$table_admin_audit = $wpdb->prefix . 'mt_admin_audit';
		self::apply(
			"CREATE TABLE $table_admin_audit (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                action_name varchar(80) NOT NULL,
                object_type varchar(50) NOT NULL DEFAULT '',
                object_id bigint(20) unsigned NOT NULL DEFAULT 0,
                context_json longtext NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY action_created (action_name, created_at),
                KEY actor_created (actor_user_id, created_at)
            ) $charset_collate;"
		);
	}

	public static function installFleetTables() {
		global $wpdb;
		self::loadUpgradeApi();
		$charset_collate = $wpdb->get_charset_collate();

		$table_types = $wpdb->prefix . 'wptb_vehicle_types';
		self::apply(
			"CREATE TABLE $table_types (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                slug varchar(100) NOT NULL,
                description text,
                icon varchar(255),
                display_order int DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY slug (slug)
            ) $charset_collate;"
		);

		$table_vehicles = $wpdb->prefix . 'wptb_vehicles';
		self::apply(
			"CREATE TABLE $table_vehicles (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(200) NOT NULL,
                vehicle_type_id mediumint(9) NOT NULL,
                description text,
                capacity int NOT NULL DEFAULT 4,
                luggage_capacity int DEFAULT 2,
                initial_fee decimal(10,2) DEFAULT 0,
                min_transfer_price decimal(10,2) DEFAULT 0,
                min_oneway_price decimal(10,2) DEFAULT 0,
                min_roundtrip_price decimal(10,2) DEFAULT 0,
                price_per_km_oneway decimal(10,2) DEFAULT 0,
                price_per_km_roundtrip decimal(10,2) DEFAULT 0,
                price_per_hour decimal(10,2) DEFAULT 0,
                is_active tinyint(1) DEFAULT 1,
                is_normal tinyint(1) DEFAULT 1,
                is_hotel tinyint(1) DEFAULT 1,
                display_order int DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY vehicle_type_id (vehicle_type_id),
                KEY is_active (is_active),
                KEY capacity (capacity)
            ) $charset_collate;"
		);

		$table_images = $wpdb->prefix . 'wptb_vehicle_images';
		self::apply(
			"CREATE TABLE $table_images (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                vehicle_id mediumint(9) NOT NULL,
                image_url varchar(500) NOT NULL,
                image_alt varchar(255),
                display_order int DEFAULT 0,
                is_primary tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY vehicle_id (vehicle_id),
                KEY is_primary (is_primary)
            ) $charset_collate;"
		);

		$table_hotel_vehicles = $wpdb->prefix . 'wptb_hotel_vehicles';
		self::apply(
			"CREATE TABLE $table_hotel_vehicles (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(200) NOT NULL,
                description text,
                capacity int NOT NULL DEFAULT 4,
                image_url varchar(500),
                display_order int DEFAULT 0,
                is_active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY is_active (is_active),
                KEY display_order (display_order)
            ) $charset_collate;"
		);
	}

	private static function loadUpgradeApi() {
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
	}

	private static function apply( $sql ) {
		global $wpdb;
		$wpdb->last_error = '';
		dbDelta( $sql );
		if ( ! empty( $wpdb->last_error ) ) {
			throw new \RuntimeException( 'Unable to apply schema definition.' );
		}
	}
}
