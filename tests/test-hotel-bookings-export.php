<?php
/**
 * «Exportar Excel por hotel»: Resumen plus one sheet per registered hotel,
 * bookings matched by hotel_id or legacy QR token, no secrets exported and a
 * well-formed .xlsx where customer text can never become a formula.
 */

define( 'ARRAY_A', 'ARRAY_A' );

function get_posts( array $args ) {
    return array(
        (object) array( 'ID' => 10, 'post_title' => 'Hotel Arts Barcelona', 'post_status' => 'publish', 'post_date' => '2026-01-15 09:30:00' ),
        (object) array( 'ID' => 11, 'post_title' => 'H10 Casanova', 'post_status' => 'publish', 'post_date' => '2026-02-01 10:00:00' ),
        (object) array( 'ID' => 12, 'post_title' => 'Hotel: Sin/Reservas*[Prueba] con un nombre larguísimo', 'post_status' => 'draft', 'post_date' => '2026-03-01 10:00:00' ),
        (object) array( 'ID' => 13, 'post_title' => 'h10 casanova', 'post_status' => 'publish', 'post_date' => '2026-04-01 10:00:00' ),
    );
}

function get_post_meta( $id, $key ) {
    $meta = array(
        10 => array( '_hqp_token' => 'HOTEL-ARTS-SECRET', '_hqp_hotel_address' => 'Carrer de la Marina 19', '_hqp_hotel_phone' => '+34 932 211 000', '_hqp_contact_name' => 'Recepción', '_hqp_contact_email' => 'arts@example.test', '_hqp_discount_percent' => '10' ),
        11 => array( '_hqp_token' => 'HOTEL-CASANOVA-SECRET' ),
    );
    return $meta[ $id ][ $key ] ?? '';
}

function get_userdata( $id ) {
    return 7 === $id ? (object) array( 'display_name' => 'Recepción Arts' ) : false;
}

class Fake_Export_Db {
    public $prefix = 'wp_';

    public function prepare( $sql, ...$args ) {
        return $sql;
    }

    public function get_results( $sql, $output ) {
        if ( false !== strpos( $sql, 'SELECT id, name' ) ) {
            return array( array( 'id' => '3', 'name' => 'MINI VAN «V» Class' ) );
        }
        return array(
            booking( array( 'id' => 501, 'hotel_id' => 10, 'status' => 'confirmed', 'payment_status' => 'paid', 'payment_method' => 'redsys', 'price_cents' => 11048, 'price' => '999.00', 'booking_date' => '2026-10-04', 'created_by_user_id' => 7 ) ),
            booking( array( 'id' => 502, 'hotel_id' => 10, 'status' => 'pending', 'price' => '80.00', 'booking_date' => '2026-09-01' ) ),
            // Hotel deleted after the booking was made.
            booking( array( 'id' => 505, 'hotel_id' => 99, 'hotel_token' => 'HOTEL-GONE', 'status' => 'confirmed', 'price' => '30.00', 'booking_date' => '2026-06-01' ) ),
            booking( array( 'id' => 504, 'hotel_id' => 11, 'status' => 'completed', 'payment_status' => 'paid', 'price' => '45.50', 'booking_date' => '2026-05-20', 'notes' => "=HYPERLINK(\"https://evil.test\",\"x\")\x07 fin", 'customer_phone' => '662024136' ) ),
            // Legacy QR booking: no hotel_id yet, matched by token.
            booking( array( 'id' => 503, 'hotel_id' => null, 'hotel_token' => 'HOTEL-ARTS-SECRET', 'status' => 'cancelled', 'price' => '60.00', 'booking_date' => '2026-01-01' ) ),
        );
    }
}

