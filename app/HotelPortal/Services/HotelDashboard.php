<?php

namespace MeTransfers\HotelPortal\Services;

use MeTransfers\HotelPortal\Access\HotelAccess;

final class HotelDashboard {
	public static function data( $hotel_id ) {
		global $wpdb;

		$hotel_id = HotelAccess::requireHotel( $hotel_id );
		$table    = $wpdb->prefix . 'wptb_bookings';
		$today    = current_time( 'Y-m-d' );
		$month    = current_time( 'Y-m-01' );

		$summary = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS total,
					SUM(CASE WHEN booking_date = %s THEN 1 ELSE 0 END) AS today_count,
					SUM(CASE WHEN booking_date >= %s AND status NOT IN ('cancelled', 'failed') THEN 1 ELSE 0 END) AS upcoming_count,
					SUM(CASE WHEN status IN ('pending', 'pending_payment', 'added-to-cart') THEN 1 ELSE 0 END) AS pending_count,
					SUM(CASE WHEN status IN ('confirmed', 'completed', 'processing') THEN 1 ELSE 0 END) AS confirmed_count,
					SUM(CASE WHEN booking_date >= %s AND status IN ('confirmed', 'completed', 'processing') THEN COALESCE(price_cents, ROUND(price * 100), 0) ELSE 0 END) AS month_revenue_cents
				FROM %i
				WHERE hotel_id = %d",
				$today,
				$today,
				$month,
				$table,
				$hotel_id
			)
		);

		$recent = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, booking_date, booking_time, customer_name, origin, destination, status, price, price_cents
				FROM %i
				WHERE hotel_id = %d
				ORDER BY created_at DESC, id DESC
				LIMIT 8',
				$table,
				$hotel_id
			)
		);

		return array(
			'total'               => isset( $summary->total ) ? (int) $summary->total : 0,
			'today'               => isset( $summary->today_count ) ? (int) $summary->today_count : 0,
			'upcoming'            => isset( $summary->upcoming_count ) ? (int) $summary->upcoming_count : 0,
			'pending'             => isset( $summary->pending_count ) ? (int) $summary->pending_count : 0,
			'confirmed'           => isset( $summary->confirmed_count ) ? (int) $summary->confirmed_count : 0,
			'month_revenue_cents' => isset( $summary->month_revenue_cents ) ? (int) $summary->month_revenue_cents : 0,
			'recent'              => is_array( $recent ) ? $recent : array(),
		);
	}

	public static function statusLabel( $status ) {
		$labels = array(
			'pending'         => esc_html__( 'Pendiente', 'me-transfers' ),
			'pending_payment' => esc_html__( 'Pago pendiente', 'me-transfers' ),
			'added-to-cart'   => esc_html__( 'En proceso', 'me-transfers' ),
			'processing'      => esc_html__( 'En proceso', 'me-transfers' ),
			'confirmed'       => esc_html__( 'Confirmada', 'me-transfers' ),
			'completed'       => esc_html__( 'Completada', 'me-transfers' ),
			'cancelled'       => esc_html__( 'Cancelada', 'me-transfers' ),
			'failed'          => esc_html__( 'Fallida', 'me-transfers' ),
		);
		$status = sanitize_key( (string) $status );
		return isset( $labels[ $status ] ) ? $labels[ $status ] : esc_html__( 'Sin estado', 'me-transfers' );
	}
}
