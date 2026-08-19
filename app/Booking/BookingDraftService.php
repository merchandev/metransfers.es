<?php

namespace MeTransfers\Booking;

/**
 * Persists short-lived booking data without exposing PII to browser storage.
 */
class BookingDraftService {
    const DEFAULT_TTL = 7200;
    const MAX_PAYLOAD_BYTES = 65535;

    private $database;

    public function __construct( $database = null ) {
        if ( null === $database ) {
            global $wpdb;
            $database = $wpdb;
        }

        $this->database = $database;
    }

    public function register() {
        add_action( 'mt_cleanup_booking_drafts', array( $this, 'purgeExpired' ) );
        if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_event' ) ) {
            return;
        }
        if ( ! wp_next_scheduled( 'mt_cleanup_booking_drafts' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'mt_cleanup_booking_drafts' );
        }
    }

    public function purgeExpired() {
        return $this->database->query(
            $this->database->prepare(
                "DELETE FROM {$this->draftTable()} WHERE expires_at <= %s LIMIT 500",
                gmdate( 'Y-m-d H:i:s' )
            )
        );
    }

    /**
     * @return string Plaintext token. Only its SHA-256 digest is persisted.
     */
    public function create( array $payload, $ttl = self::DEFAULT_TTL ) {
        $encoded = wp_json_encode( $payload );
        if ( false === $encoded || strlen( $encoded ) > self::MAX_PAYLOAD_BYTES ) {
            throw new \InvalidArgumentException( 'booking_draft_payload_invalid' );
        }

        $ttl = max( 60, (int) $ttl );
        $token = bin2hex( random_bytes( 32 ) );
        $token_hash = self::hashToken( $token );
        $idempotency_key = hash( 'sha256', 'payment:' . $token );
        $created_at = gmdate( 'Y-m-d H:i:s' );
        $expires_at = gmdate( 'Y-m-d H:i:s', time() + $ttl );

        $inserted = $this->database->insert(
            $this->draftTable(),
            array(
                'token_hash'       => $token_hash,
                'idempotency_key'  => $idempotency_key,
                'payload'          => $encoded,
                'created_at'       => $created_at,
                'expires_at'       => $expires_at,
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            throw new \RuntimeException( 'booking_draft_save_failed' );
        }

        return $token;
    }

    public function get( $token ) {
        if ( ! self::isValidToken( $token ) ) {
            return null;
        }

        $now = gmdate( 'Y-m-d H:i:s' );
        $row = $this->database->get_row(
            $this->database->prepare(
                "SELECT * FROM {$this->draftTable()} WHERE token_hash = %s AND expires_at > %s LIMIT 1",
                self::hashToken( $token ),
                $now
            ),
            'ARRAY_A'
        );

        if ( ! is_array( $row ) ) {
            return null;
        }

        $payload = json_decode( (string) $row['payload'], true );
        if ( ! is_array( $payload ) ) {
            return null;
        }

        $row['payload'] = $payload;
        return $row;
    }

    public function updatePayload( $draft_id, array $payload ) {
        $encoded = wp_json_encode( $payload );
        if ( false === $encoded || strlen( $encoded ) > self::MAX_PAYLOAD_BYTES ) {
            return false;
        }

        return false !== $this->database->update(
            $this->draftTable(),
            array( 'payload' => $encoded ),
            array( 'id' => (int) $draft_id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Returns a stable booking id. The bookings-table UNIQUE key is the final
     * guard when two requests race before the draft row can be bound.
     */
    public function ensurePaymentBooking( array $draft, callable $create_booking ) {
        $existing_id = $this->existingPaymentBookingId( $draft );
        if ( $existing_id > 0 ) {
            return $existing_id;
        }

        $created_id = (int) call_user_func( $create_booking, (string) $draft['idempotency_key'] );
        if ( $created_id > 0 ) {
            $this->bindPaymentBooking( (int) $draft['id'], $created_id );
            return $created_id;
        }

        // A concurrent request may have won the UNIQUE insert. Resolve it
        // instead of turning a harmless duplicate click into a second booking.
        $existing_id = $this->bookingIdByIdempotencyKey( (string) $draft['idempotency_key'] );
        if ( $existing_id > 0 ) {
            $this->bindPaymentBooking( (int) $draft['id'], $existing_id );
            return $existing_id;
        }

        throw new \RuntimeException( 'booking_save_failed' );
    }

    public static function summary( array $payload ) {
        return array_intersect_key(
            $payload,
            array_flip(
                array(
                    'date',
                    'time',
                    'origin',
                    'destination',
                    'vehicle_id',
                    'vehicle_name',
                    'trip_type',
                    'price',
                    'passengers',
                    'return_date',
                    'return_time',
                    'return_origin',
                    'return_destination',
                    'language',
                )
            )
        );
    }

    public static function isValidToken( $token ) {
        return is_string( $token ) && 1 === preg_match( '/\A[a-f0-9]{64}\z/', $token );
    }

    public static function hashToken( $token ) {
        return hash( 'sha256', (string) $token );
    }

    public function existingPaymentBookingId( array $draft ) {
        if ( ! empty( $draft['payment_booking_id'] ) ) {
            return (int) $draft['payment_booking_id'];
        }

        return $this->bookingIdByIdempotencyKey( (string) $draft['idempotency_key'] );
    }

    private function bookingIdByIdempotencyKey( $idempotency_key ) {
        return (int) $this->database->get_var(
            $this->database->prepare(
                "SELECT id FROM {$this->bookingsTable()} WHERE payment_idempotency_key = %s LIMIT 1",
                $idempotency_key
            )
        );
    }

    private function bindPaymentBooking( $draft_id, $booking_id ) {
        $now = gmdate( 'Y-m-d H:i:s' );
        $updated = $this->database->query(
            $this->database->prepare(
                "UPDATE {$this->draftTable()}
                 SET payment_booking_id = %d, consumed_at = COALESCE(consumed_at, %s)
                 WHERE id = %d AND (payment_booking_id IS NULL OR payment_booking_id = %d)",
                $booking_id,
                $now,
                $draft_id,
                $booking_id
            )
        );

        return false !== $updated;
    }

    private function draftTable() {
        return $this->database->prefix . 'mt_booking_drafts';
    }

    private function bookingsTable() {
        return $this->database->prefix . 'wptb_bookings';
    }
}
