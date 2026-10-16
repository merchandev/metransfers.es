<?php
namespace MeTransfers\Newsletter;

use MeTransfers\HotelPortal\Services\BookingSpreadsheetReader;

/**
 * Imports subscribers from CSV or XLSX. Columns are recognised by their
 * header (email/correo, nombre/name, idioma/language); without a header the
 * first column with an @ is the email and the next one the name.
 *
 * Addresses that unsubscribed or bounced are never re-subscribed by an import.
 */
final class Importer {
	const MAX_ROWS   = 20000;
	const MAX_BYTES  = 10485760;
	const MAX_ERRORS = 50;

	const HEADERS = array(
		'email'    => array( 'email', 'e-mail', 'mail', 'correo', 'correo electronico', 'email address', 'direccion de correo' ),
		'name'     => array( 'nombre', 'name', 'nombre completo', 'full name', 'first name', 'nombre y apellidos', 'cliente' ),
		'language' => array( 'idioma', 'language', 'lang', 'lengua' ),
	);

	/**
	 * @param array<string, mixed> $file An entry of $_FILES.
	 * @return array<int, array<int, string>>
	 */
	public static function rowsFromUpload( array $file ) {
		$tmp  = (string) ( $file['tmp_name'] ?? '' );
		$name = (string) ( $file['name'] ?? '' );
		if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || '' === $tmp || ! is_uploaded_file( $tmp ) ) {
			throw new \RuntimeException( 'No se ha recibido ningún archivo.' );
		}
		if ( (int) ( $file['size'] ?? 0 ) > self::MAX_BYTES ) {
			throw new \RuntimeException( 'El archivo supera 10 MB.' );
		}
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'csv', 'txt', 'xlsx' ), true ) ) {
			throw new \RuntimeException( 'Formato no admitido: sube un archivo .csv o .xlsx.' );
		}
		try {
			return BookingSpreadsheetReader::read( $tmp, 'xlsx' === $extension ? 'xlsx' : 'csv', self::MAX_ROWS );
		} catch ( \RuntimeException $error ) {
			throw new \RuntimeException( 'No se ha podido leer el archivo. Comprueba que es un CSV o un Excel (.xlsx) válido.' );
		}
	}

	/**
	 * @param array<int, array<int, string>> $rows
	 * @return array{added: int, updated: int, unchanged: int, blocked: int, invalid: int, duplicates: int, errors: array<int, string>}
	 */
	public static function import( array $rows, $status, $language, $update_names, $consent_text, $defer = false ) {
		$report = array(
			'added'      => 0,
			'queued'     => 0,
			'error'      => 0,
			'updated'    => 0,
			'unchanged'  => 0,
			'blocked'    => 0,
			'invalid'    => 0,
			'duplicates' => 0,
			'errors'     => array(),
		);
		$rows   = array_values( array_map( array( __CLASS__, 'cleanRow' ), $rows ) );
		if ( ! $rows ) {
			return $report;
		}

		$columns = self::columns( $rows );
		if ( $columns['header'] ) {
			array_shift( $rows );
		}
		if ( null === $columns['email'] ) {
			$report['errors'][] = 'No se ha encontrado ninguna columna de correo electrónico.';
			return $report;
		}

		$seen = array();
		foreach ( $rows as $index => $row ) {
			$line  = $index + ( $columns['header'] ? 2 : 1 );
			$raw   = trim( (string) ( $row[ $columns['email'] ] ?? '' ) );
			$email = Subscribers::normalizeEmail( $raw );
			if ( '' === $email ) {
				if ( '' !== $raw || array_filter( $row, static fn( $cell ) => '' !== $cell ) ) {
					++$report['invalid'];
					self::error( $report, 'Fila ' . $line . ': correo no válido' . ( '' !== $raw ? ' («' . $raw . '»)' : '' ) . '.' );
				}
				continue;
			}
			if ( isset( $seen[ $email ] ) ) {
				++$report['duplicates'];
				continue;
			}
			$seen[ $email ] = true;

			$name   = null !== $columns['name'] ? (string) ( $row[ $columns['name'] ] ?? '' ) : '';
			$lang   = null !== $columns['language'] ? self::language( (string) ( $row[ $columns['language'] ] ?? '' ), $language ) : Settings::language( $language );
			$result = $defer ? ManualQueue::enqueue( $email, $name, $lang, $status, 'import', $consent_text ) : Subscribers::upsert( $email, $name, $lang, $status, 'import', $consent_text, $update_names );
			$result = 'duplicate' === $result ? 'duplicates' : $result;
			if ( 'blocked' === $result ) {
				self::error( $report, 'Fila ' . $line . ': ' . $email . ' se dio de baja o rebotó; no se ha vuelto a suscribir.' );
			}
			if ( isset( $report[ $result ] ) ) {
				++$report[ $result ];
			}
		}
		return $report;
	}

	/**
	 * @param array<int, array<int, string>> $rows
	 * @return array{header: bool, email: int|null, name: int|null, language: int|null}
	 */
	public static function columns( array $rows ) {
		$found = array(
			'header'   => false,
			'email'    => null,
			'name'     => null,
			'language' => null,
		);
		foreach ( $rows[0] as $index => $cell ) {
			$label = self::label( $cell );
			foreach ( self::HEADERS as $field => $names ) {
				if ( null === $found[ $field ] && in_array( $label, $names, true ) ) {
					$found[ $field ] = $index;
					$found['header'] = true;
				}
			}
		}
		if ( null !== $found['email'] ) {
			return $found;
		}

		// No usable header: the first column with an address in the first rows is the email.
		foreach ( array_slice( $rows, 0, 5 ) as $row ) {
			foreach ( $row as $index => $cell ) {
				if ( false !== strpos( (string) $cell, '@' ) ) {
					$found['email'] = $index;
					if ( null === $found['name'] && isset( $row[ $index + 1 ] ) ) {
						$found['name'] = $index + 1;
					}
					$found['header'] = $found['header'] || false === strpos( implode( '', $rows[0] ), '@' );
					return $found;
				}
			}
		}
		return $found;
	}

	public static function language( $value, $fallback ) {
		$value = self::label( $value );
		if ( in_array( $value, array( 'en', 'english', 'ingles', 'eng', 'en-us', 'en-gb', 'en_us', 'en_gb' ), true ) ) {
			return 'en';
		}
		if ( in_array( $value, array( 'es', 'espanol', 'spanish', 'castellano', 'es-es', 'es_es' ), true ) ) {
			return 'es';
		}
		return Settings::language( $fallback );
	}

	/**
	 * UTF-8 cells: a CSV saved by Excel on Windows is usually Windows-1252,
	 * and the first cell may start with a byte order mark.
	 *
	 * @param array<int, string> $row
	 * @return array<int, string>
	 */
	public static function cleanRow( $row ) {
		$clean = array();
		foreach ( (array) $row as $index => $cell ) {
			$cell = (string) $cell;
			if ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $cell, 'UTF-8' ) ) {
				$cell = (string) mb_convert_encoding( $cell, 'UTF-8', 'Windows-1252' );
			}
			$clean[ $index ] = trim( (string) preg_replace( '/^\xEF\xBB\xBF/', '', $cell ) );
		}
		return $clean;
	}

	private static function label( $cell ) {
		$label = strtolower( trim( (string) $cell ) );
		$label = function_exists( 'remove_accents' ) ? remove_accents( $label ) : $label;
		return trim( (string) preg_replace( '/[\s_]+/', ' ', $label ), ' :' );
	}

	private static function error( array &$report, $message ) {
		if ( count( $report['errors'] ) < self::MAX_ERRORS ) {
			$report['errors'][] = $message;
		}
	}
}