function booking( array $values ) {
    return $values + array(
        'hotel_id' => 0, 'hotel_token' => '', 'status' => 'pending', 'payment_status' => 'pending', 'payment_method' => '',
        'price' => '0', 'price_cents' => null, 'booking_date' => '2026-01-01', 'booking_time' => '12:00:00',
        'customer_name' => 'Cliente Prueba', 'customer_email' => 'cliente@example.test', 'customer_phone' => '+34 600 000 000',
        'passengers' => '5', 'suitcases' => '4', 'carry_ons' => '2', 'vehicle_id' => '3', 'trip_type' => 'one_way',
        'origin' => 'Aeropuerto BCN', 'destination' => 'H10 Casanova', 'distance_km' => '14.3', 'duration_minutes' => '25',
        'flight_number' => 'VY1234', 'return_date' => null, 'return_time' => null, 'return_pickup_address' => null,
        'return_dropoff_address' => null, 'notes' => '', 'source' => 'Hotel QR', 'created_by_user_id' => null,
        'booking_locale' => 'es', 'created_at' => '2026-09-28 22:15:00', 'payment_intent_id' => 'ORDER-1',
        'payment_idempotency_key' => 'IDEMPOTENCY-SECRET', 'analytics_client_id' => 'GA-CLIENT-SECRET',
    );
}

require_once __DIR__ . '/../app/Admin/XlsxWorkbook.php';
require_once __DIR__ . '/../app/Admin/HotelBookingsExport.php';

use MeTransfers\Admin\HotelBookingsExport;
use MeTransfers\Admin\XlsxWorkbook;

