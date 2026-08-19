<?php
namespace MeTransfers\Analytics;

use MeTransfers\Core\Settings;

final class PurchaseOutbox {
    const CRON_HOOK = 'mt_dispatch_analytics_outbox';

    public function register() {
        add_action( self::CRON_HOOK, array( __CLASS__, 'dispatch' ) );
        if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_event' ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + 300, 'hourly', self::CRON_HOOK );
        }
    }

    public static function recordPurchase( $booking ) {
        global $wpdb;
        if ( ! $booking || empty( $booking->id ) || (float) $booking->price <= 0 ) {
            return false;
        }

        // No GA cookie means no analytics consent/client identity. Keep the
        // financial record in WordPress only and do not send it to Google.
        if ( empty( $booking->analytics_client_id ) ) {
            return false;
        }
        $client_id = sanitize_text_field( $booking->analytics_client_id );
        if ( ! preg_match( '/^\d+\.\d+$/', $client_id ) ) {
            return false;
        }
        $payload = array(
            'client_id' => $client_id,
            'events'    => array(
                array(
                    'name'   => 'purchase',
                    'params' => array(
                        'transaction_id' => (string) $booking->id,
                        'value'          => (float) $booking->price,
                        'currency'       => 'EUR',
                        'engagement_time_msec' => 1,
                    ),
                ),
            ),
        );

        $inserted = $wpdb->query(
            $wpdb->prepare(
                "INSERT IGNORE INTO {$wpdb->prefix}mt_analytics_outbox
                    (event_name, event_key, payload, status, created_at)
                 VALUES (%s, %s, %s, %s, %s)",
                'purchase',
                'purchase:' . (int) $booking->id,
                wp_json_encode( $payload ),
                'pending',
                current_time( 'mysql', true )
            )
        );
        if ( $inserted && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_single_event( time() + 5, self::CRON_HOOK );
        }
        return false !== $inserted;
    }

    public static function dispatch() {
        global $wpdb;
        $measurement_id = (string) Settings::get( 'ga4_measurement_id', '' );
        $api_secret = (string) Settings::get( 'ga4_api_secret', '' );
        if ( '' === $measurement_id || '' === $api_secret ) {
            return;
        }

        $table = $wpdb->prefix . 'mt_analytics_outbox';
        $events = $wpdb->get_results(
            "SELECT * FROM $table
             WHERE attempts < 8
               AND (status = 'pending' OR (status = 'processing' AND locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)))
             ORDER BY id ASC LIMIT 20"
        );
        foreach ( (array) $events as $event ) {
            $claimed = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table SET status = 'processing', locked_at = %s
                     WHERE id = %d
                       AND (status = 'pending' OR (status = 'processing' AND locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)))",
                    current_time( 'mysql', true ),
                    (int) $event->id
                )
            );
            if ( 1 !== $claimed ) {
                continue;
            }

            $url = add_query_arg(
                array( 'measurement_id' => $measurement_id, 'api_secret' => $api_secret ),
                'https://www.google-analytics.com/mp/collect'
            );
            $response = wp_remote_post( $url, array(
                'timeout' => 8,
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => $event->payload,
            ) );
            $success = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) >= 200 && wp_remote_retrieve_response_code( $response ) < 300;
            $wpdb->update(
                $table,
                array(
                    'attempts'   => (int) $event->attempts + 1,
                    'status'     => $success ? 'sent' : 'pending',
                    'last_error' => $success ? null : ( is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response ) ),
                    'locked_at'  => null,
                    'sent_at'    => $success ? current_time( 'mysql', true ) : null,
                ),
                array( 'id' => (int) $event->id ),
                array( '%d', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
        }
    }
}
