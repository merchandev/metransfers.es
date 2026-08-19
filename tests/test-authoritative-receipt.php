<?php

function assert_receipt( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function wp_salt( $scheme = 'auth' ) {
    return 'receipt-test-salt-' . $scheme;
}

function home_url( $path = '/' ) {
    return 'https://example.test' . $path;
}

class WPTB_Vehicle_Manager {
    public static function get_vehicle( $vehicle_id ) {
        return 7 === (int) $vehicle_id ? (object) array( 'name' => 'Executive Van' ) : null;
    }
}

class Receipt_WPDB_Mock {
    public $prefix = 'wp_';
    public $booking;
    public $prepared_order = '';

    public function prepare( $query, $order_id ) {
        $this->prepared_order = $order_id;
        return $query;
    }

    public function get_row( $query ) {
        return $this->booking;
    }
}

require_once __DIR__ . '/../app/Pricing/Money.php';
require_once __DIR__ . '/../app/Booking/I18n.php';
require_once __DIR__ . '/../app/Payments/Redsys/Gateway.php';
require_once __DIR__ . '/../app/Booking/ReceiptService.php';

use MeTransfers\Booking\ReceiptService;
use MeTransfers\Payments\Redsys\Gateway;

$order_id = '000000000123';
$token = Gateway::confirmation_token( $order_id );
$booking = (object) array(
    'id'                      => 123,
    'payment_intent_id'       => $order_id,
    'status'                  => 'confirmed',
    'payment_status'          => 'paid',
    'booking_date'            => '2026-09-01',
    'booking_time'            => '10:30:00',
    'origin'                  => 'Barcelona',
    'destination'             => 'Girona',
    'distance_km'             => 101.4,
    'duration_minutes'        => 85,
    'price'                   => '999.99',
    'price_cents'             => 18500,
    'customer_name'           => 'Test Customer',
    'passengers'              => 3,
    'suitcases'               => 2,
    'carry_ons'               => 1,
    'vehicle_id'              => 7,
    'trip_type'               => 'round_trip',
    'return_pickup_address'   => 'Girona',
    'return_dropoff_address'  => 'Barcelona',
    'return_date'             => '2026-09-05',
    'return_time'             => '17:30:00',
    'payment_method'          => 'redsys',
    'booking_locale'          => 'en',
);

$wpdb = new Receipt_WPDB_Mock();
$wpdb->booking = $booking;
$GLOBALS['wpdb'] = $wpdb;

$service = new ReceiptService();
$receipt = $service->find( $order_id, $token );
assert_receipt( is_array( $receipt ), 'A valid token and paid final booking must resolve a receipt.' );
assert_receipt( 18500 === $receipt['price_cents'] && '185.00' === $receipt['price'], 'Receipt money must come from authoritative stored cents.' );
assert_receipt( 'Executive Van' === $receipt['vehicle_name'], 'Receipt vehicle data must be resolved server-side.' );
assert_receipt( 'en' === $receipt['locale'] && $order_id === $wpdb->prepared_order, 'Receipt locale and lookup must come from the stored booking.' );

assert_receipt( null === $service->find( $order_id, str_repeat( '0', 64 ) ), 'An invalid receipt token must be rejected before disclosure.' );
assert_receipt( null === $service->find( $order_id . '-', $token ), 'A malformed order identifier must be rejected.' );

$pending = clone $booking;
$pending->payment_status = 'pending';
$wpdb->booking = $pending;
assert_receipt( null === $service->find( $order_id, $token ), 'Pending bookings must never receive a receipt.' );

$receipt_url = ReceiptService::url( $order_id, 'en' );
assert_receipt(
    0 === strpos( $receipt_url, 'https://example.test/en/reservas-metransfers/?mt_receipt=1&oid=' )
        && false !== strpos( $receipt_url, '&token=' . $token ),
    'Receipt URLs must be localized and order-bound with an HMAC token.'
);

$root = dirname( __DIR__ );
$controller = file_get_contents( $root . '/app/Booking/ReceiptController.php' );
$template = file_get_contents( $root . '/app/Legacy/WPTB/templates/receipt.php' );
$confirmation = file_get_contents( $root . '/app/Legacy/WPTB/templates/booking-details.php' );
$payment_js = file_get_contents( $root . '/app/Legacy/WPTB/assets/js/redsys-payment.js' );
$public = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-public.php' );

assert_receipt(
    false !== strpos( $controller, 'X-Robots-Tag: noindex, nofollow, noarchive' )
        && false !== strpos( $controller, 'Referrer-Policy: no-referrer' )
        && false !== strpos( $controller, 'nocache_headers()' ),
    'Receipt responses must be private, non-indexable and non-cacheable.'
);
assert_receipt( false !== strpos( $template, "\$receipt['price']" ) && false !== strpos( $template, 'data-print-receipt' ), 'The printable receipt must render the server DTO.' );
assert_receipt( false !== strpos( $confirmation, 'ReceiptService::url' ), 'The confirmation page must expose only the signed server receipt URL.' );
assert_receipt( false === strpos( $payment_js, 'lastBookingData' ) && false === stripos( $payment_js, 'jspdf' ), 'Browser state and jsPDF must not be receipt authorities.' );
assert_receipt( false === strpos( $public, 'pdf_library_url' ) && false === strpos( $public, 'cdnjs.cloudflare.com/ajax/libs/jspdf' ), 'No remote PDF library may be exposed to the booking frontend.' );

echo "Authoritative receipt tests passed.\n";