function assert_export( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

/**
 * @return array<string, array{type: string, value: string, style: string}>
 */
function sheet_cells( ZipArchive $zip, $number ) {
    $xml = $zip->getFromName( 'xl/worksheets/sheet' . $number . '.xml' );
    $doc = simplexml_load_string( $xml );
    assert_export( false !== $doc, "Sheet $number must be well-formed XML." );
    $cells = array();
    foreach ( $doc->sheetData->row as $row ) {
        foreach ( $row->c as $cell ) {
            $type = (string) $cell['t'];
            $cells[ (string) $cell['r'] ] = array(
                'type'  => $type,
                'value' => 'inlineStr' === $type ? (string) $cell->is->t : (string) $cell->v,
                'style' => (string) $cell['s'],
            );
        }
    }
    return $cells;
}

function column_values( array $cells, $column ) {
    $values = array();
    foreach ( $cells as $reference => $cell ) {
        if ( preg_match( '/^' . $column . '(\d+)$/', $reference ) ) {
            $values[] = $cell['value'];
        }
    }
    return $values;
}

function row_of( array $cells, $column, $value ) {
    foreach ( $cells as $reference => $cell ) {
        if ( preg_match( '/^' . $column . '(\d+)$/', $reference, $match ) && $value === $cell['value'] ) {
            return (int) $match[1];
        }
    }
    return 0;
}

$GLOBALS['wpdb'] = new Fake_Export_Db();

// 1. collect(): hotel_id first, legacy token second, secrets never leave the server.
$data = HotelBookingsExport::collect();
assert_export( 4 === count( $data['hotels'] ) && 5 === count( $data['bookings'] ), 'Every registered hotel and every hotel booking must be collected.' );
$hotel_of = array_column( $data['bookings'], 'export_hotel_id', 'id' );
ksort( $hotel_of );
assert_export( array( 501 => 10, 502 => 10, 503 => 10, 504 => 11, 505 => 0 ) === $hotel_of, 'Bookings must be matched by hotel_id, then by the QR token of older bookings.' );
$serialized = serialize( $data );
foreach ( array( 'HOTEL-ARTS-SECRET', 'HOTEL-CASANOVA-SECRET', 'IDEMPOTENCY-SECRET', 'GA-CLIENT-SECRET' ) as $secret ) {
    assert_export( false === strpos( $serialized, $secret ), "The export must not carry $secret." );
}
assert_export( 'MINI VAN «V» Class' === $data['bookings'][0]['vehicle_name'] && 'Recepción Arts' === $data['bookings'][0]['created_by'], 'Vehicle names and portal users must be resolved.' );

// 2. build() + save(): one workbook, Resumen first, then one sheet per hotel.
$path = tempnam( sys_get_temp_dir(), 'mt-xlsx' );
HotelBookingsExport::build( $data['hotels'], $data['bookings'], new DateTimeImmutable( '2026-09-29 10:30:00' ) )->save( $path );
$zip = new ZipArchive();
assert_export( true === $zip->open( $path ), 'The export must be a valid zip (.xlsx) file.' );
foreach ( array( '[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml' ) as $part ) {
    assert_export( false !== simplexml_load_string( $zip->getFromName( $part ) ), "$part must be well-formed XML." );
}
$workbook = simplexml_load_string( $zip->getFromName( 'xl/workbook.xml' ) );
$names    = array();
foreach ( $workbook->sheets->sheet as $sheet ) {
    $names[] = (string) $sheet['name'];
}
assert_export( array( 'Resumen', 'Hotel Arts Barcelona', 'H10 Casanova', 'Hotel Sin Reservas Prueba con u', 'h10 casanova (2)', 'Sin hotel registrado' ) === $names, 'Sheets must be Resumen, one per hotel (valid, unique Excel names) and the unmatched bookings: ' . implode( ' | ', $names ) );
assert_export( 6 === $zip->numFiles - 5, 'Each sheet must have its own worksheet part.' );

$all_xml = '';
for ( $i = 1; $i <= 6; $i++ ) {
    $all_xml .= $zip->getFromName( "xl/worksheets/sheet$i.xml" );
}
assert_export( false === strpos( $all_xml, '<f>' ), 'No cell may contain a formula.' );
assert_export( false === strpos( $all_xml, 'SECRET' ), 'No token or internal key may reach the file.' );

// 3. Resumen: totals per hotel and every booking with its hotel.
$summary = sheet_cells( $zip, 1 );
$arts    = row_of( $summary, 'A', 'Hotel Arts Barcelona' );
assert_export( $arts > 0, 'Resumen must list each hotel in the totals.' );
assert_export( array( '3', '1', '1', '1' ) === array( $summary[ "B$arts" ]['value'], $summary[ "C$arts" ]['value'], $summary[ "D$arts" ]['value'], $summary[ "E$arts" ]['value'] ), 'Hotel totals must count total, confirmed, pending and cancelled bookings.' );
assert_export( '110.48' === $summary[ "F$arts" ]['value'] && '110.48' === $summary[ "G$arts" ]['value'], 'Amounts must come from price_cents when present.' );
$total = row_of( $summary, 'A', 'TOTAL' );
assert_export( '5' === $summary[ "B$total" ]['value'] && '185.98' === $summary[ "F$total" ]['value'], 'The TOTAL row must add every hotel, including unmatched bookings.' );
$ids = array_values( array_filter( column_values( $summary, 'B' ), static function ( $id ) { return in_array( $id, array( '501', '502', '503', '504', '505' ), true ); } ) );
assert_export( array( '501', '502', '505', '504', '503' ) === $ids, 'Resumen must hold every hotel booking, newest service date first.' );
assert_export( 'Hotel eliminado (ID 99)' === $summary[ 'A' . row_of( $summary, 'B', '505' ) ]['value'], 'A booking of a deleted hotel must say so.' );
$sheet1 = simplexml_load_string( $zip->getFromName( 'xl/worksheets/sheet1.xml' ) );
assert_export( isset( $sheet1->autoFilter ) && 0 === strpos( (string) $sheet1->autoFilter['ref'], 'A' . ( row_of( $summary, 'B', '501' ) - 1 ) . ':' ), 'The bookings table in Resumen must have filter buttons on its header.' );
assert_export( false !== strpos( $zip->getFromName( 'xl/workbook.xml' ), '_xlnm._FilterDatabase' ), 'Excel needs the filter range as a defined name.' );

// 4. Hotel sheets: details, activity and only that hotel's bookings.
$arts_sheet = sheet_cells( $zip, 2 );
assert_export( 'Hotel Arts Barcelona' === $arts_sheet['A1']['value'] && 'Carrer de la Marina 19' === $arts_sheet[ 'B' . row_of( $arts_sheet, 'A', 'Dirección' ) ]['value'], 'A hotel sheet must start with the hotel details.' );
assert_export( '10.00' === $arts_sheet[ 'B' . row_of( $arts_sheet, 'A', 'Descuento (%)' ) ]['value'], 'The hotel discount must be exported.' );
assert_export( '5' === $arts_sheet[ 'B' . row_of( $arts_sheet, 'A', 'Pasajeros trasladados' ) ]['value'], 'Activity must count passengers of confirmed bookings only.' );
$arts_ids = array_values( array_filter( column_values( $arts_sheet, 'A' ), 'ctype_digit' ) );
assert_export( array( '501', '502', '503' ) === $arts_ids, 'A hotel sheet must list only its own bookings, including legacy QR ones.' );
$first = row_of( $arts_sheet, 'A', '501' );
assert_export( (string) floor( XlsxWorkbook::serialDate( '2026-10-04' ) ) === $arts_sheet[ "B$first" ]['value'] && '' === $arts_sheet[ "B$first" ]['type'], 'Service dates must be real Excel dates.' );
assert_export( '46023' === (string) floor( XlsxWorkbook::serialDate( '2026-01-01' ) ), 'Excel serial dates must count from 1899-12-30.' );
assert_export( 'Confirmada' === $arts_sheet[ "D$first" ]['value'] && 'Pagado' === $arts_sheet[ "E$first" ]['value'] && 'Tarjeta (Redsys)' === $arts_sheet[ "F$first" ]['value'], 'Statuses and payment methods must be readable.' );
assert_export( 'MINI VAN «V» Class' === $arts_sheet[ "N$first" ]['value'] && 'Recepción Arts' === $arts_sheet[ "AA$first" ]['value'], 'Vehicle and creator must appear by name.' );

$casanova = sheet_cells( $zip, 3 );
$row      = row_of( $casanova, 'A', '504' );
assert_export( 'inlineStr' === $casanova[ "Y$row" ]['type'] && 0 === strpos( $casanova[ "Y$row" ]['value'], '=HYPERLINK' ) && false === strpos( $casanova[ "Y$row" ]['value'], "\x07" ), 'Notes starting with "=" must stay text and control characters must be removed.' );
assert_export( 'inlineStr' === $casanova[ "J$row" ]['type'] && '662024136' === $casanova[ "J$row" ]['value'], 'Phone numbers must stay text.' );

$empty = sheet_cells( $zip, 4 );
assert_export( 'Borrador' === $empty[ 'B' . row_of( $empty, 'A', 'Estado del hotel' ) ]['value'], 'Hotels that are not published must still get their sheet.' );
assert_export( row_of( $empty, 'A', 'Este hotel todavía no tiene reservas.' ) > 0, 'A hotel without bookings must say so.' );
$orphans = sheet_cells( $zip, 6 );
assert_export( array( '505' ) === array_values( array_filter( column_values( $orphans, 'A' ), 'ctype_digit' ) ), 'Unmatched bookings must get their own sheet.' );

$zip->close();
unlink( $path );

// 5. Sheet names: Excel limits and reserved words.
$book = new XlsxWorkbook();
$book->addSheet( 'History' );
$book->addSheet( "'Quoted'" );
$book->addSheet( '' );
assert_export( 'History (hoja)' === $book->sheetName( 0 ) && 'Quoted' === $book->sheetName( 1 ) && 'Hoja' === $book->sheetName( 2 ), 'Reserved, quoted and empty sheet names must be made valid.' );
assert_export( 'AE12' === XlsxWorkbook::reference( 30, 12 ) && 'Z1' === XlsxWorkbook::reference( 25, 1 ) && 'AA1' === XlsxWorkbook::reference( 26, 1 ), 'Column letters must follow Excel.' );

echo "Hotel bookings export tests passed.\n";
