<?php
namespace MeTransfers\Booking;

use MeTransfers\Payments\Redsys\Gateway;
use MeTransfers\Pricing\Money;

final class ReceiptService {
    public function find( $raw_order_id, $token ) {
        if ( ! is_scalar( $raw_order_id ) || ! is_scalar( $token ) ) {
            return null;
        }

        $raw_order_id = (string) $raw_order_id;
        $order_id = preg_replace( '/[^0-9A-Za-z]/', '', $raw_order_id );
        if ( '' === $order_id
            || $raw_order_id !== $order_id
            || ! Gateway::verify_confirmation_token( $order_id, (string) $token ) ) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'wptb_bookings';
        $booking = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE payment_intent_id = %s LIMIT 1",
                $order_id
            )
        );

        if ( ! $booking
            || 'paid' !== (string) $booking->payment_status
            || ! in_array( (string) $booking->status, array( 'confirmed', 'completed' ), true ) ) {
            return null;
        }

        try {
            $money = Money::fromBooking( $booking );
        } catch ( \InvalidArgumentException $exception ) {
            return null;
        }

        $vehicle_name = '#' . (int) $booking->vehicle_id;
        if ( class_exists( '\WPTB_Vehicle_Manager' ) ) {
            $vehicle = \WPTB_Vehicle_Manager::get_vehicle( (int) $booking->vehicle_id );
            if ( $vehicle && ! empty( $vehicle->name ) ) {
                $vehicle_name = (string) $vehicle->name;
            }
        }

        return array(
            'booking_id'         => (int) $booking->id,
            'reference'          => $order_id,
            'booking_date'       => (string) $booking->booking_date,
            'booking_time'       => (string) $booking->booking_time,
            'origin'             => (string) $booking->origin,
            'destination'        => (string) $booking->destination,
            'distance_km'        => isset( $booking->distance_km ) ? (float) $booking->distance_km : 0.0,
            'duration_minutes'   => isset( $booking->duration_minutes ) ? (int) $booking->duration_minutes : 0,
            'price'              => $money->decimal(),
            'price_cents'        => $money->cents(),
            'currency'           => 'EUR',
            'customer_name'      => (string) $booking->customer_name,
            'passengers'         => max( 1, (int) $booking->passengers ),
            'suitcases'          => isset( $booking->suitcases ) ? max( 0, (int) $booking->suitcases ) : 0,
            'carry_ons'          => isset( $booking->carry_ons ) ? max( 0, (int) $booking->carry_ons ) : 0,
            'vehicle_name'       => $vehicle_name,
            'trip_type'          => 'round_trip' === (string) $booking->trip_type ? 'round_trip' : 'one_way',
            'return_origin'      => isset( $booking->return_pickup_address ) ? (string) $booking->return_pickup_address : '',
            'return_destination' => isset( $booking->return_dropoff_address ) ? (string) $booking->return_dropoff_address : '',
            'return_date'        => isset( $booking->return_date ) ? (string) $booking->return_date : '',
            'return_time'        => isset( $booking->return_time ) ? (string) $booking->return_time : '',
            'payment_method'     => isset( $booking->payment_method ) ? (string) $booking->payment_method : '',
            'locale'             => I18n::normalizeLanguage( isset( $booking->booking_locale ) ? $booking->booking_locale : 'es' ),
        );
    }

    public static function url( $order_id, $language = 'es' ) {
        $order_id = preg_replace( '/[^0-9A-Za-z]/', '', (string) $order_id );
        if ( '' === $order_id ) {
            throw new \InvalidArgumentException( 'Invalid receipt order identifier.' );
        }

        $language = I18n::normalizeLanguage( $language );
        $prefix = 'es' === $language ? '' : $language . '/';
        return home_url(
            '/' . $prefix . 'reservas-metransfers/'
            . '?mt_receipt=1&oid=' . rawurlencode( $order_id )
            . '&token=' . rawurlencode( Gateway::confirmation_token( $order_id ) )
        );
    }
}
