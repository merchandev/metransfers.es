<?php

namespace MeTransfers\HotelPortal\Services;

use MeTransfers\HotelPortal\Access\HotelAccess;

final class HotelContext {
	public static function currentHotelId( $requested_hotel_id = 0, $user_id = 0 ) {
		$user_id            = $user_id ? absint( $user_id ) : get_current_user_id();
		$requested_hotel_id = absint( $requested_hotel_id );
		if ( $requested_hotel_id && HotelAccess::canAccessHotel( $requested_hotel_id, $user_id ) ) {
			return $requested_hotel_id;
		}

		$active_hotel_id = absint( get_user_meta( $user_id, 'mt_active_hotel_id', true ) );
		if ( $active_hotel_id && HotelAccess::canAccessHotel( $active_hotel_id, $user_id ) ) {
			return $active_hotel_id;
		}

		return HotelAccess::activeHotelId( $user_id );
	}

	public static function selectHotel( $hotel_id, $user_id = 0 ) {
		$user_id  = $user_id ? absint( $user_id ) : get_current_user_id();
		$hotel_id = absint( $hotel_id );
		if ( ! HotelAccess::canAccessHotel( $hotel_id, $user_id ) ) {
			return false;
		}
		if ( absint( get_user_meta( $user_id, 'mt_active_hotel_id', true ) ) === $hotel_id ) {
			return true;
		}

		return false !== update_user_meta( $user_id, 'mt_active_hotel_id', $hotel_id );
	}
}
