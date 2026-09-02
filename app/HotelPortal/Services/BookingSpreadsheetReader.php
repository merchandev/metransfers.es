<?php

namespace MeTransfers\HotelPortal\Services;

final class BookingSpreadsheetReader {
	const MAX_ROWS = 2000;

	public static function read( $path, $extension ) {
		return 'csv' === strtolower( (string) $extension ) ? self::readCsv( $path ) : self::readXlsx( $path );
	}

	private static function readCsv( $path ) {
		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) {
			throw new \RuntimeException( 'spreadsheet_unreadable' );
		}
		$first     = fgets( $handle );
		$delimiter = substr_count( (string) $first, ';' ) > substr_count( (string) $first, ',' ) ? ';' : ',';
		rewind( $handle );
		$rows = array();
		while ( count( $rows ) <= self::MAX_ROWS ) {
			$row = fgetcsv( $handle, 0, $delimiter );
			if ( false === $row ) {
				break;
			}
			$rows[] = array_map( 'strval', $row );
		}
		fclose( $handle );
		return $rows;
	}

	private static function readXlsx( $path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new \RuntimeException( 'xlsx_not_supported' );
		}
		$zip = new \ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			throw new \RuntimeException( 'spreadsheet_unreadable' );
		}

		$shared = self::sharedStrings( $zip );
		$xml    = self::zipEntry( $zip, 'xl/worksheets/sheet1.xml' );
		$zip->close();
		if ( false === $xml ) {
			throw new \RuntimeException( 'spreadsheet_unreadable' );
		}

		$document = new \DOMDocument();
		if ( ! $document->loadXML( $xml, LIBXML_NONET | LIBXML_NOBLANKS ) ) {
			throw new \RuntimeException( 'spreadsheet_unreadable' );
		}
		$xpath = new \DOMXPath( $document );
		$xpath->registerNamespace( 'x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main' );
		$rows = array();
		foreach ( $xpath->query( '//x:sheetData/x:row' ) as $row_node ) {
			if ( count( $rows ) >= self::MAX_ROWS + 1 ) {
				break;
			}
			$row = array();
			foreach ( $xpath->query( 'x:c', $row_node ) as $cell ) {
				if ( ! $cell instanceof \DOMElement ) {
					continue;
				}
				$reference = $cell->getAttribute( 'r' );
				$column    = self::columnIndex( $reference );
				$type      = $cell->getAttribute( 't' );
				$value     = '';
				if ( 'inlineStr' === $type ) {
					foreach ( $xpath->query( './/x:t', $cell ) as $text ) {
						// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
						$value .= $text->textContent;
					}
				} else {
					$value_node = $xpath->query( 'x:v', $cell )->item( 0 );
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
					$value = $value_node ? $value_node->textContent : '';
					if ( 's' === $type ) {
						$value = $shared[ (int) $value ] ?? '';
					}
				}
				$row[ $column ] = (string) $value;
			}
			if ( $row ) {
				$max    = max( array_keys( $row ) );
				$rows[] = array_replace( array_fill( 0, $max + 1, '' ), $row );
			}
		}
		return $rows;
	}

	private static function sharedStrings( \ZipArchive $zip ) {
		$xml = self::zipEntry( $zip, 'xl/sharedStrings.xml' );
		if ( false === $xml ) {
			return array();
		}
		$document = new \DOMDocument();
		if ( ! $document->loadXML( $xml, LIBXML_NONET | LIBXML_NOBLANKS ) ) {
			return array();
		}
		$values = array();
		foreach ( $document->getElementsByTagName( 'si' ) as $item ) {
			$value = '';
			foreach ( $item->getElementsByTagName( 't' ) as $text ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
				$value .= $text->textContent;
			}
			$values[] = $value;
		}
		return $values;
	}

	private static function zipEntry( \ZipArchive $zip, $name ) {
		$stat = $zip->statName( $name );
		if ( ! is_array( $stat ) || empty( $stat['size'] ) || (int) $stat['size'] > 26214400 ) {
			return false;
		}
		return $zip->getFromName( $name );
	}

	private static function columnIndex( $reference ) {
		$letters = preg_replace( '/[^A-Z]/', '', strtoupper( (string) $reference ) );
		$index   = 0;
		foreach ( str_split( $letters ) as $letter ) {
			$index = ( $index * 26 ) + ord( $letter ) - 64;
		}
		return max( 0, $index - 1 );
	}
}
