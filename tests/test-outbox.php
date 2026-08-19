<?php

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );

function assert_outbox( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

function sanitize_text_field( $value ) {
    return trim( (string) $value );
}

function absint( $value ) {
    return abs( (int) $value );
}

function wp_json_encode( $value ) {
    return json_encode( $value );
}

function current_time( $type, $gmt = false ) {
    return '2026-08-19 19:00:00';
}

$GLOBALS['mt_scheduled_events'] = array();

function wp_next_scheduled( $hook, $args = array() ) {
    $key = $hook . ':' . json_encode( $args );
    return isset( $GLOBALS['mt_scheduled_events'][ $key ] )
        ? $GLOBALS['mt_scheduled_events'][ $key ]
        : false;
}

function wp_schedule_single_event( $timestamp, $hook, $args = array() ) {
    $key = $hook . ':' . json_encode( $args );
    $GLOBALS['mt_scheduled_events'][ $key ] = $timestamp;
    return true;
}

class OutboxWpdbMock {
    public $prefix = 'wp_';
    public $last_args = array();
    public $events = array();

    public function prepare( $query, ...$args ) {
        if ( false !== strpos( $query, '%i' ) ) {
            $identifier = array_shift( $args );
            $query = preg_replace( '/%i/', (string) $identifier, $query, 1 );
        }
        $this->last_args = $args;
        return $query;
    }

    public function query( $query ) {
        if ( false === strpos( $query, 'INSERT IGNORE INTO wp_mt_outbox' ) ) {
            return false;
        }
        $key = (string) $this->last_args[0];
        if ( isset( $this->events[ $key ] ) ) {
            return 0;
        }
        $this->events[ $key ] = array(
            'type' => (string) $this->last_args[1],
            'id'   => (int) $this->last_args[2],
        );
        return 1;
    }
}

global $wpdb;
$wpdb = new OutboxWpdbMock();

require_once __DIR__ . '/../app/Core/Outbox.php';
require_once __DIR__ . '/../app/Booking/BookingEvents.php';

assert_outbox( \MeTransfers\Booking\BookingEvents::paid( 42 ), 'The first paid event must be persisted.' );
assert_outbox( \MeTransfers\Booking\BookingEvents::paid( 42 ), 'A duplicate paid event must be treated as already durable.' );
assert_outbox( 1 === count( $wpdb->events ), 'Duplicate IPNs must produce one parent event row.' );
assert_outbox(
    'booking.paid' === $wpdb->events['booking.paid:42']['type'],
    'Event types must preserve their dot-separated contract.'
);
assert_outbox(
    1 === count( $GLOBALS['mt_scheduled_events'] ),
    'Only a newly inserted event should schedule immediate processing.'
);

$paid_events = \MeTransfers\Booking\BookingEvents::channelEvents( 'paid', 42 );
$paid_keys = array_column( $paid_events, 'key' );
assert_outbox( 5 === count( $paid_keys ), 'A paid booking must expand to five channel events.' );
assert_outbox( 5 === count( array_unique( $paid_keys ) ), 'Every paid channel event key must be unique.' );
foreach ( array(
    'email.customer.paid:42',
    'email.admin.paid:42',
    'email.hotel.paid:42',
    'whatsapp.admin.paid:42',
    'analytics.purchase:42',
) as $expected_key ) {
    assert_outbox( in_array( $expected_key, $paid_keys, true ), "Missing channel key $expected_key." );
}

$pending_keys = array_column( \MeTransfers\Booking\BookingEvents::channelEvents( 'pending', 42 ), 'key' );
assert_outbox( 3 === count( $pending_keys ), 'Pending bookings must enqueue email channels only.' );
assert_outbox(
    false === strpos( implode( '|', $pending_keys ), 'whatsapp' ),
    'Pending bookings must never enqueue WhatsApp.'
);

$retry = \MeTransfers\Core\Outbox::outcomeForAttempt( 3, false );
$dead_letter = \MeTransfers\Core\Outbox::outcomeForAttempt( 8, false );
$success = \MeTransfers\Core\Outbox::outcomeForAttempt( 1, true );
assert_outbox( 'pending' === $retry['status'] && 240 === $retry['delay'], 'Retries must use exponential backoff.' );
assert_outbox( 'failed' === $dead_letter['status'] && 0 === $dead_letter['delay'], 'The eighth failure must enter dead-letter state.' );
assert_outbox( 'processed' === $success['status'], 'Successful events must be marked processed.' );

$root = dirname( __DIR__ );
$public_controller = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-public.php' );
$ipn_start = strpos( $public_controller, 'public function listen_redsys_ipn()' );
$ipn_end = strpos( $public_controller, 'public function configure_smtp', $ipn_start );
$ipn = substr( $public_controller, $ipn_start, $ipn_end - $ipn_start );
assert_outbox( false !== strpos( $ipn, 'BookingEvents::paid' ), 'The IPN must persist a paid event.' );
foreach ( array( 'process_booking_notifications', 'recordPurchase', 'wp_mail(', 'sendWhatsapp', 'wp_remote_' ) as $forbidden ) {
    assert_outbox( false === strpos( $ipn, $forbidden ), "The IPN must not execute $forbidden before ACK." );
}
$paid_event_position = strpos( $ipn, 'BookingEvents::paid' );
assert_outbox(
    false !== strpos( $ipn, 'status_header( 200 )', $paid_event_position ),
    'The IPN must acknowledge only after the outbox insert.'
);

$schema = file_get_contents( $root . '/app/Core/Schema.php' );
assert_outbox(
    false !== strpos( $schema, 'mt_outbox' )
        && false !== strpos( $schema, 'UNIQUE KEY event_key' )
        && false !== strpos( $schema, 'status_available' ),
    'The durable outbox schema and indexes must be migration-managed.'
);

$admin_controller = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-admin.php' );
$resend_start = strpos( $admin_controller, 'public function resend_booking_email()' );
$resend_end = strpos( $admin_controller, 'public function resend_booking_whatsapp', $resend_start );
$resend = substr( $admin_controller, $resend_start, $resend_end - $resend_start );
assert_outbox( false !== strpos( $resend, 'NotificationService::sendEmails' ), 'Manual email resend must call the email-only API.' );
assert_outbox( false === strpos( $resend, 'process_booking_notifications' ), 'Manual email resend must not call the multi-channel facade.' );
assert_outbox( false === strpos( $resend, 'sendWhatsapp' ), 'Manual email resend must not resend WhatsApp.' );
$whatsapp_end = strpos( $admin_controller, 'public function delete_single_booking', $resend_end );
$whatsapp_resend = substr( $admin_controller, $resend_end, $whatsapp_end - $resend_end );
assert_outbox( false !== strpos( $whatsapp_resend, 'NotificationService::sendWhatsapp' ), 'Manual WhatsApp resend must use the channel-only API.' );
assert_outbox( false === strpos( $whatsapp_resend, 'sendEmails' ), 'Manual WhatsApp resend must not resend email.' );

echo "Generic outbox tests passed.\n";
