<?php
namespace MeTransfers\Core;

final class DataMigrations {
	public static function backfillPriceCents() {
		global $wpdb;
		$table  = $wpdb->prefix . 'wptb_bookings';
		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE %i
             SET price_cents = CAST(ROUND(price * 100) AS UNSIGNED)
				 WHERE price_cents IS NULL AND price IS NOT NULL AND price >= 0',
				$table
			)
		);
		if ( false === $result ) {
			throw new \RuntimeException( 'Unable to backfill booking price cents.' );
		}
	}

	public static function ensureBookingIdFloor() {
		global $wpdb;
		$table  = $wpdb->prefix . 'wptb_bookings';
		$max_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(id) FROM %i', $table ) );
		if ( $max_id < 10000 && false === $wpdb->query( $wpdb->prepare( 'ALTER TABLE %i AUTO_INCREMENT = 10000', $table ) ) ) {
			throw new \RuntimeException( 'Unable to enforce the booking identifier floor.' );
		}
	}

	public static function backfillHotelBookingRelations() {
		global $wpdb;
		$bookings  = $wpdb->prefix . 'wptb_bookings';
		$hotel_ids = get_posts(
			array(
				'post_type'      => 'hotel_partner',
				'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $hotel_ids as $hotel_id ) {
			$hotel_id = absint( $hotel_id );
			$token    = trim( (string) get_post_meta( $hotel_id, '_hqp_token', true ) );
			if ( ! $hotel_id || '' === $token ) {
				continue;
			}

			$result = $wpdb->query(
				$wpdb->prepare(
					"UPDATE %i
                     SET hotel_id = %d,
                         source = CASE
                             WHEN source IS NULL OR source = '' OR source = 'Metransfers' THEN 'Hotel QR'
                             ELSE source
                         END
                     WHERE hotel_id IS NULL
                       AND hotel_token = %s",
					$bookings,
					$hotel_id,
					$token
				)
			);

			if ( false === $result ) {
				throw new \RuntimeException( 'Unable to backfill hotel booking relationships.' );
			}
		}
	}

	public static function backfillHotelUserAssignments() {

		$user_ids = get_users(
			array(
				'role'   => 'check_hoteles',
				'fields' => 'ID',
			)
		);

		foreach ( $user_ids as $user_id ) {
			$user_id  = absint( $user_id );
			$existing = get_user_meta( $user_id, 'mt_hotel_ids', true );
			if ( is_array( $existing ) && ! empty( $existing ) ) {
				continue;
			}

			$hotel_ids = get_posts(
				array(
					'post_type'      => 'hotel_partner',
					'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
					'author'         => $user_id,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			$hotel_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $hotel_ids ) ) ) );

			if ( ! empty( $hotel_ids ) ) {
				update_user_meta( $user_id, 'mt_hotel_ids', $hotel_ids );
			}
		}
	}

	public static function repairHotelBookingRelations() {

		global $wpdb;
		$table     = $wpdb->prefix . 'wptb_bookings';
		$hotel_ids = get_posts(
			array(
				'post_type'      => 'hotel_partner',
				'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $hotel_ids as $hotel_id ) {
			$hotel_id  = absint( $hotel_id );
				$token = trim( (string) get_post_meta( $hotel_id, '_hqp_token', true ) );
			if ( ! $hotel_id || '' === $token ) {
				continue;
			}
			$result = $wpdb->query(
				$wpdb->prepare(
					'UPDATE %i SET hotel_id = %d WHERE hotel_token = %s AND (hotel_id IS NULL OR hotel_id <> %d)',
					$table,
					$hotel_id,
					$token,
					$hotel_id
				)
			);
			if ( false === $result ) {
				throw new \RuntimeException( 'Unable to repair hotel booking relationships.' );
			}
		}
	}

	public static function assignKnownHotelUsers() {

			$assignments = array(
				'alenti@metransfers.es'           => array( 'alenti' ),
				'andantedrassanes@metransfers.es' => array( 'andantedrassanes' ),
				'casanova@metransfers.es'         => array( 'casanova' ),
				'catalonia@metransfers.es'        => array( 'catalonia' ),
				'costabrava@metransfers.es'       => array( 'costabrava' ),
				'dantebarcelona@metransfers.es'   => array( 'dantebarcelona' ),
				'fenalsgarden@metransfers.es'     => array( 'fenalsgarden' ),
				'flamingo@metransfers.es'         => array( 'flamingo' ),
				'galeon@metransfers.es'           => array( 'galeon' ),
				'garbi@metransfers.es'            => array( 'garbi' ),
				'lespalmeres@metransfers.es'      => array( 'lespalmeres', 'lespalmares' ),
				'maldasingular@metransfers.es'    => array( 'maldasingular' ),
				'montserrat@metransfers.es'       => array( 'montserrat', 'monserrat' ),
				'sirius@metransfers.es'           => array( 'sirius' ),
				'tahiti@metransfers.es'           => array( 'tahiti' ),
				'urquinaonaplaza@metransfers.es'  => array( 'urquinaonaplaza' ),
			);
			$hotels      = get_posts(
				array(
					'post_type'      => 'hotel_partner',
					'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
					'posts_per_page' => -1,
					'no_found_rows'  => true,
				)
			);

		foreach ( $assignments as $email => $needles ) {
			$user = get_user_by( 'email', $email );
			if ( ! $user instanceof \WP_User ) {
				continue;
			}
			$hotel_id = self::matchingHotelId( $hotels, $needles );
			if ( $hotel_id ) {
				update_user_meta( (int) $user->ID, 'mt_hotel_ids', array( $hotel_id ) );
				update_user_meta( (int) $user->ID, 'mt_primary_hotel_id', $hotel_id );
			}
		}

		$supervisor = get_user_by( 'email', 'check@metransfers.es' );
		if ( $supervisor instanceof \WP_User ) {
			$supervisor->add_cap( \MeTransfers\Admin\Capabilities::HOTEL_ACCESS_ALL );
			$supervisor->add_cap( \MeTransfers\Admin\Capabilities::HOTEL_IMPORT_BOOKINGS );
		}
	}

	private static function matchingHotelId( array $hotels, array $needles ) {

		foreach ( $hotels as $hotel ) {
			$title = remove_accents( strtolower( (string) $hotel->post_title ) );
			$title = preg_replace( '/[^a-z0-9]+/', '', $title );
			foreach ( $needles as $needle ) {
				if ( false !== strpos( $title, $needle ) ) {
					return absint( $hotel->ID );
				}
			}
		}
		return 0;
	}
}
