<?php
namespace MeTransfers\Admin;

/**
 * «Exportar Excel por hotel»: one workbook with a «Resumen» sheet (totals per
 * hotel and every hotel booking) and one sheet per registered hotel with its
 * details, its activity and all of its bookings.
 */
final class HotelBookingsExport {
	const ACTION = 'mt_export_hotel_bookings';

	const CONFIRMED = array( 'confirmed', 'completed', 'processing' );
	const PENDING   = array( 'pending', 'pending_payment' );
	const CANCELLED = array( 'cancelled' );

	const STATUS_LABELS = array(
		'pending'         => 'Pendiente',
		'pending_payment' => 'Pago pendiente',
		'confirmed'       => 'Confirmada',
		'processing'      => 'En proceso',
		'completed'       => 'Completada',
		'cancelled'       => 'Cancelada',
	);

	const PAYMENT_LABELS = array(
		'paid'     => 'Pagado',
		'pending'  => 'Pendiente',
		'failed'   => 'Fallido',
		'refunded' => 'Reembolsado',
	);

	const METHOD_LABELS = array(
		'redsys'        => 'Tarjeta (Redsys)',
		'complimentary' => 'Sin cargo',
	);

	const HOTEL_STATUS_LABELS = array(
		'publish' => 'Activo',
		'draft'   => 'Borrador',
		'pending' => 'Pendiente de revisión',
		'private' => 'Privado',
		'future'  => 'Programado',
	);

	const ORPHANS = 'Sin hotel registrado';

