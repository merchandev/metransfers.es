<?php

namespace MeTransfers\HotelPortal\Services;

use MeTransfers\Admin\AuditLog;
use MeTransfers\Admin\Capabilities;
use MeTransfers\HotelPortal\Access\HotelAccess;

final class BookingImporter {
	const NONCE_ACTION = 'mt_hotel_import_bookings';
	const MAX_BYTES    = 5242880;

	public static function process( $hotel_id ) {
		if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || empty( $_POST['mt_import_bookings'] ) ) {
			return array();
		}
		$hotel_id = HotelAccess::requireHotel( $hotel_id );
		if ( ! user_can( get_current_user_id(), Capabilities::HOTEL_IMPORT_BOOKINGS ) ) {
			return self::resultError( esc_html__( 'No tienes permiso para importar reservas.', 'me-transfers' ) );
		}
		$nonce = isset( $_POST['mt_import_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mt_import_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return self::resultError( esc_html__( 'La solicitud ha caducado. Inténtalo de nuevo.', 'me-transfers' ) );
		}
		$file = isset( $_FILES['booking_file'] ) && is_array( $_FILES['booking_file'] ) ? $_FILES['booking_file'] : array();
		if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE )
			|| empty( $file['tmp_name'] )
			|| ! is_uploaded_file( (string) $file['tmp_name'] )
			|| (int) ( $file['size'] ?? 0 ) > self::MAX_BYTES ) {
			return self::resultError( esc_html__( 'Selecciona un archivo válido de hasta 5 MB.', 'me-transfers' ) );
		}

		$extension = strtolower( pathinfo( sanitize_file_name( (string) ( $file['name'] ?? '' ) ), PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'xlsx', 'csv' ), true ) ) {
			return self::resultError( esc_html__( 'El archivo debe ser XLSX o CSV.', 'me-transfers' ) );
		}

		try {
			$rows = BookingSpreadsheetReader::read( (string) $file['tmp_name'], $extension );
			return self::importRows( $hotel_id, $rows );
		} catch ( \Throwable $error ) {
			return self::resultError( esc_html__( 'No se pudo leer el archivo. Comprueba que no esté dañado.', 'me-transfers' ) );
		}
	}

	public static function importRows( $hotel_id, array $rows ) {
		global $wpdb;
		$hotel_id = HotelAccess::requireHotel( $hotel_id );
		if ( count( $rows ) < 2 ) {
			return self::resultError( esc_html__( 'El archivo no contiene reservas.', 'me-transfers' ) );
		}
		$headers  = array_map( array( __CLASS__, 'normalizeHeader' ), array_shift( $rows ) );
		$map      = array_flip( $headers );
		$required = array( 'nombre_del_cliente', 'origen', 'destino', 'fecha_traslado', 'hora_traslado' );
		if ( array_diff( $required, array_keys( $map ) ) ) {
			return self::resultError( esc_html__( 'Faltan columnas obligatorias: cliente, origen, destino, fecha u hora del traslado.', 'me-transfers' ) );
		}

		$imported = 0;
		$skipped  = 0;
		$errors   = array();
		foreach ( $rows as $offset => $row ) {
			if ( ! array_filter( $row, static fn( $value ) => '' !== (string) $value ) ) {
				continue;
			}
			$record = self::recordFromRow( $row, $map, $hotel_id );
			if ( isset( $record['error'] ) ) {
				// translators: 1: spreadsheet row number, 2: validation error.
				$errors[] = sprintf( esc_html__( 'Fila %1$d: %2$s', 'me-transfers' ), $offset + 2, $record['error'] );
				continue;
			}
			if ( self::exists( $record ) ) {
				++$skipped;
				continue;
			}
			if ( false === $wpdb->insert( $wpdb->prefix . 'wptb_bookings', $record ) ) {
				// translators: %d is the spreadsheet row number.
				$errors[] = sprintf( esc_html__( 'Fila %d: no se pudo guardar.', 'me-transfers' ), $offset + 2 );
				continue;
			}
			++$imported;
		}

		AuditLog::record(
			'hotel.portal.bookings_imported',
			'hotel',
			$hotel_id,
			array(
				'imported' => $imported,
				'skipped'  => $skipped,
				'errors'   => count( $errors ),
			)
		);
		return array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => array_slice( $errors, 0, 20 ),
		);
	}

	private static function recordFromRow( array $row, array $map, $hotel_id ) {
		$get         = static function ( $key ) use ( $row, $map ) {
			return isset( $map[ $key ], $row[ $map[ $key ] ] ) ? trim( (string) $row[ $map[ $key ] ] ) : '';
		};
		$date        = self::dateValue( $get( 'fecha_traslado' ) );
		$time        = self::timeValue( $get( 'hora_traslado' ) );
		$name        = sanitize_text_field( $get( 'nombre_del_cliente' ) );
		$origin      = sanitize_text_field( $get( 'origen' ) );
		$destination = sanitize_text_field( $get( 'destino' ) );
		if ( ! $date || ! $time || '' === $name || '' === $origin || '' === $destination ) {
			return array( 'error' => esc_html__( 'datos obligatorios incompletos o fecha inválida.', 'me-transfers' ) );
		}

		$reference   = sanitize_text_field( $get( 'n_ref_id' ) );
		$vehicle     = sanitize_text_field( $get( 'vehiculo' ) );
		$notes       = sanitize_textarea_field( $get( 'notas_adicionales' ) );
		$metadata    = array_filter( array( $reference ? 'Ref. externa: ' . $reference : '', $vehicle ? 'Vehículo: ' . $vehicle : '', $notes ) );
		$price_cents = self::moneyCents( $get( 'precio' ) );

		return array(
			'booking_date'       => $date,
			'booking_time'       => $time,
			'origin'             => $origin,
			'destination'        => $destination,
			'distance_km'        => self::decimalValue( $get( 'distancia_km' ) ),
			'price'              => $price_cents / 100,
			'price_cents'        => $price_cents,
			'customer_name'      => $name,
			'customer_email'     => sanitize_email( $get( 'email' ) ),
			'customer_phone'     => sanitize_text_field( $get( 'telefono' ) ),
			'passengers'         => max( 1, absint( $get( 'pasajeros' ) ) ),
			'suitcases'          => absint( $get( 'equipaje' ) ),
			'flight_number'      => sanitize_text_field( $get( 'n_vuelo' ) ),
			'notes'              => implode( "\n", $metadata ),
			'trip_type'          => 'one_way',
			'status'             => self::statusValue( $get( 'estado' ) ),
			'payment_method'     => '',
			'payment_status'     => 'confirmed' === self::statusValue( $get( 'estado' ) ) ? 'paid' : 'pending',
			'hotel_token'        => get_post_meta( $hotel_id, '_hqp_token', true ),
			'hotel_id'           => absint( $hotel_id ),
			'created_by_user_id' => get_current_user_id(),
			'source'             => 'Importación Excel',
			'created_at'         => self::dateTimeValue( $get( 'hora_de_registro' ) ),
		);
	}

	private static function exists( array $record ) {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE hotel_id = %d AND booking_date = %s AND booking_time = %s AND customer_email = %s AND origin = %s AND destination = %s LIMIT 1',
				$wpdb->prefix . 'wptb_bookings',
				$record['hotel_id'],
				$record['booking_date'],
				$record['booking_time'],
				$record['customer_email'],
				$record['origin'],
				$record['destination']
			)
		);
	}

	private static function normalizeHeader( $header ) {
		$header = remove_accents( strtolower( trim( (string) $header ) ) );
		$header = str_replace( array( '€', '(', ')', '/', 'º', '°' ), '', $header );
		return trim( preg_replace( '/[^a-z0-9]+/', '_', $header ), '_' );
	}

	private static function dateValue( $value ) {
		if ( is_numeric( $value ) ) {
			return gmdate( 'Y-m-d', ( (int) $value - 25569 ) * 86400 );
		}
		$timestamp = strtotime( str_replace( '/', '-', (string) $value ) );
		return false === $timestamp ? '' : gmdate( 'Y-m-d', $timestamp );
	}

	private static function timeValue( $value ) {
		if ( is_numeric( $value ) && (float) $value < 1 ) {
			return gmdate( 'H:i:s', (int) round( (float) $value * 86400 ) );
		}
		$timestamp = strtotime( (string) $value );
		return false === $timestamp ? '' : gmdate( 'H:i:s', $timestamp );
	}

	private static function dateTimeValue( $value ) {
		if ( is_numeric( $value ) ) {
			return gmdate( 'Y-m-d H:i:s', (int) round( ( (float) $value - 25569 ) * 86400 ) );
		}
		$timestamp = strtotime( (string) $value );
		return false === $timestamp ? current_time( 'mysql' ) : gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	private static function decimalValue( $value ) {
		$value = preg_replace( '/[^0-9,.-]/', '', (string) $value );
		return (float) str_replace( ',', '.', $value );
	}

	private static function moneyCents( $value ) {
		return max( 0, (int) round( self::decimalValue( $value ) * 100 ) );
	}

	private static function statusValue( $value ) {
		$value = remove_accents( strtolower( trim( (string) $value ) ) );
		if ( false !== strpos( $value, 'confirm' ) ) {
			return 'confirmed';
		}
		if ( false !== strpos( $value, 'cancel' ) ) {
			return 'cancelled';
		}
		if ( false !== strpos( $value, 'pago' ) ) {
			return 'pending_payment';
		}
		if ( false !== strpos( $value, 'complet' ) ) {
			return 'completed';
		}
		return 'pending';
	}

	private static function resultError( $message ) {
		return array(
			'imported' => 0,
			'skipped'  => 0,
			'errors'   => array( $message ),
		);
	}
}
