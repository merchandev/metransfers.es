<?php

function assert_booking_draft( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function wp_json_encode( $value ) {
    return json_encode( $value );
}

class BookingDraftWpdbMock {
    public $prefix = 'wp_';
    public $insert_id = 0;
    public $last_args = array();
    public $drafts = array();
    public $bookings = array();

    public function prepare( $query, ...$args ) {
        $this->last_args = $args;
        return $query;
    }

    public function insert( $table, $data, $formats = array() ) {
        if ( 'wp_mt_booking_drafts' !== $table ) {
            return false;
        }

        $this->insert_id++;
        $data['id'] = $this->insert_id;
        $data['payment_booking_id'] = null;
        $data['consumed_at'] = null;
        $this->drafts[ $this->insert_id ] = $data;
        return 1;
    }

    public function get_row( $query, $output = null ) {
        if ( false === strpos( $query, 'wp_mt_booking_drafts' ) ) {
            return null;
        }

        $hash = (string) $this->last_args[0];
        $now = (string) $this->last_args[1];
        foreach ( $this->drafts as $draft ) {
            if ( hash_equals( $draft['token_hash'], $hash ) && $draft['expires_at'] > $now ) {
                return $draft;
            }
        }

        return null;
    }

    public function get_var( $query ) {
        if ( false === strpos( $query, 'payment_idempotency_key' ) ) {
            return null;
        }

        $key = (string) $this->last_args[0];
        return isset( $this->bookings[ $key ] ) ? $this->bookings[ $key ] : null;
    }

    public function update( $table, $data, $where, $formats = array(), $where_formats = array() ) {
        $id = (int) $where['id'];
        if ( 'wp_mt_booking_drafts' !== $table || ! isset( $this->drafts[ $id ] ) ) {
            return false;
        }
        $this->drafts[ $id ] = array_merge( $this->drafts[ $id ], $data );
        return 1;
    }

    public function query( $query ) {
        if ( false === strpos( $query, 'UPDATE wp_mt_booking_drafts' ) ) {
            return false;
        }

        $booking_id = (int) $this->last_args[0];
        $draft_id = (int) $this->last_args[2];
        if ( ! isset( $this->drafts[ $draft_id ] ) ) {
            return 0;
        }

        $bound_id = $this->drafts[ $draft_id ]['payment_booking_id'];
        if ( null !== $bound_id && (int) $bound_id !== $booking_id ) {
            return 0;
        }

        $this->drafts[ $draft_id ]['payment_booking_id'] = $booking_id;
        $this->drafts[ $draft_id ]['consumed_at'] = (string) $this->last_args[1];
        return 1;
    }

    public function createBooking( $idempotency_key ) {
        if ( isset( $this->bookings[ $idempotency_key ] ) ) {
            return 0;
        }
        $id = 10000 + count( $this->bookings );
        $this->bookings[ $idempotency_key ] = $id;
        return $id;
    }
}

$database = new BookingDraftWpdbMock();
require_once __DIR__ . '/../app/Booking/BookingDraftService.php';

$service = new \MeTransfers\Booking\BookingDraftService( $database );
$payload = array(
    'date'           => '2026-09-01',
    'time'           => '10:00',
    'origin'         => 'Barcelona',
    'destination'    => 'Girona',
    'vehicle_id'     => 7,
    'vehicle_name'   => 'Sedan',
    'price'          => 120.50,
    'customer_name'  => 'Private Customer',
    'customer_email' => 'private@example.test',
    'customer_phone' => '+34000000000',
    'notes'          => 'Private note',
);

$token = $service->create( $payload );
assert_booking_draft( 64 === strlen( $token ) && ctype_xdigit( $token ), 'Draft tokens must contain 256 random bits.' );
$stored = reset( $database->drafts );
assert_booking_draft( $token !== $stored['token_hash'], 'The plaintext token must never be stored.' );
assert_booking_draft( hash( 'sha256', $token ) === $stored['token_hash'], 'The database must store only the SHA-256 token digest.' );
assert_booking_draft( false === strpos( $stored['payload'], $token ), 'The token must not leak into the JSON payload.' );

$draft = $service->get( $token );
assert_booking_draft( is_array( $draft ) && $payload['customer_email'] === $draft['payload']['customer_email'], 'A live token must resolve its server-side payload.' );
assert_booking_draft( null === $service->get( str_repeat( 'z', 64 ) ), 'Malformed tokens must be rejected before lookup.' );

$summary = \MeTransfers\Booking\BookingDraftService::summary( $draft['payload'] );
foreach ( array( 'customer_name', 'customer_email', 'customer_phone', 'notes' ) as $private_key ) {
    assert_booking_draft( ! array_key_exists( $private_key, $summary ), "The browser summary must omit $private_key." );
}

$create_calls = 0;
$first_booking_id = $service->ensurePaymentBooking(
    $draft,
    function ( $idempotency_key ) use ( $database, &$create_calls ) {
        $create_calls++;
        return $database->createBooking( $idempotency_key );
    }
);
$same_draft = $service->get( $token );
$second_booking_id = $service->ensurePaymentBooking(
    $same_draft,
    function ( $idempotency_key ) use ( $database, &$create_calls ) {
        $create_calls++;
        return $database->createBooking( $idempotency_key );
    }
);
assert_booking_draft( $first_booking_id === $second_booking_id, 'A repeated payment start must return the existing booking.' );
assert_booking_draft( 1 === $create_calls && 1 === count( $database->bookings ), 'Double submit must create exactly one booking.' );
assert_booking_draft( ! empty( $same_draft['consumed_at'] ), 'Binding a booking must mark the draft as consumed.' );

$race_token = $service->create( $payload );
$race_draft = $service->get( $race_token );
$race_booking_id = $service->ensurePaymentBooking(
    $race_draft,
    function ( $idempotency_key ) use ( $database ) {
        // Simulate another request winning the UNIQUE insert immediately
        // before this request receives its duplicate-key result.
        $database->createBooking( $idempotency_key );
        return 0;
    }
);
assert_booking_draft( $race_booking_id > 0, 'A concurrent UNIQUE-key winner must resolve to its existing booking.' );
$race_reloaded = $service->get( $race_token );
assert_booking_draft( $race_booking_id === (int) $race_reloaded['payment_booking_id'], 'The race winner must be bound back to the draft.' );

$expired_token = $service->create( $payload, 60 );
$expired_id = $database->insert_id;
$database->drafts[ $expired_id ]['expires_at'] = gmdate( 'Y-m-d H:i:s', time() - 1 );
assert_booking_draft( null === $service->get( $expired_token ), 'Expired drafts must not be readable.' );

$root = dirname( __DIR__ );
$schema = file_get_contents( $root . '/app/Core/Schema.php' );
assert_booking_draft(
    false !== strpos( $schema, 'mt_booking_drafts' )
        && false !== strpos( $schema, 'UNIQUE KEY payment_idempotency_key' )
        && false !== strpos( $schema, 'UNIQUE KEY token_hash' ),
    'Draft and booking idempotency constraints must be migration-managed.'
);

$booking_js = file_get_contents( $root . '/app/Legacy/WPTB/assets/js/booking-app.js' );
$pii_start = strpos( $booking_js, 'bookingData.customer_name' );
$pii_end = strpos( $booking_js, '// ===== READ URL PARAMS', $pii_start );
$post_pii_flow = substr( $booking_js, $pii_start, $pii_end - $pii_start );
assert_booking_draft(
    false === strpos( $post_pii_flow, "sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData))" )
        && false !== strpos( $post_pii_flow, 'JSON.stringify({ draft_token: draftToken })' ),
    'After PII collection, browser storage must contain only the opaque draft token.'
);

$payment_js = file_get_contents( $root . '/app/Legacy/WPTB/assets/js/redsys-payment.js' );
assert_booking_draft( false === strpos( $payment_js, 'sessionStorage.setItem' ), 'The payment page must never persist the server-side payload.' );
assert_booking_draft(
    false !== strpos( $payment_js, 'draft_token: token' )
        && false === strpos( $payment_js, 'booking_data: JSON.stringify(bookingData)' ),
    'Payment initiation must submit the draft token instead of PII.'
);

echo "Booking draft and payment idempotency tests passed.\n";
