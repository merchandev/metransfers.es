<?php

namespace MeTransfers\HotelPortal\Access;

final class HotelAccess {

	const BLOCKED_META_KEY = 'mt_hotel_access_blocked';

	public static function canEnterPortal( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id
			&& ! self::isBlocked( $user_id )
			&& user_can( $user_id, \MeTransfers\Admin\Capabilities::HOTEL_PORTAL_ACCESS )
			&& ! empty( self::userHotelIds( $user_id ) );
	}

	public static function isBlocked( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id > 0 && '1' === (string) get_user_meta( $user_id, self::BLOCKED_META_KEY, true );
	}
	/**
	 * Determina si el usuario tiene acceso global a todos los hoteles publicados.
	 * Única definición de "supervisor Hotel" en el sistema.
	 */
	public static function hasGlobalAccess( int $user_id = 0 ): bool {
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		return user_can( $user_id, 'manage_options' )
			|| user_can( $user_id, \MeTransfers\Admin\Capabilities::HOTEL_ACCESS_ALL );
	}

	public static function userHotelIds( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}

		if ( self::hasGlobalAccess( $user_id ) ) {
			$ids = get_posts(
				array(
					'post_type'      => 'hotel_partner',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => 'title',
					'order'          => 'ASC',
					'no_found_rows'  => true,
				)
			);
		} else {
			$ids = get_user_meta( $user_id, 'mt_hotel_ids', true );
		}

		if ( ! is_array( $ids ) ) {
			$single_id = absint( $ids );
			$ids       = $single_id ? array( $single_id ) : array();
		}

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		return array_values(
			array_filter(
				$ids,
				static function ( $hotel_id ) {
					return 'hotel_partner' === get_post_type( $hotel_id )
						&& 'publish' === get_post_status( $hotel_id );
				}
			)
		);
	}

	public static function canAccessHotel( $hotel_id, $user_id = 0 ) {
		$hotel_id = absint( $hotel_id );
		return $hotel_id && in_array( $hotel_id, self::userHotelIds( $user_id ), true );
	}

	public static function requireHotel( $hotel_id, $user_id = 0 ) {
		if ( ! self::canAccessHotel( $hotel_id, $user_id ) ) {
			status_header( 403 );
			throw new \RuntimeException( 'hotel_access_denied' );
		}

		return absint( $hotel_id );
	}

	public static function canAccessBooking( $booking_id, $user_id = 0 ) {
		global $wpdb;
		$booking_id = absint( $booking_id );
		if ( ! $booking_id ) {
			return false;
		}

		$hotel_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT hotel_id FROM {$wpdb->prefix}wptb_bookings WHERE id = %d LIMIT 1",
				$booking_id
			)
		);

		return $hotel_id > 0 && self::canAccessHotel( $hotel_id, $user_id );
	}

	public static function activeHotelId( $user_id = 0 ) {
		$user_id   = $user_id ? absint( $user_id ) : get_current_user_id();
		$hotel_ids = self::userHotelIds( $user_id );
		if ( empty( $hotel_ids ) ) {
			return 0;
		}

		$preferred = absint( get_user_meta( $user_id, 'mt_primary_hotel_id', true ) );
		return in_array( $preferred, $hotel_ids, true ) ? $preferred : $hotel_ids[0];
	}
}
