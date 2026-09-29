<?php
namespace MeTransfers\Admin;

/**
 * Minimal .xlsx writer for admin exports: several sheets, typed cells (text,
 * integer, decimal, money, date, date-time), column widths, a frozen header
 * and one autofilter per sheet.
 *
 * Text is always written as an inline string, so customer input starting with
 * "=" can never become a formula when the file is opened.
 */
final class XlsxWorkbook {
	const TEXT          = 'text';
	const HEADER        = 'header';
	const TITLE         = 'title';
	const LABEL         = 'label';
	const INTEGER       = 'integer';
	const DECIMAL       = 'decimal';
	const MONEY         = 'money';
	const DATE          = 'date';
	const DATETIME      = 'datetime';
	const TOTAL         = 'total';
	const TOTAL_INTEGER = 'total_integer';
	const TOTAL_MONEY   = 'total_money';

	// Style name => index in the cellXfs list written by stylesXml().
	const STYLES = array(
		self::TEXT          => 0,
		self::HEADER        => 1,
		self::TITLE         => 2,
		self::LABEL         => 3,
		self::INTEGER       => 4,
		self::DECIMAL       => 5,
		self::MONEY         => 6,
		self::DATE          => 7,
		self::DATETIME      => 8,
		self::TOTAL         => 9,
		self::TOTAL_INTEGER => 10,
		self::TOTAL_MONEY   => 11,
	);

	const NUMERIC = array( self::INTEGER, self::DECIMAL, self::MONEY, self::TOTAL_INTEGER, self::TOTAL_MONEY );

	const MAX_TEXT  = 32767;
	const MAX_SHEET = 31;

	const XML_HEADER = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";

	/** @var array<int, array{name: string, widths: array<int, float>, rows: array<int, string>, freeze: int, filter: string}> */
	private $sheets = array();

	/**
	 * Adds a sheet and returns its index. The name is made valid and unique
	 * (Excel: 31 characters, none of : \ / ? * [ ], not "History").
	 *
	 * @param array<int, float|int> $widths Column widths in characters.
	 */
	public function addSheet( $name, array $widths = array() ) {
		$this->sheets[] = array(
			'name'   => $this->uniqueName( $name ),
			'widths' => $widths,
			'rows'   => array(),
			'freeze' => 0,
			'filter' => '',
		);
		return count( $this->sheets ) - 1;
	}

	public function sheetName( $sheet ) {
		return $this->sheets[ $sheet ]['name'];
	}

	/**
	 * Appends a row. Each cell is a plain value (strings as text, int and
	 * float as numbers, null or '' as an empty cell) or array( value, style ).
	 *
	 * @return int The 1-based row number written.
	 */
	public function addRow( $sheet, array $cells, $style = null ) {
		$number = count( $this->sheets[ $sheet ]['rows'] ) + 1;
		$xml    = '';
		foreach ( array_values( $cells ) as $column => $cell ) {
			$value      = is_array( $cell ) ? $cell[0] : $cell;
			$cell_style = is_array( $cell ) ? $cell[1] : $style;
			$xml       .= $this->cellXml( self::reference( $column, $number ), $value, $cell_style );
		}
		$this->sheets[ $sheet ]['rows'][] = '<row r="' . $number . '">' . $xml . '</row>';
		return $number;
	}

	public function rowCount( $sheet ) {
		return count( $this->sheets[ $sheet ]['rows'] );
	}

	/**
	 * Keeps the first $rows rows visible while scrolling.
	 */
	public function freezeRows( $sheet, $rows ) {
		$this->sheets[ $sheet ]['freeze'] = max( 0, (int) $rows );
	}

	/**
	 * Adds Excel's filter buttons to a table: header row through last row.
	 */
	public function autoFilter( $sheet, $header_row, $last_row, $columns ) {
		if ( $last_row <= $header_row || $columns < 1 ) {
			return;
		}
		$this->sheets[ $sheet ]['filter'] = self::reference( 0, $header_row ) . ':' . self::reference( $columns - 1, $last_row );
	}

