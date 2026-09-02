<?php

namespace MeTransfers\HotelPortal\Services;

final class HotelBookingAttribution {
	public static function forQrBooking( $hotel_id, $hotel_token ) {
		return array(
			'hotel_token'        => sanitize_text_field( (string) $hotel_token ),
			'hotel_id'           => absint( $hotel_id ),
			'created_by_user_id' => null,
			'source'             => 'Hotel QR',
		);
	}
}
