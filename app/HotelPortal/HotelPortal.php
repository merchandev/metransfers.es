<?php

namespace MeTransfers\HotelPortal;

use MeTransfers\HotelPortal\Auth\AdminRedirects;
use MeTransfers\HotelPortal\Auth\AuthController;
use MeTransfers\HotelPortal\Routing\Router;
use MeTransfers\HotelPortal\Services\HotelBookingController;
use MeTransfers\HotelPortal\Services\HotelContext;

final class HotelPortal {
	public static function isPortalRequest() {
		if ( '' !== (string) get_query_var( 'mt_hotel_portal', '' ) ) {
			return true;
		}

		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		return 1 === preg_match( '#^/hoteles(?:/|$)#', $request_path );
	}

	public function register() {
		$auth = new AuthController();
		( new Router( $auth ) )->register();
		( new AdminRedirects() )->register();
		( new HotelBookingController() )->register();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueAssets' ), 20 );
	}

	public function enqueueAssets() {
		if ( ! self::isPortalRequest() ) {
			return;
		}

		$css_path = get_template_directory() . '/assets/css/hotel-portal.css';
		$js_path  = get_template_directory() . '/assets/js/hotel-portal.js';
		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'mt-hotel-portal',
				get_template_directory_uri() . '/assets/css/hotel-portal.css',
				array(),
				(string) filemtime( $css_path )
			);
		}
		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'mt-hotel-portal',
				get_template_directory_uri() . '/assets/js/hotel-portal.js',
				array(),
				(string) filemtime( $js_path ),
				true
			);
			wp_localize_script(
				'mt-hotel-portal',
				'mtHotelPortal',
				array(
					'showPassword' => esc_html__( 'Mostrar', 'me-transfers' ),
					'hidePassword' => esc_html__( 'Ocultar', 'me-transfers' ),
				)
			);
		}

		if ( 'booking-new' !== sanitize_key( (string) get_query_var( 'mt_hotel_portal', '' ) ) ) {
			return;
		}
		$booking_path = get_template_directory() . '/assets/js/hotel-portal-booking.js';
		$dependencies = array();
		$api_key      = trim( (string) \MeTransfers\Core\Settings::get( 'google_maps_api_key', '' ) );
		if ( '' !== $api_key ) {
			wp_enqueue_script(
				'google-maps',
				add_query_arg(
					array(
						'key'       => $api_key,
						'libraries' => 'places,geometry',
						'language'  => 'es',
						'region'    => 'ES',
					),
					'https://maps.googleapis.com/maps/api/js'
				),
				array(),
				null,
				true
			);
			$dependencies[] = 'google-maps';
		}
		if ( file_exists( $booking_path ) ) {
			wp_enqueue_script( 'mt-hotel-portal-booking', get_template_directory_uri() . '/assets/js/hotel-portal-booking.js', $dependencies, (string) filemtime( $booking_path ), true );
			$hotel_id = HotelContext::currentHotelId();
			wp_localize_script(
				'mt-hotel-portal-booking',
				'mtHotelBooking',
				array(
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'nonce'        => wp_create_nonce( HotelBookingController::NONCE_ACTION ),
					'hotelAddress' => $hotel_id ? (string) get_post_meta( $hotel_id, '_hqp_hotel_address', true ) : '',
					'currency'     => 'EUR',
				)
			);
		}
	}
}
