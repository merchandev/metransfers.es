<?php

namespace MeTransfers\HotelPortal\Services;

use MeTransfers\Admin\AuditLog;
use MeTransfers\Admin\Capabilities;
use MeTransfers\Booking\QuoteService;
use MeTransfers\Booking\VehicleCapacityPolicy;
use MeTransfers\HotelPortal\Access\HotelAccess;
use MeTransfers\Pricing\Money;

final class HotelOperations {
	const NONCE_ACTION = 'mt_hotel_operations';

	public static function bookings( $hotel_id ) {
		global $wpdb;
		$hotel_id = HotelAccess::requireHotel( $hotel_id );
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$search   = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$page     = max( 1, isset( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1 );
		$where    = 'hotel_id = %d';
		$args     = array( $wpdb->prefix . 'wptb_bookings', $hotel_id );
		if ( in_array( $status, array( 'pending', 'pending_payment', 'confirmed', 'completed', 'cancelled' ), true ) ) {
			$where .= ' AND status = %s';
			$args[] = $status;
		}
		if ( '' !== $search ) {
			$where .= ' AND (customer_name LIKE %s OR customer_email LIKE %s OR origin LIKE %s OR destination LIKE %s)';
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$args   = array_merge( $args, array( $like, $like, $like, $like ) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Where and argument count are assembled from server-owned fragments.
		$count_sql = $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE {$where}", ...$args );
		$total     = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared immediately above.
		$args[]    = 20;
		$args[]    = ( $page - 1 ) * 20;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Where and argument count are assembled from server-owned fragments.
		$list_sql = $wpdb->prepare( "SELECT * FROM %i WHERE {$where} ORDER BY booking_date DESC, booking_time DESC, id DESC LIMIT %d OFFSET %d", ...$args );
		return array(
			'rows'   => (array) $wpdb->get_results( $list_sql ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared immediately above.
			'total'  => $total,
			'page'   => $page,
			'pages'  => max( 1, (int) ceil( $total / 20 ) ),
			'status' => $status,
			'search' => $search,
		);
	}

	public static function booking( $hotel_id, $booking_id ) {
		global $wpdb;
		$hotel_id   = HotelAccess::requireHotel( $hotel_id );
		$booking_id = absint( $booking_id );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d AND hotel_id = %d LIMIT 1', $wpdb->prefix . 'wptb_bookings', $booking_id, $hotel_id ) );
	}

	public static function createBooking( $hotel_id ) {
		global $wpdb;
		if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || empty( $_POST['mt_create_booking'] ) ) {
			return array();
		}
		$hotel_id = HotelAccess::requireHotel( $hotel_id );
		if ( ! user_can( get_current_user_id(), Capabilities::HOTEL_CREATE_BOOKINGS ) ) {
			return array( 'error' => esc_html__( 'No tienes permiso para crear reservas.', 'me-transfers' ) );
		}
		$nonce = isset( $_POST['mt_operations_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mt_operations_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return array( 'error' => esc_html__( 'La solicitud ha caducado.', 'me-transfers' ) );
		}
		$get         = static fn( $key ) => isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		$date        = $get( 'booking_date' );
		$time        = $get( 'booking_time' );
		$name        = $get( 'customer_name' );
		$origin      = $get( 'origin' );
		$destination = $get( 'destination' );
		$trip_type   = 'round_trip' === $get( 'trip_type' ) ? 'round_trip' : 'one_way';
		$vehicle_id  = absint( $get( 'vehicle_id' ) );
		$passengers  = max( 1, absint( $get( 'passengers' ) ) );
		$suitcases   = absint( $get( 'suitcases' ) );
		$carry_ons   = absint( $get( 'carry_ons' ) );
		$return_date = $get( 'return_date' );
		$return_time = $get( 'return_time' );
		$return_from = $get( 'return_origin' );
		$return_to   = $get( 'return_destination' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) || '' === $name || '' === $origin || '' === $destination || ! $vehicle_id ) {
			return array( 'error' => esc_html__( 'Completa correctamente los campos obligatorios.', 'me-transfers' ) );
		}
		$quote_input = array(
			'date'               => $date,
			'time'               => $time,
			'origin'             => $origin,
			'destination'        => $destination,
			'trip_type'          => $trip_type,
			'return_date'        => $return_date,
			'return_time'        => $return_time,
			'return_origin'      => $return_from,
			'return_destination' => $return_to,
			'vehicle_id'         => $vehicle_id,
			'passengers'         => $passengers,
			'suitcases'          => $suitcases,
			'carry_ons'          => $carry_ons,
			'language'           => 'es',
		);
		$quote       = QuoteService::create( $quote_input );
		if ( empty( $quote['valid'] ) ) {
			return array( 'error' => isset( $quote['error'] ) ? $quote['error'] : esc_html__( 'No se pudo validar la cotización.', 'me-transfers' ) );
		}
		$vehicle  = \WPTB_Vehicle_Manager::get_vehicle( $vehicle_id );
		$capacity = VehicleCapacityPolicy::validate( $vehicle, $passengers, $suitcases, $carry_ons, 'es' );
		if ( empty( $capacity['valid'] ) ) {
			return array( 'error' => $capacity['message'] );
		}
		$money        = new Money( (int) $quote['price_cents'] );
		$quoted_cents = absint( $get( 'quoted_price_cents' ) );
		if ( ! $quoted_cents || $quoted_cents !== $money->cents() ) {
			return array( 'error' => esc_html__( 'El precio se actualizó. Vuelve a calcular y revisa la cotización.', 'me-transfers' ) );
		}
		$data = array(
			'booking_date'           => $date,
			'booking_time'           => $time . ':00',
			'origin'                 => $origin,
			'destination'            => $destination,
			'distance_km'            => (float) $quote['total_distance_km'],
			'duration_minutes'       => (int) $quote['duration_minutes'],
			'price'                  => $money->decimalFloat(),
			'price_cents'            => $money->cents(),
			'customer_name'          => $name,
			'customer_email'         => sanitize_email( $get( 'customer_email' ) ),
			'customer_phone'         => $get( 'customer_phone' ),
			'passengers'             => $passengers,
			'suitcases'              => $suitcases,
			'carry_ons'              => $carry_ons,
			'flight_number'          => $get( 'flight_number' ),
			'notes'                  => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
			'vehicle_id'             => $vehicle_id,
			'trip_type'              => $trip_type,
			'return_pickup_address'  => 'round_trip' === $trip_type ? $return_from : '',
			'return_dropoff_address' => 'round_trip' === $trip_type ? $return_to : '',
			'return_date'            => 'round_trip' === $trip_type ? $return_date : null,
			'return_time'            => 'round_trip' === $trip_type ? $return_time . ':00' : null,
			'status'                 => 'pending',
			'payment_status'         => 'pending',
			'payment_method'         => '',
			'hotel_token'            => get_post_meta( $hotel_id, '_hqp_token', true ),
			'hotel_id'               => $hotel_id,
			'created_by_user_id'     => get_current_user_id(),
			'source'                 => 'Portal Hotel',
			'created_at'             => current_time( 'mysql' ),
		);
		if ( false === $wpdb->insert( $wpdb->prefix . 'wptb_bookings', $data ) ) {
			return array( 'error' => esc_html__( 'No se pudo guardar la reserva.', 'me-transfers' ) );
		}
		AuditLog::record( 'hotel.portal.booking_created', 'booking', (int) $wpdb->insert_id, array( 'hotel_id' => $hotel_id ) );
		return array(
			'success'    => esc_html__( 'Reserva creada correctamente.', 'me-transfers' ),
			'booking_id' => (int) $wpdb->insert_id,
		);
	}

	public static function customers( $hotel_id ) {
		global $wpdb;
		$hotel_id = HotelAccess::requireHotel( $hotel_id );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT customer_email, MAX(customer_name) customer_name, MAX(customer_phone) customer_phone, COUNT(*) bookings, MAX(booking_date) last_booking, SUM(CASE WHEN status IN ('confirmed','completed','processing') THEN COALESCE(price_cents, ROUND(price*100),0) ELSE 0 END) total_cents FROM %i WHERE hotel_id = %d AND customer_email <> '' GROUP BY customer_email ORDER BY last_booking DESC LIMIT 500", $wpdb->prefix . 'wptb_bookings', $hotel_id ) );
	}

	public static function statistics( $hotel_id ) {
		global $wpdb;
		$hotel_id = HotelAccess::requireHotel( $hotel_id );
		$monthly  = (array) $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(booking_date,'%%Y-%%m') period, COUNT(*) total, SUM(CASE WHEN status IN ('confirmed','completed','processing') THEN 1 ELSE 0 END) confirmed, SUM(CASE WHEN status IN ('confirmed','completed','processing') THEN COALESCE(price_cents, ROUND(price*100),0) ELSE 0 END) revenue_cents FROM %i WHERE hotel_id = %d AND booking_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY period ORDER BY period ASC", $wpdb->prefix . 'wptb_bookings', $hotel_id ) );
		$routes   = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT origin, destination, COUNT(*) total FROM %i WHERE hotel_id = %d GROUP BY origin, destination ORDER BY total DESC LIMIT 10', $wpdb->prefix . 'wptb_bookings', $hotel_id ) );
		return array(
			'monthly' => $monthly,
			'routes'  => $routes,
		);
	}

	public static function profile( $hotel_id ) {
		$hotel_id = HotelAccess::requireHotel( $hotel_id );
		$notice   = '';
		if ( 'POST' === strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) && ! empty( $_POST['mt_update_hotel'] ) ) {
			$nonce = isset( $_POST['mt_operations_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mt_operations_nonce'] ) ) : '';
			if ( wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				foreach ( array(
					'_hqp_hotel_address' => 'address',
					'_hqp_hotel_phone'   => 'phone',
					'_hqp_contact_name'  => 'contact_name',
				) as $meta => $field ) {
					update_post_meta( $hotel_id, $meta, isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '' );
				}
				update_post_meta( $hotel_id, '_hqp_contact_email', isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '' );
				AuditLog::record( 'hotel.portal.profile_updated', 'hotel', $hotel_id );
				$notice = esc_html__( 'Datos del hotel actualizados.', 'me-transfers' );
			}
		}
		return array(
			'name'          => get_the_title( $hotel_id ),
			'address'       => get_post_meta( $hotel_id, '_hqp_hotel_address', true ),
			'phone'         => get_post_meta( $hotel_id, '_hqp_hotel_phone', true ),
			'contact_name'  => get_post_meta( $hotel_id, '_hqp_contact_name', true ),
			'contact_email' => get_post_meta( $hotel_id, '_hqp_contact_email', true ),
			'token'         => get_post_meta( $hotel_id, '_hqp_token', true ),
			'notice'        => $notice,
			'qr_url'        => admin_url( 'admin-post.php?action=hqp_download_qr&post_id=' . $hotel_id . '&nonce=' . wp_create_nonce( 'hqp_download_qr_' . $hotel_id ) ),
			'flyer_url'     => admin_url( 'admin-post.php?action=hqp_download_flyer&post_id=' . $hotel_id . '&nonce=' . wp_create_nonce( 'hqp_download_flyer_' . $hotel_id ) ),
		);
	}
}
