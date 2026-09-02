<?php
namespace MeTransfers\Core;

use MeTransfers\Analytics\PurchaseOutbox;
use MeTransfers\Booking\BookingEvents;
use MeTransfers\Notifications\NotificationService;

final class OutboxHandler {
	public static function handle( $event_type, $aggregate_id, $payload ) {
		unset( $payload );
		$aggregate_id = absint( $aggregate_id );
		if ( $aggregate_id <= 0 ) {
			return false;
		}

		if ( 'booking.pending' === $event_type ) {
			return BookingEvents::expand( 'pending', $aggregate_id );
		}
		if ( 'booking.paid' === $event_type ) {
			return BookingEvents::expand( 'paid', $aggregate_id );
		}

		$booking = self::booking( $aggregate_id );
		if ( ! $booking ) {
			return false;
		}

		if ( 0 === strpos( $event_type, 'email.' ) ) {
			$parts = explode( '.', $event_type );
			if ( 3 !== count( $parts )
				|| ! in_array( $parts[1], array( 'customer', 'admin', 'hotel' ), true )
				|| ! in_array( $parts[2], array( 'pending', 'paid' ), true ) ) {
				return false;
			}
			if ( ! self::statusMatches( $booking, $parts[2] ) ) {
				return 'pending' === $parts[2];
			}
			$status = 'paid' === $parts[2] ? 'confirmed' : 'pending';
			if ( 'customer' === $parts[1] ) {
				return NotificationService::sendCustomerEmail( $aggregate_id, $booking, $status );
			}
			if ( 'admin' === $parts[1] ) {
				return NotificationService::sendAdminEmail( $aggregate_id, $booking, $status );
			}
			return NotificationService::sendHotelEmail( $aggregate_id, $booking, $status );
		}

		if ( 'whatsapp.admin.paid' === $event_type ) {
			return self::statusMatches( $booking, 'paid' )
				&& NotificationService::sendWhatsapp( $aggregate_id, $booking );
		}

		if ( 'analytics.purchase' === $event_type ) {
			return self::statusMatches( $booking, 'paid' )
				&& PurchaseOutbox::deliverPurchase( $booking );
		}

		return false;
	}

	private static function booking( $booking_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wptb_bookings WHERE id = %d",
				$booking_id
			)
		);
	}

	private static function statusMatches( $booking, $expected ) {
		$paid = 'paid' === (string) $booking->payment_status
			&& in_array( (string) $booking->status, array( 'confirmed', 'completed' ), true );
		if ( 'paid' === $expected ) {
			return $paid;
		}
		return ! $paid
			&& in_array( (string) $booking->status, array( 'pending', 'pending_payment', 'added-to-cart' ), true );
	}
}
