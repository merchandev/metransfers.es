<?php
namespace MeTransfers\Booking;

use MeTransfers\Core\Outbox;

final class BookingEvents {
    public static function pending( $booking_id ) {
        $booking_id = absint( $booking_id );
        return Outbox::enqueue(
            'booking.pending',
            'booking.pending:' . $booking_id,
            $booking_id,
            array( 'booking_id' => $booking_id )
        );
    }

    public static function paid( $booking_id ) {
        $booking_id = absint( $booking_id );
        return Outbox::enqueue(
            'booking.paid',
            'booking.paid:' . $booking_id,
            $booking_id,
            array( 'booking_id' => $booking_id )
        );
    }

    public static function expand( $status, $booking_id ) {
        global $wpdb;
        $status = 'paid' === $status ? 'paid' : 'pending';
        $booking_id = absint( $booking_id );
        $booking = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT status, payment_status FROM {$wpdb->prefix}wptb_bookings WHERE id = %d",
                $booking_id
            )
        );
        if ( ! $booking ) {
            return false;
        }

        $is_paid = 'paid' === (string) $booking->payment_status
            && in_array( (string) $booking->status, array( 'confirmed', 'completed' ), true );
        if ( 'pending' === $status ) {
            if ( $is_paid || ! in_array( (string) $booking->status, array( 'pending', 'pending_payment', 'added-to-cart' ), true ) ) {
                return true;
            }
        }
        if ( 'paid' === $status && ! $is_paid ) {
            return false;
        }

        foreach ( self::channelEvents( $status, $booking_id ) as $event ) {
            if ( ! Outbox::enqueue( $event['type'], $event['key'], $booking_id, array( 'booking_id' => $booking_id ) ) ) {
                return false;
            }
        }
        return true;
    }

    public static function channelEvents( $status, $booking_id ) {
        $status = 'paid' === $status ? 'paid' : 'pending';
        $booking_id = absint( $booking_id );
        $channels = array( 'email.customer', 'email.admin', 'email.hotel' );
        if ( 'paid' === $status ) {
            $channels[] = 'whatsapp.admin';
            $channels[] = 'analytics.purchase';
        }

        $events = array();
        foreach ( $channels as $channel ) {
            $type = 'analytics.purchase' === $channel ? $channel : $channel . '.' . $status;
            $events[] = array(
                'type' => $type,
                'key'  => $type . ':' . $booking_id,
            );
        }
        return $events;
    }
}
