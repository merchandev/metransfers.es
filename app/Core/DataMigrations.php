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
}