	public function register() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'download' ) );
	}

	public static function url() {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION );
	}

	/**
	 * Button next to a wp-admin page title.
	 */
	public static function button() {
		if ( ! current_user_can( Capabilities::EXPORT_BOOKINGS ) ) {
			return;
		}
		echo '<a href="' . esc_url( self::url() ) . '" class="page-title-action">Exportar Excel por hotel</a>';
	}

	public static function download() {
		if ( ! current_user_can( Capabilities::EXPORT_BOOKINGS ) ) {
			wp_die( 'No tienes permisos suficientes.', '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );

		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$data = self::collect();
		$path = wp_tempnam( 'reservas-hoteles.xlsx' );
		try {
			self::build( $data['hotels'], $data['bookings'], current_datetime() )->save( $path );
		} catch ( \RuntimeException $error ) {
			wp_delete_file( $path );
			wp_die( 'No se pudo generar el Excel: ' . esc_html( $error->getMessage() ) );
		}

		AuditLog::record(
			'export.hotel_bookings',
			'booking',
			0,
			array(
				'hotels'   => count( $data['hotels'] ),
				'bookings' => count( $data['bookings'] ),
			)
		);

		// Stray output from other plugins would corrupt the zip.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="reservas-hoteles-' . wp_date( 'Y-m-d' ) . '.xlsx"' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming a server-generated temporary file.
		wp_delete_file( $path );
		exit;
	}

	/**
	 * Every registered hotel (not in the trash) and every booking linked to a
	 * hotel, by hotel_id or, for older QR bookings, by the hotel token.
	 *
	 * @return array{hotels: array<int, array>, bookings: array<int, array>}
	 */
	public static function collect() {
		global $wpdb;

		$hotels   = array();
		$by_token = array();
		$posts    = get_posts(
			array(
				'post_type'        => 'hotel_partner',
				'post_status'      => array_keys( self::HOTEL_STATUS_LABELS ),
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);
		foreach ( $posts as $post ) {
			$hotels[ (int) $post->ID ] = array(
				'id'            => (int) $post->ID,
				'name'          => '' !== trim( $post->post_title ) ? $post->post_title : 'Hotel ' . $post->ID,
				'status'        => $post->post_status,
				'created_at'    => $post->post_date,
				'address'       => (string) get_post_meta( $post->ID, '_hqp_hotel_address', true ),
				'phone'         => (string) get_post_meta( $post->ID, '_hqp_hotel_phone', true ),
				'contact_name'  => (string) get_post_meta( $post->ID, '_hqp_contact_name', true ),
				'contact_email' => (string) get_post_meta( $post->ID, '_hqp_contact_email', true ),
				'discount'      => (float) get_post_meta( $post->ID, '_hqp_discount_percent', true ),
			);
			$token                     = (string) get_post_meta( $post->ID, '_hqp_token', true );
			if ( '' !== $token ) {
				$by_token[ $token ] = (int) $post->ID;
			}
		}

		$vehicles = array();
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT id, name FROM %i', $wpdb->prefix . 'wptb_vehicles' ), ARRAY_A ) as $vehicle ) {
			$vehicles[ (int) $vehicle['id'] ] = (string) $vehicle['name'];
		}

		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE hotel_id > 0 OR (hotel_token IS NOT NULL AND hotel_token <> '') ORDER BY booking_date DESC, booking_time DESC, id DESC",
				$wpdb->prefix . 'wptb_bookings'
			),
			ARRAY_A
		);

		$users    = array();
		$bookings = array();
		foreach ( $rows as $row ) {
			$hotel_id = (int) $row['hotel_id'];
			if ( ! isset( $hotels[ $hotel_id ] ) ) {
				$token    = (string) $row['hotel_token'];
				$hotel_id = '' !== $token && isset( $by_token[ $token ] ) ? $by_token[ $token ] : 0;
			}

			$user_id = (int) $row['created_by_user_id'];
			if ( $user_id && ! isset( $users[ $user_id ] ) ) {
				$user              = get_userdata( $user_id );
				$users[ $user_id ] = $user ? $user->display_name : 'Usuario ' . $user_id;
			}

			// The token is a credential for the hotel's QR page: it never leaves the server.
			unset( $row['hotel_token'], $row['payment_idempotency_key'], $row['analytics_client_id'] );
			$row['export_hotel_id'] = $hotel_id;
			$row['missing_hotel']   = (int) $row['hotel_id'];
			$row['vehicle_name']    = $vehicles[ (int) $row['vehicle_id'] ] ?? '';
			$row['created_by']      = $user_id ? $users[ $user_id ] : '';
			$bookings[]             = $row;
		}

		return array(
			'hotels'   => array_values( $hotels ),
			'bookings' => $bookings,
		);
	}

	/**
	 * @param array<int, array> $hotels   From collect().
	 * @param array<int, array> $bookings From collect(), newest first.
	 */
	public static function build( array $hotels, array $bookings, \DateTimeInterface $generated_at ) {
		$book     = new XlsxWorkbook();
		$names    = array();
		$by_hotel = array();
		foreach ( $hotels as $hotel ) {
			$names[ $hotel['id'] ]    = $hotel['name'];
			$by_hotel[ $hotel['id'] ] = array();
		}
		foreach ( $bookings as $booking ) {
			$by_hotel[ (int) $booking['export_hotel_id'] ][] = $booking;
		}

		$summary = $book->addSheet( 'Resumen', array_merge( array( 32 ), self::widths() ) );
		$book->addRow( $summary, array( array( 'Reservas de hoteles · MeTransfers', XlsxWorkbook::TITLE ) ) );
		$book->addRow( $summary, array( array( 'Generado el', XlsxWorkbook::LABEL ), array( $generated_at->format( 'Y-m-d H:i:s' ), XlsxWorkbook::DATETIME ) ) );
		$book->addRow( $summary, array() );
		$book->addRow( $summary, array( array( 'Totales por hotel', XlsxWorkbook::LABEL ) ) );
		$book->addRow( $summary, self::header( array( 'Hotel', 'Reservas', 'Confirmadas o completadas', 'Pendientes', 'Canceladas', 'Importe confirmado (€)', 'Importe pagado (€)', 'Primera reserva', 'Última reserva' ) ) );

		$all = self::activity( $bookings );
		foreach ( $by_hotel as $hotel_id => $rows ) {
			if ( 0 === $hotel_id && ! $rows ) {
				continue;
			}
			$activity = self::activity( $rows );
			$book->addRow(
				$summary,
				array(
					0 === $hotel_id ? self::ORPHANS : $names[ $hotel_id ],
					$activity['total'],
					$activity['confirmed'],
					$activity['pending'],
					$activity['cancelled'],
					array( $activity['confirmed_amount'], XlsxWorkbook::MONEY ),
					array( $activity['paid_amount'], XlsxWorkbook::MONEY ),
					array( $activity['first'], XlsxWorkbook::DATE ),
					array( $activity['last'], XlsxWorkbook::DATE ),
				)
			);
		}
		$book->addRow(
			$summary,
			array(
				array( 'TOTAL', XlsxWorkbook::TOTAL ),
				array( $all['total'], XlsxWorkbook::TOTAL_INTEGER ),
				array( $all['confirmed'], XlsxWorkbook::TOTAL_INTEGER ),
				array( $all['pending'], XlsxWorkbook::TOTAL_INTEGER ),
				array( $all['cancelled'], XlsxWorkbook::TOTAL_INTEGER ),
				array( $all['confirmed_amount'], XlsxWorkbook::TOTAL_MONEY ),
				array( $all['paid_amount'], XlsxWorkbook::TOTAL_MONEY ),
			)
		);

		$book->addRow( $summary, array() );
		$book->addRow( $summary, array( array( 'Todas las reservas de hoteles', XlsxWorkbook::LABEL ) ) );
		$header = $book->addRow( $summary, self::header( array_merge( array( 'Hotel' ), self::columns() ) ) );
		foreach ( $bookings as $booking ) {
			$hotel_id = (int) $booking['export_hotel_id'];
			$hotel    = $hotel_id ? $names[ $hotel_id ] : self::orphanName( $booking );
			$book->addRow( $summary, array_merge( array( $hotel ), self::bookingCells( $booking ) ) );
		}
		$book->autoFilter( $summary, $header, $book->rowCount( $summary ), count( self::columns() ) + 1 );

		foreach ( $hotels as $hotel ) {
			self::hotelSheet( $book, $hotel, $by_hotel[ $hotel['id'] ] );
		}
		if ( ! empty( $by_hotel[0] ) ) {
			self::hotelSheet( $book, null, $by_hotel[0] );
		}

		return $book;
	}

	private static function hotelSheet( XlsxWorkbook $book, $hotel, array $bookings ) {
		$sheet    = $book->addSheet( $hotel ? $hotel['name'] : self::ORPHANS, self::widths() );
		$activity = self::activity( $bookings );

		if ( $hotel ) {
			$book->addRow( $sheet, array( array( $hotel['name'], XlsxWorkbook::TITLE ) ) );
			$details = array(
				array( 'Estado del hotel', self::HOTEL_STATUS_LABELS[ $hotel['status'] ] ?? $hotel['status'] ),
				array( 'Dirección', $hotel['address'] ),
				array( 'Teléfono', $hotel['phone'] ),
				array( 'Persona de contacto', $hotel['contact_name'] ),
				array( 'Email de contacto', $hotel['contact_email'] ),
				array( 'Descuento (%)', array( $hotel['discount'], XlsxWorkbook::DECIMAL ) ),
				array( 'Alta en MeTransfers', array( $hotel['created_at'], XlsxWorkbook::DATE ) ),
			);
		} else {
			$book->addRow( $sheet, array( array( self::ORPHANS, XlsxWorkbook::TITLE ) ) );
			$details = array( array( 'Nota', 'Reservas cuyo hotel se ha eliminado o ya no se puede identificar.' ) );
		}
		foreach ( $details as $detail ) {
			$book->addRow( $sheet, array( array( $detail[0], XlsxWorkbook::LABEL ), $detail[1] ) );
		}

		$book->addRow( $sheet, array() );
		$book->addRow( $sheet, array( array( 'Actividad', XlsxWorkbook::TITLE ) ) );
		$lines = array(
			array( 'Reservas totales', $activity['total'] ),
			array( 'Confirmadas o completadas', $activity['confirmed'] ),
			array( 'Pendientes (incluye pago pendiente)', $activity['pending'] ),
			array( 'Canceladas', $activity['cancelled'] ),
			array( 'Importe confirmado (€)', array( $activity['confirmed_amount'], XlsxWorkbook::MONEY ) ),
			array( 'Importe pagado (€)', array( $activity['paid_amount'], XlsxWorkbook::MONEY ) ),
			array( 'Pasajeros trasladados', $activity['passengers'] ),
			array( 'Kilómetros', array( $activity['km'], XlsxWorkbook::DECIMAL ) ),
			array( 'Primera reserva', array( $activity['first'], XlsxWorkbook::DATE ) ),
			array( 'Última reserva', array( $activity['last'], XlsxWorkbook::DATE ) ),
		);
		foreach ( $lines as $line ) {
			$book->addRow( $sheet, array( array( $line[0], XlsxWorkbook::LABEL ), $line[1] ) );
		}

		$book->addRow( $sheet, array() );
		$book->addRow( $sheet, array( array( 'Reservas', XlsxWorkbook::TITLE ) ) );
		if ( ! $bookings ) {
			$book->addRow( $sheet, array( 'Este hotel todavía no tiene reservas.' ) );
			return;
		}
		$header = $book->addRow( $sheet, self::header( self::columns() ) );
		foreach ( $bookings as $booking ) {
			$book->addRow( $sheet, self::bookingCells( $booking ) );
		}
		$book->autoFilter( $sheet, $header, $book->rowCount( $sheet ), count( self::columns() ) );
	}

	/**
	 * Confirmed revenue follows the Hotel Portal statistics: confirmed,
	 * completed and processing bookings.
	 */
	private static function activity( array $bookings ) {
		$activity = array(
			'total'            => count( $bookings ),
			'confirmed'        => 0,
			'pending'          => 0,
			'cancelled'        => 0,
			'confirmed_amount' => 0.0,
			'paid_amount'      => 0.0,
			'passengers'       => 0,
			'km'               => 0.0,
			'first'            => '',
			'last'             => '',
		);
		foreach ( $bookings as $booking ) {
			$status = (string) $booking['status'];
			$amount = self::amount( $booking );
			if ( in_array( $status, self::CONFIRMED, true ) ) {
				++$activity['confirmed'];
				$activity['confirmed_amount'] += $amount;
				$activity['passengers']       += (int) $booking['passengers'];
				$activity['km']               += (float) $booking['distance_km'];
			} elseif ( in_array( $status, self::PENDING, true ) ) {
				++$activity['pending'];
			} elseif ( in_array( $status, self::CANCELLED, true ) ) {
				++$activity['cancelled'];
			}
			if ( 'paid' === (string) $booking['payment_status'] ) {
				$activity['paid_amount'] += $amount;
			}

			$date = (string) $booking['booking_date'];
			if ( '' !== $date && 0 !== strpos( $date, '0000' ) ) {
				$activity['first'] = '' === $activity['first'] || $date < $activity['first'] ? $date : $activity['first'];
				$activity['last']  = $date > $activity['last'] ? $date : $activity['last'];
			}
		}
		$activity['confirmed_amount'] = round( $activity['confirmed_amount'], 2 );
		$activity['paid_amount']      = round( $activity['paid_amount'], 2 );
		$activity['km']               = round( $activity['km'], 2 );
		return $activity;
	}

	private static function amount( array $booking ) {
		return null !== $booking['price_cents'] && '' !== $booking['price_cents']
			? (int) $booking['price_cents'] / 100
			: (float) $booking['price'];
	}

	private static function columns() {
		return array(
			'Nº reserva',
			'Fecha del servicio',
			'Hora',
			'Estado',
			'Pago',
			'Método de pago',
			'Importe (€)',
			'Cliente',
			'Email',
			'Teléfono',
			'Pasajeros',
			'Maletas',
			'Equipaje de mano',
			'Vehículo',
			'Tipo de viaje',
			'Origen',
			'Destino',
			'Distancia (km)',
			'Duración (min)',
			'Nº de vuelo',
			'Fecha de vuelta',
			'Hora de vuelta',
			'Recogida de vuelta',
			'Destino de vuelta',
			'Notas',
			'Canal',
			'Creada por',
			'Idioma',
			'Creada el',
			'Ref. de pago',
		);
	}

	private static function widths() {
		return array( 11, 14, 8, 15, 12, 14, 12, 26, 30, 17, 10, 9, 10, 24, 13, 42, 42, 12, 12, 12, 14, 10, 36, 36, 45, 14, 20, 9, 17, 22 );
	}

	private static function header( array $titles ) {
		return array_map(
			static function ( $title ) {
				return array( $title, XlsxWorkbook::HEADER );
			},
			$titles
		);
	}

	private static function bookingCells( array $booking ) {
		$status  = (string) $booking['status'];
		$payment = (string) $booking['payment_status'];
		return array(
			(int) $booking['id'],
			array( $booking['booking_date'], XlsxWorkbook::DATE ),
			self::time( $booking['booking_time'] ),
			self::STATUS_LABELS[ $status ] ?? $status,
			self::PAYMENT_LABELS[ $payment ] ?? $payment,
			self::METHOD_LABELS[ (string) $booking['payment_method'] ] ?? (string) $booking['payment_method'],
			array( self::amount( $booking ), XlsxWorkbook::MONEY ),
			(string) $booking['customer_name'],
			(string) $booking['customer_email'],
			(string) $booking['customer_phone'],
			(int) $booking['passengers'],
			(int) $booking['suitcases'],
			(int) $booking['carry_ons'],
			(string) $booking['vehicle_name'],
			'round_trip' === $booking['trip_type'] ? 'Ida y vuelta' : 'Solo ida',
			(string) $booking['origin'],
			(string) $booking['destination'],
			array( (float) $booking['distance_km'], XlsxWorkbook::DECIMAL ),
			(int) $booking['duration_minutes'],
			(string) $booking['flight_number'],
			array( (string) $booking['return_date'], XlsxWorkbook::DATE ),
			self::time( $booking['return_time'] ),
			(string) $booking['return_pickup_address'],
			(string) $booking['return_dropoff_address'],
			(string) $booking['notes'],
			(string) $booking['source'],
			(string) $booking['created_by'],
			'en' === $booking['booking_locale'] ? 'Inglés' : 'Español',
			array( (string) $booking['created_at'], XlsxWorkbook::DATETIME ),
			(string) $booking['payment_intent_id'],
		);
	}

	private static function time( $value ) {
		return substr( (string) $value, 0, 5 );
	}

	private static function orphanName( array $booking ) {
		return $booking['missing_hotel'] ? 'Hotel eliminado (ID ' . $booking['missing_hotel'] . ')' : self::ORPHANS;
	}
}
