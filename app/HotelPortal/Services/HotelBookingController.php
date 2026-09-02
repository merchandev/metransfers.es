<?php

namespace MeTransfers\HotelPortal\Services;

use MeTransfers\Admin\Capabilities;
use MeTransfers\Booking\QuoteService;
use MeTransfers\HotelPortal\Access\HotelAccess;
use MeTransfers\Security\RequestRateLimiter;

final class HotelBookingController {
	const NONCE_ACTION = 'mt_hotel_booking_quote';

	public function register() {
		add_action( 'wp_ajax_mt_hotel_booking_quote', array( $this, 'quote' ) );
	}

	public function quote() {
		$nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'La solicitud ha caducado. Recarga la página.', 'me-transfers' ) ), 403 );
		}
		if ( ! user_can( get_current_user_id(), Capabilities::HOTEL_CREATE_BOOKINGS ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No tienes permiso para cotizar reservas.', 'me-transfers' ) ), 403 );
		}
		if ( ! RequestRateLimiter::consumeForIdentifier( 'hotel_booking_quote', 30, MINUTE_IN_SECONDS, 'user:' . get_current_user_id() ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Has realizado demasiadas consultas. Espera un minuto.', 'me-transfers' ) ), 429 );
		}

		try {
			HotelAccess::requireHotel( HotelContext::currentHotelId() );
			$quote = QuoteService::createVehicleList( self::quoteInput( $_POST ) );
		} catch ( \Throwable $error ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No se pudo calcular la ruta.', 'me-transfers' ) ), 400 );
		}
		if ( empty( $quote['valid'] ) ) {
			wp_send_json_error( array( 'message' => isset( $quote['error'] ) ? $quote['error'] : esc_html__( 'No hay vehículos disponibles.', 'me-transfers' ) ), 422 );
		}
		wp_send_json_success( $quote );
	}

	public static function quoteInput( $source ) {
		$source = is_array( $source ) ? $source : array();
		$input  = array();
		foreach ( array( 'booking_date', 'booking_time', 'origin', 'destination', 'trip_type', 'return_date', 'return_time', 'return_origin', 'return_destination' ) as $key ) {
			$input[ $key ] = isset( $source[ $key ] ) && is_scalar( $source[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $source[ $key ] ) ) : '';
		}
		$input['date'] = $input['booking_date'];
		$input['time'] = $input['booking_time'];
		foreach ( array( 'passengers', 'suitcases', 'carry_ons' ) as $key ) {
			$input[ $key ] = isset( $source[ $key ] ) && is_scalar( $source[ $key ] ) ? absint( $source[ $key ] ) : 0;
		}
		$input['language'] = 'es';
		return $input;
	}
}