	/**
	 * Writes the workbook to $path.
	 */
	public function save( $path ) {
		if ( ! class_exists( '\ZipArchive' ) ) {
			throw new \RuntimeException( 'ZipArchive is required to write .xlsx files.' );
		}
		if ( ! $this->sheets ) {
			$this->addSheet( 'Hoja 1' );
		}

		$zip = new \ZipArchive();
		if ( true !== $zip->open( $path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
			throw new \RuntimeException( 'Unable to create the .xlsx file.' );
		}
		$zip->addFromString( '[Content_Types].xml', $this->contentTypesXml() );
		$zip->addFromString( '_rels/.rels', self::XML_HEADER . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>' );
		$zip->addFromString( 'xl/workbook.xml', $this->workbookXml() );
		$zip->addFromString( 'xl/_rels/workbook.xml.rels', $this->workbookRelsXml() );
		$zip->addFromString( 'xl/styles.xml', self::stylesXml() );
		foreach ( $this->sheets as $index => $sheet ) {
			$zip->addFromString( 'xl/worksheets/sheet' . ( $index + 1 ) . '.xml', $this->sheetXml( $index, $sheet ) );
		}
		if ( ! $zip->close() ) {
			throw new \RuntimeException( 'Unable to write the .xlsx file.' );
		}
	}

	/**
	 * A1-style reference from a 0-based column and a 1-based row.
	 */
	public static function reference( $column, $row ) {
		$letters = '';
		for ( $n = $column + 1; $n > 0; $n = intdiv( $n - 1, 26 ) ) {
			$letters = chr( 65 + ( $n - 1 ) % 26 ) . $letters;
		}
		return $letters . $row;
	}

	/**
	 * Excel serial number for 'Y-m-d' or 'Y-m-d H:i:s', or null when empty or invalid.
	 */
	public static function serialDate( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value || 0 === strpos( $value, '0000-00-00' ) ) {
			return null;
		}
		$format = strlen( $value ) > 10 ? '!Y-m-d H:i:s' : '!Y-m-d';
		$date   = \DateTimeImmutable::createFromFormat( $format, $value, new \DateTimeZone( 'UTC' ) );
		if ( ! $date ) {
			return null;
		}
		return $date->getTimestamp() / 86400 + 25569;
	}

	private function cellXml( $reference, $value, $style ) {
		if ( null === $value || '' === $value || false === $value ) {
			return '';
		}

		$style = null === $style ? ( is_int( $value ) ? self::INTEGER : ( is_float( $value ) ? self::DECIMAL : self::TEXT ) ) : $style;
		$index = isset( self::STYLES[ $style ] ) ? self::STYLES[ $style ] : 0;

		if ( self::DATE === $style || self::DATETIME === $style ) {
			$serial = self::serialDate( $value );
			if ( null === $serial ) {
				return '';
			}
			// Full precision: Excel truncates to the minute, so 22:15:00 stored as 22:14:59.97 would show 22:14.
			$value = self::DATE === $style ? (string) floor( $serial ) : rtrim( rtrim( sprintf( '%.10F', $serial ), '0' ), '.' );
			return '<c r="' . $reference . '" s="' . $index . '"><v>' . $value . '</v></c>';
		}

		if ( in_array( $style, self::NUMERIC, true ) && is_numeric( $value ) ) {
			$whole  = in_array( $style, array( self::INTEGER, self::TOTAL_INTEGER ), true );
			$number = $whole ? (string) (int) $value : sprintf( '%.2F', (float) $value );
			return '<c r="' . $reference . '" s="' . $index . '"><v>' . $number . '</v></c>';
		}

		return '<c r="' . $reference . '" s="' . $index . '" t="inlineStr"><is><t xml:space="preserve">' . self::escape( $value ) . '</t></is></c>';
	}

	private static function escape( $text ) {
		$text = (string) $text;
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$text = (string) mb_convert_encoding( $text, 'UTF-8', 'UTF-8' );
		}
		// Characters XML 1.0 forbids (control codes pasted into notes) would corrupt the file.
		$text = (string) preg_replace( '/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text );
		if ( function_exists( 'mb_substr' ) && mb_strlen( $text, 'UTF-8' ) > self::MAX_TEXT ) {
			$text = mb_substr( $text, 0, self::MAX_TEXT, 'UTF-8' );
		}
		return htmlspecialchars( $text, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}

	private function uniqueName( $name ) {
		$name = trim( str_replace( array( ':', '\\', '/', '?', '*', '[', ']' ), ' ', (string) $name ), " '" );
		$name = (string) preg_replace( '/\s+/u', ' ', $name );
		if ( '' === $name || 'history' === strtolower( $name ) ) {
			$name = '' === $name ? 'Hoja' : $name . ' (hoja)';
		}

		$taken     = array_map( array( __CLASS__, 'lower' ), array_column( $this->sheets, 'name' ) );
		$candidate = mb_substr( $name, 0, self::MAX_SHEET );
		for ( $copy = 2; in_array( self::lower( $candidate ), $taken, true ); $copy++ ) {
			$suffix    = ' (' . $copy . ')';
			$candidate = rtrim( mb_substr( $name, 0, self::MAX_SHEET - strlen( $suffix ) ) ) . $suffix;
		}
		return $candidate;
	}

	// Excel compares sheet names without case.
	private static function lower( $text ) {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	}

	private function sheetXml( $index, array $sheet ) {
		$view = '<sheetView workbookViewId="0"' . ( 0 === $index ? ' tabSelected="1"' : '' ) . '>';
		if ( $sheet['freeze'] > 0 ) {
			$top   = self::reference( 0, $sheet['freeze'] + 1 );
			$view .= '<pane ySplit="' . $sheet['freeze'] . '" topLeftCell="' . $top . '" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="' . $top . '" sqref="' . $top . '"/>';
		}
		$view .= '</sheetView>';

		$cols = '';
		foreach ( array_values( $sheet['widths'] ) as $column => $width ) {
			$cols .= '<col min="' . ( $column + 1 ) . '" max="' . ( $column + 1 ) . '" width="' . sprintf( '%.2F', (float) $width ) . '" customWidth="1"/>';
		}

		return self::XML_HEADER
			. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
			. '<sheetViews>' . $view . '</sheetViews>'
			. '<sheetFormatPr defaultRowHeight="15"/>'
			. ( '' !== $cols ? '<cols>' . $cols . '</cols>' : '' )
			. '<sheetData>' . implode( '', $sheet['rows'] ) . '</sheetData>'
			. ( '' !== $sheet['filter'] ? '<autoFilter ref="' . $sheet['filter'] . '"/>' : '' )
			. '<pageMargins left="0.5" right="0.5" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
			. '</worksheet>';
	}

	private function workbookXml() {
		$sheets  = '';
		$filters = '';
		foreach ( $this->sheets as $index => $sheet ) {
			$sheets .= '<sheet name="' . self::escape( $sheet['name'] ) . '" sheetId="' . ( $index + 1 ) . '" r:id="rId' . ( $index + 1 ) . '"/>';
			if ( '' !== $sheet['filter'] ) {
				$range    = '$' . str_replace( ':', ':$', preg_replace( '/([A-Z]+)(\d+)/', '$1\$$2', $sheet['filter'] ) );
				$filters .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $index . '" hidden="1">'
					. self::escape( "'" . str_replace( "'", "''", $sheet['name'] ) . "'!" . $range ) . '</definedName>';
			}
		}

		return self::XML_HEADER
			. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
			. '<bookViews><workbookView/></bookViews>'
			. '<sheets>' . $sheets . '</sheets>'
			. ( '' !== $filters ? '<definedNames>' . $filters . '</definedNames>' : '' )
			. '</workbook>';
	}

	private function workbookRelsXml() {
		$relations = '';
		foreach ( array_keys( $this->sheets ) as $index ) {
			$relations .= '<Relationship Id="rId' . ( $index + 1 ) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ( $index + 1 ) . '.xml"/>';
		}
		$relations .= '<Relationship Id="rId' . ( count( $this->sheets ) + 1 ) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
		return self::XML_HEADER . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $relations . '</Relationships>';
	}

	private function contentTypesXml() {
		$overrides = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
			. '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
		foreach ( array_keys( $this->sheets ) as $index ) {
			$overrides .= '<Override PartName="/xl/worksheets/sheet' . ( $index + 1 ) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
		}
		return self::XML_HEADER
			. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
			. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
			. '<Default Extension="xml" ContentType="application/xml"/>'
			. $overrides
			. '</Types>';
	}

	// Order of <xf> entries must match STYLES.
	private static function stylesXml() {
		return self::XML_HEADER
			. '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<numFmts count="4">'
			. '<numFmt numFmtId="164" formatCode="#,##0.00\ &quot;€&quot;"/>'
			. '<numFmt numFmtId="165" formatCode="dd/mm/yyyy"/>'
			. '<numFmt numFmtId="166" formatCode="dd/mm/yyyy\ hh:mm"/>'
			. '<numFmt numFmtId="167" formatCode="#,##0.00"/>'
			. '</numFmts>'
			. '<fonts count="4">'
			. '<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
			. '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
			. '<font><b/><sz val="14"/><color rgb="FF0B1F35"/><name val="Calibri"/><family val="2"/></font>'
			. '<font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
			. '</fonts>'
			. '<fills count="4">'
			. '<fill><patternFill patternType="none"/></fill>'
			. '<fill><patternFill patternType="gray125"/></fill>'
			. '<fill><patternFill patternType="solid"><fgColor rgb="FF0B1F35"/><bgColor indexed="64"/></patternFill></fill>'
			. '<fill><patternFill patternType="solid"><fgColor rgb="FFE8EEF6"/><bgColor indexed="64"/></patternFill></fill>'
			. '</fills>'
			. '<borders count="2">'
			. '<border><left/><right/><top/><bottom/><diagonal/></border>'
			. '<border><left/><right/><top style="thin"><color rgb="FF0B1F35"/></top><bottom style="thin"><color rgb="FF0B1F35"/></bottom><diagonal/></border>'
			. '</borders>'
			. '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
			. '<cellXfs count="12">'
			. '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
			. '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
			. '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
			. '<xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
			. '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
			. '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
			. '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
			. '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
			. '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
			. '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>'
			. '<xf numFmtId="3" fontId="3" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>'
			. '<xf numFmtId="164" fontId="3" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>'
			. '</cellXfs>'
			. '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
			. '</styleSheet>';
	}
}
