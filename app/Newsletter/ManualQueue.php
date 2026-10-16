<?php
namespace MeTransfers\Newsletter;

use MeTransfers\Core\Outbox;

/** Durable FIFO for administrative additions, independent of public signups. */
final class ManualQueue {
	const HOOK      = 'mt_newsletter_manual_release';
	const NEXT      = 'mt_newsletter_manual_next';
	const LAST      = 'mt_newsletter_manual_last';
	const INTERVAL  = 6 * 60 * 60;
	const MIN_BATCH = 12;
	const MAX_BATCH = 98;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'mt_newsletter_manual_queue';
	}

	public function register() {
		add_action( self::HOOK, array( __CLASS__, 'dispatch' ) );
		self::schedule();
	}

	private static function schedule() {
		$next = (int) get_option( self::NEXT, 0 );
		if ( $next && ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( max( time() + 1, $next ), self::HOOK );
		}
	}

	public static function count() {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', self::table() ) );
	}

	/** @return array<int, array<string, mixed>> */
	public static function preview() {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT id, email FROM %i ORDER BY id ASC LIMIT 50', self::table() ), ARRAY_A );
	}

	/** @return string queued, duplicate, blocked, invalid or error. */
	public static function enqueue( $email, $name, $language, $status, $source, $consent ) {
		global $wpdb;
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', self::lockName() ) ) ) {
			return 'error';
		}
		try {
			return self::enqueueLocked( $email, $name, $language, $status, $source, $consent );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lockName() ) );
		}
	}

	private static function enqueueLocked( $email, $name, $language, $status, $source, $consent ) {
		global $wpdb;
		$email = Subscribers::normalizeEmail( $email );
		if ( '' === $email ) {
			return 'invalid';
		}
		$existing = Subscribers::findByEmail( $email );
		if ( $existing ) {
			return in_array( $existing['status'], Subscribers::SENDABLE, true ) ? 'duplicate' : 'blocked';
		}
		$inserted = $wpdb->query(
			$wpdb->prepare(
				'INSERT IGNORE INTO %i (email, name, language, subscriber_status, source, consent_text, created_at) VALUES (%s, %s, %s, %s, %s, %s, %s)',
				self::table(),
				$email,
				mb_substr( sanitize_text_field( $name ), 0, 120 ),
				Settings::language( $language ),
				'subscribed' === $status ? 'subscribed' : 'pending',
				sanitize_key( $source ),
				mb_substr( sanitize_text_field( $consent ), 0, 255 ),
				gmdate( 'Y-m-d H:i:s' )
			)
		);
		if ( false === $inserted ) {
			return 'error';
		}
		// Atomic first-write: further uploads never postpone an existing batch.
		add_option( self::NEXT, time() + self::INTERVAL, '', false );
		self::schedule();
		return 1 === $inserted ? 'queued' : 'duplicate';
	}

	/** @return array<string, int> */
	public static function paste( $text, $language, $status, $consent ) {
		$report = array(
			'queued'    => 0,
			'duplicate' => 0,
			'blocked'   => 0,
			'invalid'   => 0,
			'error'     => 0,
		);
		foreach ( preg_split( '/[,;\s]+/u', trim( (string) $text ), -1, PREG_SPLIT_NO_EMPTY ) as $email ) {
			// Validate before sanitizing, so malformed input cannot become a different address.
			$result = is_email( $email ) ? self::enqueue( $email, '', $language, $status, 'admin', $consent ) : 'invalid';
			++$report[ $result ];
		}
		return $report;
	}

	private static function lockName() {
		return 'mt_nl_manual_' . substr( hash( 'sha256', self::table() ), 0, 40 );
	}

	public static function acquireLock() {
		global $wpdb;
		return '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', self::lockName() ) );
	}

	public static function releaseLock() {
		global $wpdb;
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lockName() ) );
	}

	/** One batch per six hours, even for duplicate cron invocations or delayed WP-Cron. */
	public static function dispatch() {
		global $wpdb;
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', self::lockName() ) ) ) {
			return 0;
		}
		$added = 0;
		try {
			$next = (int) get_option( self::NEXT, 0 );
			if ( ! $next || $next > time() ) {
				return 0;
			}
			// Persist the next window before processing; never run catch-up bursts.
			if ( ! update_option( self::NEXT, time() + self::INTERVAL, false ) ) {
				return 0;
			}
			$limit  = wp_rand( self::MIN_BATCH, self::MAX_BATCH );
			$rows   = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id ASC LIMIT %d', self::table(), $limit ), ARRAY_A );
			$failed = 0;
			foreach ( $rows as $row ) {
				if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
					++$failed;
					continue;
				}
				try {
					// An organic signup made while waiting always wins, including its consent.
					$existing = Subscribers::findByEmail( $row['email'] );
					$result   = $existing ? 'unchanged' : Subscribers::upsert( $row['email'], $row['name'], $row['language'], $row['subscriber_status'], $row['source'], $row['consent_text'] );
					if ( 'invalid' === $result ) {
						throw new \RuntimeException( 'subscriber_write_failed' );
					}
					if ( 'added' === $result && 'pending' === $row['subscriber_status'] ) {
						$subscriber = Subscribers::findByEmail( $row['email'] );
						if ( ! $subscriber || ! Outbox::enqueue( 'newsletter.confirm', 'newsletter.manual.' . (int) $subscriber['id'] . '.' . (int) $row['id'], (int) $subscriber['id'] ) ) {
							throw new \RuntimeException( 'confirmation_queue_failed' );
						}
					}
					if ( false === $wpdb->delete( self::table(), array( 'id' => (int) $row['id'] ) ) || false === $wpdb->query( 'COMMIT' ) ) {
						throw new \RuntimeException( 'queue_delete_failed' );
					}
					$added += 'added' === $result ? 1 : 0;
				} catch ( \Throwable $error ) {
					$wpdb->query( 'ROLLBACK' );
					++$failed;
				}
			}
			update_option(
				self::LAST,
				array(
					'at'     => time(),
					'limit'  => $limit,
					'added'  => $added,
					'failed' => $failed,
				),
				false
			);
			if ( ! self::count() ) {
				delete_option( self::NEXT );
			}
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lockName() ) );
			self::schedule();
		}
		return $added;
	}

	/** Cancel waiting contacts without changing already admitted subscribers. */
	public static function cancel( $id = 0 ) {
		global $wpdb;
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', self::lockName() ) ) ) {
			return false;
		}
		try {
			return $id ? $wpdb->delete( self::table(), array( 'id' => absint( $id ) ) ) : $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', self::table() ) );
		} finally {
			if ( ! self::count() ) {
				delete_option( self::NEXT );
				wp_clear_scheduled_hook( self::HOOK );
			}
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lockName() ) );
		}
	}

	public static function findByEmail( $email ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE email = %s', self::table(), Subscribers::normalizeEmail( $email ) ), ARRAY_A );
	}

	public static function eraseEmail( $email ) {
		global $wpdb;
		if ( ! self::acquireLock() ) {
			return false;
		}
		try {
			return $wpdb->delete( self::table(), array( 'email' => Subscribers::normalizeEmail( $email ) ) );
		} finally {
			self::releaseLock();
		}
	}
}
