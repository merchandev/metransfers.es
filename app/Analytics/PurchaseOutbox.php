<?php
namespace MeTransfers\Analytics;

use MeTransfers\Core\Outbox;
use MeTransfers\Core\Settings;
use MeTransfers\Pricing\Money;

/**
 * GA4 delivery adapter. New purchases use the generic outbox; the legacy
 * dispatcher remains registered only to drain rows created before 6.1.0.
 */
final class PurchaseOutbox {
    const CRON_HOOK = 'mt_dispatch_analytics_outbox';

    public function register() {
        add_action( self::CRON_HOOK, array( __CLASS__, 'dispatchLegacy' ) );
        if ( function_exists( 'wp_next_scheduled' )
            && function_exists( 'wp_schedule_event' )
            && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + 300, 'hourly', self::CRON_HOOK );
        }
    }

    public static function recordPurchase( $booking ) {
        if ( ! self::eligible( $booking ) ) {
            return false;
        }
        return Outbox::enqueue(
            'analytics.purchase',
            'analytics.purchase:' . (int) $booking->id,
            (int) $booking->id,
            array( 'booking_id' => (int) $booking->id )
        );
    }

    public static function deliverPurchase( $booking ) {
        // Absence of an analytics client ID is a deliberate no-op, not a
        // delivery failure that should fill the dead-letter queue.
        if ( ! self::eligible( $booking ) ) {
            return true;
        }

        return self::sendPayload( self::payload( $booking ) );
    }

    public static function dispatchLegacy() {
        global $wpdb;
        if ( ! self::configured() ) {
            return;
        }

        $table = $wpdb->prefix . 'mt_analytics_outbox';
        $events = $wpdb->get_results(
            "SELECT * FROM $table
             WHERE attempts < 8
               AND ((status = 'pending' AND (available_at IS NULL OR available_at <= UTC_TIMESTAMP()))
                 OR (status = 'processing' AND locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)))
             ORDER BY id ASC LIMIT 20"
        );
        foreach ( (array) $events as $event ) {
            $claimed = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table SET status = 'processing', locked_at = %s
                     WHERE id = %d
                       AND ((status = 'pending' AND (available_at IS NULL OR available_at <= UTC_TIMESTAMP()))
                         OR (status = 'processing' AND locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)))",
                    current_time( 'mysql', true ),
                    (int) $event->id
                )
            );
            if ( 1 !== $claimed ) {
                continue;
            }

            $success = self::sendPayload( (string) $event->payload );
            $attempts = (int) $event->attempts + 1;
            $outcome = Outbox::outcomeForAttempt( $attempts, $success );
            $now = current_time( 'mysql', true );
            $wpdb->update(
                $table,
                array(
                    'attempts'     => $attempts,
                    'status'       => $success ? 'sent' : $outcome['status'],
                    'last_error'   => $success ? null : 'analytics_delivery_failed',
                    'locked_at'    => null,
                    'available_at' => $success || 'failed' === $outcome['status']
                        ? $now
                        : gmdate( 'Y-m-d H:i:s', time() + $outcome['delay'] ),
                    'sent_at'      => $success ? $now : null,
                    'failed_at'    => 'failed' === $outcome['status'] ? $now : null,
                ),
                array( 'id' => (int) $event->id ),
                array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
        }
    }

    /**
     * Backwards-compatible entry point for custom cron invocations.
     */
    public static function dispatch() {
        self::dispatchLegacy();
    }

    private static function eligible( $booking ) {
        if ( ! $booking || empty( $booking->id ) ) {
            return false;
        }
        try {
            if ( Money::fromBooking( $booking )->cents() <= 0 ) {
                return false;
            }
        } catch ( \InvalidArgumentException $exception ) {
            return false;
        }
        $client_id = isset( $booking->analytics_client_id )
            ? sanitize_text_field( $booking->analytics_client_id )
            : '';
        return 1 === preg_match( '/^\d+\.\d+$/', $client_id );
    }

    private static function payload( $booking ) {
        $money = Money::fromBooking( $booking );
        return array(
            'client_id' => sanitize_text_field( $booking->analytics_client_id ),
            'events'    => array(
                array(
                    'name'   => 'purchase',
                    'params' => array(
                        'transaction_id'       => (string) $booking->id,
                        'value'                => $money->decimalFloat(),
                        'currency'             => 'EUR',
                        'engagement_time_msec' => 1,
                    ),
                ),
            ),
        );
    }

    private static function configured() {
        return '' !== (string) Settings::get( 'ga4_measurement_id', '' )
            && '' !== (string) Settings::get( 'ga4_api_secret', '' );
    }

    private static function sendPayload( $payload ) {
        $measurement_id = (string) Settings::get( 'ga4_measurement_id', '' );
        $api_secret = (string) Settings::get( 'ga4_api_secret', '' );
        if ( '' === $measurement_id || '' === $api_secret ) {
            return false;
        }

        $body = is_array( $payload ) ? wp_json_encode( $payload ) : (string) $payload;
        $url = add_query_arg(
            array( 'measurement_id' => $measurement_id, 'api_secret' => $api_secret ),
            'https://www.google-analytics.com/mp/collect'
        );
        $response = wp_remote_post(
            $url,
            array(
                'timeout' => 8,
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => $body,
            )
        );
        if ( is_wp_error( $response ) ) {
            return false;
        }
        $status_code = wp_remote_retrieve_response_code( $response );
        return $status_code >= 200 && $status_code < 300;
    }
}
