<?php
namespace MeTransfers\Core;

/**
 * Durable event queue for work that must happen after the request is acknowledged.
 */
final class Outbox {
	const CRON_HOOK     = 'mt_dispatch_outbox';
	const CRON_SCHEDULE = 'mt_every_five_minutes';
	const MAX_ATTEMPTS  = 8;

	public function register() {
		add_filter( 'cron_schedules', array( __CLASS__, 'cronSchedules' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'dispatch' ), 10, 1 );

		if ( function_exists( 'wp_next_scheduled' )
			&& function_exists( 'wp_schedule_event' )
			&& ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, self::CRON_SCHEDULE, self::CRON_HOOK );
		}
	}

	public static function cronSchedules( $schedules ) {
		$schedules[ self::CRON_SCHEDULE ] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => 'Every five minutes (MeTransfers)',
		);
		return $schedules;
	}

	public static function enqueue( $event_type, $event_key, $aggregate_id, $payload = array() ) {
		global $wpdb;

		$event_type   = preg_replace( '/[^a-z0-9._-]/', '', strtolower( (string) $event_type ) );
		$event_key    = sanitize_text_field( (string) $event_key );
		$aggregate_id = absint( $aggregate_id );
		if ( '' === $event_type
			|| strlen( $event_type ) > 80
			|| '' === $event_key
			|| strlen( $event_key ) > 191
			|| $aggregate_id <= 0
			|| ! is_array( $payload ) ) {
			return false;
		}

		$now      = current_time( 'mysql', true );
		$table    = $wpdb->prefix . 'mt_outbox';
		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO %i
                    (event_key, event_type, aggregate_id, payload, status, attempts, available_at, created_at)
                 VALUES (%s, %s, %d, %s, 'pending', 0, %s, %s)",
				$table,
				$event_key,
				$event_type,
				$aggregate_id,
				wp_json_encode( $payload ),
				$now,
				$now
			)
		);

		if ( 1 === $inserted
			&& function_exists( 'wp_next_scheduled' )
			&& function_exists( 'wp_schedule_single_event' )
			&& ! wp_next_scheduled( self::CRON_HOOK, array( 'immediate' ) ) ) {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK, array( 'immediate' ) );
		}

		// A duplicate key means the event is already durable and is success.
		return false !== $inserted;
	}

	public static function dispatch( $reason = 'scheduled' ) {
		global $wpdb;
		unset( $reason );

		$table  = $wpdb->prefix . 'mt_outbox';
		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i
                 WHERE (status = 'pending' AND available_at <= UTC_TIMESTAMP())
                    OR (status = 'processing' AND locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE))
                 ORDER BY id ASC
                 LIMIT 20",
				$table
			)
		);

		foreach ( (array) $events as $event ) {
			if ( ! self::claim( $event ) ) {
				continue;
			}

			try {
				$payload = json_decode( (string) $event->payload, true );
				if ( ! is_array( $payload ) ) {
					throw new \RuntimeException( 'invalid_payload' );
				}
				if ( ! OutboxHandler::handle( (string) $event->event_type, (int) $event->aggregate_id, $payload ) ) {
					throw new \RuntimeException( 'event_handler_failed' );
				}
				self::complete( $event );
			} catch ( \Throwable $exception ) {
				self::retryOrFail( $event, $exception->getMessage() );
			}
		}
	}

	public static function backoffSeconds( $attempts ) {
		$attempts = max( 1, (int) $attempts );
		return min( HOUR_IN_SECONDS, MINUTE_IN_SECONDS * ( 2 ** ( $attempts - 1 ) ) );
	}

	public static function outcomeForAttempt( $attempts, $success ) {
		$attempts = max( 1, (int) $attempts );
		if ( $success ) {
			return array(
				'status' => 'processed',
				'delay'  => 0,
			);
		}
		if ( $attempts >= self::MAX_ATTEMPTS ) {
			return array(
				'status' => 'failed',
				'delay'  => 0,
			);
		}
		return array(
			'status' => 'pending',
			'delay'  => self::backoffSeconds( $attempts ),
		);
	}

	private static function claim( $event ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'mt_outbox';
		$claimed = $wpdb->query(
			$wpdb->prepare(
				"UPDATE %i
                 SET status = 'processing', locked_at = %s
                 WHERE id = %d
                   AND ((status = 'pending' AND available_at <= UTC_TIMESTAMP())
                     OR (status = 'processing' AND locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)))",
				$table,
				current_time( 'mysql', true ),
				(int) $event->id
			)
		);
		return 1 === $claimed;
	}

	private static function complete( $event ) {
		global $wpdb;
		$table = $wpdb->prefix . 'mt_outbox';
		$wpdb->update(
			$table,
			array(
				'status'       => 'processed',
				'attempts'     => (int) $event->attempts + 1,
				'last_error'   => null,
				'locked_at'    => null,
				'processed_at' => current_time( 'mysql', true ),
			),
			array( 'id' => (int) $event->id ),
			array( '%s', '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	private static function retryOrFail( $event, $error ) {
		global $wpdb;
		$attempts     = (int) $event->attempts + 1;
		$outcome      = self::outcomeForAttempt( $attempts, false );
		$now          = current_time( 'mysql', true );
		$available_at = 'failed' === $outcome['status']
			? $now
			: gmdate( 'Y-m-d H:i:s', time() + $outcome['delay'] );
		$error        = substr( sanitize_text_field( (string) $error ), 0, 500 );
		$table        = $wpdb->prefix . 'mt_outbox';

		$wpdb->update(
			$table,
			array(
				'status'       => $outcome['status'],
				'attempts'     => $attempts,
				'available_at' => $available_at,
				'last_error'   => $error,
				'locked_at'    => null,
				'failed_at'    => 'failed' === $outcome['status'] ? $now : null,
			),
			array( 'id' => (int) $event->id ),
			array( '%s', '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}
}
