<?php
namespace MeTransfers\Newsletter;

/**
 * Campaigns and their sending queue.
 *
 * Queuing a campaign writes one delivery row per recipient (unique per
 * campaign and subscriber, so nobody gets it twice). A cron worker sends them
 * in small batches under the hourly limit set in Ajustes, with one worker at a
 * time, retries for temporary failures and no resend after an interruption.
 */
final class Campaigns {
	const STATUSES = array(
		'draft'     => 'Borrador',
		'sending'   => 'Enviando',
		'sent'      => 'Enviada',
		'cancelled' => 'Cancelada',
	);

	const AUDIENCES = array(
		'subscribed'         => 'Suscritos (confirmados)',
		'pending'            => 'Pendientes (solo correo de confirmación)',
		'subscribed,pending' => 'Suscritos y confirmación a pendientes',
	);

	const CRON_HOOK      = 'mt_newsletter_dispatch';
	const CRON_SCHEDULE  = 'mt_every_five_minutes';
	const MAX_ATTEMPTS   = 3;
	const BATCH_LIMIT    = 100;
	const TIME_BUDGET    = 25;
	const STALE_MINUTES  = 15;
	const LAST_RUN       = 'mt_newsletter_last_dispatch';
	const ADMIN_INTERVAL = 60;

	public function register() {
		add_filter( 'cron_schedules', array( __CLASS__, 'cronSchedules' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'dispatch' ) );
		if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_event' ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
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

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'mt_newsletter_campaigns';
	}

	public static function deliveriesTable() {
		global $wpdb;
		return $wpdb->prefix . 'mt_newsletter_deliveries';
	}

	public static function find( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), (int) $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * @return array<int, array>
	 */
	public static function latest( $limit = 100 ) {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', self::table(), (int) $limit ), ARRAY_A );
	}

	/**
	 * Creates or updates a draft. Campaigns that started sending are frozen.
	 *
	 * @param array<string, mixed> $data subject, preheader, body, audience_status, audience_language.
	 * @return int Campaign id, or 0 when it can no longer be edited.
	 */
	public static function save( array $data, $id = 0 ) {
		global $wpdb;
		$now    = gmdate( 'Y-m-d H:i:s' );
		$values = self::clean( $data );
		if ( $id ) {
			$campaign = self::find( $id );
			if ( ! $campaign || 'draft' !== $campaign['status'] ) {
				return 0;
			}
			$wpdb->update( self::table(), $values + array( 'updated_at' => $now ), array( 'id' => (int) $id ) );
			return (int) $id;
		}

		$inserted = $wpdb->insert(
			self::table(),
			$values + array(
				'status'     => 'draft',
				'created_by' => get_current_user_id(),
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * @return array{subject: string, preheader: string, body: string, audience_status: string, audience_language: string}
	 */
	public static function clean( array $data ) {
		$audience = (string) ( $data['audience_status'] ?? 'subscribed' );
		$language = (string) ( $data['audience_language'] ?? '' );
		return array(
			'subject'           => mb_substr( sanitize_text_field( (string) ( $data['subject'] ?? '' ) ), 0, 200 ),
			'preheader'         => mb_substr( sanitize_text_field( (string) ( $data['preheader'] ?? '' ) ), 0, 200 ),
			'body'              => wp_kses_post( (string) ( $data['body'] ?? '' ) ),
			'audience_status'   => isset( self::AUDIENCES[ $audience ] ) ? $audience : 'subscribed',
			'audience_language' => in_array( $language, array( 'es', 'en' ), true ) ? $language : '',
		);
	}

	public static function duplicate( $id ) {
		$campaign = self::find( $id );
		if ( ! $campaign ) {
			return 0;
		}
		$campaign['subject'] = mb_substr( $campaign['subject'] . ' (copia)', 0, 200 );
		return self::save( $campaign );
	}

	/**
	 * @return array<int, string>
	 */
	public static function audienceStatuses( $audience ) {
		$statuses = array_intersect( explode( ',', (string) $audience ), Subscribers::SENDABLE );
		return $statuses ? array_values( $statuses ) : array( 'subscribed' );
	}

	public static function audienceCount( $audience, $language ) {
		global $wpdb;
		list( $where, $values ) = self::audienceWhere( $audience, $language );
		// The where clause is built from fixed fragments; every value goes through prepare().
		$sql = "SELECT COUNT(*) FROM %i WHERE $where";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built above from fixed fragments; every value is a placeholder.
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, array_merge( array( Subscribers::table() ), $values ) ) );
	}

	/**
	 * Freezes the recipient list and starts sending.
	 *
	 * @return int|false Recipients queued, or false when the campaign is not a sendable draft.
	 */
	public static function queue( $id ) {
		global $wpdb;
		$campaign = self::find( $id );
		if ( ! $campaign || '' === trim( (string) $campaign['subject'] ) || '' === trim( wp_strip_all_tags( (string) $campaign['body'] ) ) ) {
			return false;
		}

		$now = gmdate( 'Y-m-d H:i:s' );
		// Only one click can move the draft forward, even with a double submit.
		$claimed = $wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'sending', queued_at = %s, updated_at = %s WHERE id = %d AND status = 'draft'", self::table(), $now, $now, (int) $id ) );
		if ( 1 !== $claimed ) {
			return false;
		}

		list( $where, $values ) = self::audienceWhere( $campaign['audience_status'], $campaign['audience_language'] );
		$sql                    = "INSERT IGNORE INTO %i (campaign_id, subscriber_id, email, status, available_at, created_at)
                 SELECT %d, id, email, 'pending', %s, %s FROM %i WHERE $where";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built above from fixed fragments; every value is a placeholder.
		$queued = $wpdb->query( $wpdb->prepare( $sql, array_merge( array( self::deliveriesTable(), (int) $id, $now, $now, Subscribers::table() ), $values ) ) );
		$queued = max( 0, (int) $queued );
		$wpdb->update( self::table(), array( 'total' => $queued ), array( 'id' => (int) $id ) );
		self::refresh( $id );
		self::scheduleSoon();
		return $queued;
	}

	public static function cancel( $id ) {
		global $wpdb;
		$now     = gmdate( 'Y-m-d H:i:s' );
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'cancelled', finished_at = %s, updated_at = %s WHERE id = %d AND status IN ('draft', 'sending')", self::table(), $now, $now, (int) $id ) );
		$wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'cancelled' WHERE campaign_id = %d AND status = 'pending'", self::deliveriesTable(), (int) $id ) );
		self::refresh( $id );
		return 1 === $updated;
	}

	/**
	 * Cron worker: one batch, one worker at a time.
	 *
	 * @return int Emails sent in this run.
	 */
	public static function dispatch( $respect_interval = false ) {
		global $wpdb;
		$lock = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', self::lockName() ) );
		if ( '1' !== (string) $lock ) {
			return 0;
		}
		if ( $respect_interval && (int) get_option( self::LAST_RUN, 0 ) > time() - self::ADMIN_INTERVAL ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lockName() ) );
			return 0;
		}
		update_option( self::LAST_RUN, time(), false );

		$sent = 0;
		// Every running campaign is recounted, including one whose remaining
		// recipients all unsubscribed (nothing left to send, so never touched below).
		$touched = array_fill_keys( array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT id FROM %i WHERE status = 'sending'", self::table() ) ) ), true );
		try {
			// A worker that died mid-send may or may not have delivered: never resend.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET status = 'failed', last_error = 'interrupted', locked_at = NULL WHERE status = 'sending' AND locked_at < %s",
					self::deliveriesTable(),
					gmdate( 'Y-m-d H:i:s', time() - self::STALE_MINUTES * MINUTE_IN_SECONDS )
				)
			);

			$allowance = self::allowance();
			$rows      = $allowance > 0 ? (array) $wpdb->get_results(
				$wpdb->prepare(
					"SELECT d.* FROM %i d INNER JOIN %i c ON c.id = d.campaign_id
                     WHERE d.status = 'pending' AND d.available_at <= %s AND c.status = 'sending'
                     ORDER BY d.id ASC LIMIT %d",
					self::deliveriesTable(),
					self::table(),
					gmdate( 'Y-m-d H:i:s' ),
					$allowance
				),
				ARRAY_A
			) : array();

			$started   = microtime( true );
			$campaigns = array();
			foreach ( $rows as $delivery ) {
				if ( microtime( true ) - $started > self::TIME_BUDGET ) {
					break;
				}
				$campaign_id             = (int) $delivery['campaign_id'];
				$touched[ $campaign_id ] = true;
				if ( ! isset( $campaigns[ $campaign_id ] ) ) {
					$campaigns[ $campaign_id ] = self::find( $campaign_id );
				}
				if ( self::deliver( $delivery, $campaigns[ $campaign_id ] ) ) {
					++$sent;
				}
			}
		} finally {
			foreach ( array_keys( $touched ) as $campaign_id ) {
				self::refresh( $campaign_id );
			}
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lockName() ) );
		}

		if ( self::pendingCount() > 0 ) {
			self::scheduleSoon();
		}
		return $sent;
	}

	/** Explicit send starts one bounded batch immediately, even when WP-Cron cannot run. */
	public static function start( $id ) {
		$queued = self::queue( $id );
		if ( false !== $queued && $queued > 0 ) {
			self::dispatch();
		}
		return $queued;
	}

	/** The last SMTP/local configuration error of an unfinished delivery. */
	public static function pendingError() {
		global $wpdb;
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT d.last_error FROM %i d INNER JOIN %i c ON c.id = d.campaign_id WHERE d.status = 'pending' AND c.status = 'sending' AND d.last_error <> '' ORDER BY d.id DESC LIMIT 1", self::deliveriesTable(), self::table() ) );
	}

	/**
	 * Emails the hourly limit still allows, capped per run.
	 */
	public static function allowance() {
		global $wpdb;
		$rate        = max( 1, (int) Settings::get( 'rate_per_hour' ) );
		$sent_recent = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'sent' AND sent_at >= %s", self::deliveriesTable(), gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
		return self::batchSize( $rate, $sent_recent );
	}

	/**
	 * Per-run batch: a twelfth of the hourly rate (one run every five
	 * minutes), never above BATCH_LIMIT or what the last hour left.
	 */
	public static function batchSize( $rate_per_hour, $sent_last_hour ) {
		$per_run = (int) ceil( max( 1, (int) $rate_per_hour ) / 12 );
		return max( 0, min( self::BATCH_LIMIT, $per_run, (int) $rate_per_hour - (int) $sent_last_hour ) );
	}

	public static function pendingCount() {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i d INNER JOIN %i c ON c.id = d.campaign_id WHERE d.status = 'pending' AND c.status = 'sending'",
				self::deliveriesTable(),
				self::table()
			)
		);
	}

	/**
	 * Recounts a campaign and closes it when nothing is left to send.
	 */
	public static function refresh( $id ) {
		global $wpdb;
		$counts = self::deliveryCounts( $id );
		$now    = gmdate( 'Y-m-d H:i:s' );
		$wpdb->update(
			self::table(),
			array(
				'sent'       => $counts['sent'],
				'failed'     => $counts['failed'],
				'skipped'    => $counts['skipped'] + $counts['cancelled'],
				'updated_at' => $now,
			),
			array( 'id' => (int) $id )
		);
		if ( 0 === $counts['pending'] + $counts['sending'] ) {
			$wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'sent', finished_at = %s WHERE id = %d AND status = 'sending'", self::table(), $now, (int) $id ) );
		}
	}

	/**
	 * @return array<string, int> Deliveries per status.
	 */
	public static function deliveryCounts( $id ) {
		global $wpdb;
		$counts = array_fill_keys( array( 'pending', 'sending', 'sent', 'failed', 'skipped', 'cancelled' ), 0 );
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM %i WHERE campaign_id = %d GROUP BY status', self::deliveriesTable(), (int) $id ), ARRAY_A ) as $row ) {
			$counts[ $row['status'] ] = (int) $row['total'];
		}
		return $counts;
	}

	/**
	 * @return array<int, array> Failed deliveries with their error.
	 */
	public static function failures( $id, $limit = 50 ) {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT email, attempts, last_error FROM %i WHERE campaign_id = %d AND status = 'failed' ORDER BY id ASC LIMIT %d", self::deliveriesTable(), (int) $id, (int) $limit ), ARRAY_A );
	}

	/**
	 * Sends the campaign to one address as it would look for that subscriber.
	 */
	public static function sendTest( array $campaign, $email, $name = '' ) {
		$subscriber = array(
			'id'       => 0,
			'email'    => $email,
			'name'     => $name,
			'language' => '' !== (string) $campaign['audience_language'] ? $campaign['audience_language'] : 'es',
		);
		$rendered   = Mailer::renderCampaign( $campaign, $subscriber );
		return Mailer::send( $email, '[PRUEBA] ' . $rendered['subject'], $rendered['html'], Subscribers::unsubscribeUrl( $subscriber ) );
	}

	public static function scheduleSoon() {
		if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_single_event' ) && ! wp_next_scheduled( self::CRON_HOOK, array( 'soon' ) ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::CRON_HOOK, array( 'soon' ) );
		}
	}

	private static function deliver( array $delivery, $campaign ) {
		global $wpdb;
		$table   = self::deliveriesTable();
		$claimed = $wpdb->query( $wpdb->prepare( "UPDATE %i SET status = 'sending', locked_at = %s, attempts = attempts + 1 WHERE id = %d AND status = 'pending'", $table, gmdate( 'Y-m-d H:i:s' ), (int) $delivery['id'] ) );
		if ( 1 !== $claimed ) {
			return false;
		}

		$subscriber = Subscribers::find( (int) $delivery['subscriber_id'] );
		if ( ! $campaign || ! $subscriber || ! in_array( $subscriber['status'], self::audienceStatuses( $campaign['audience_status'] ), true ) ) {
			$reason = $subscriber ? 'status:' . $subscriber['status'] : 'subscriber_deleted';
			$wpdb->update(
				$table,
				array(
					'status'     => 'skipped',
					'last_error' => $reason,
					'locked_at'  => null,
				),
				array( 'id' => (int) $delivery['id'] )
			);
			return false;
		}

		if ( 'pending' === $subscriber['status'] && ! empty( $subscriber['confirmation_sent_at'] ) && strtotime( $subscriber['confirmation_sent_at'] . ' UTC' ) > time() - Subscribers::RESEND_SECONDS ) {
			$wpdb->update(
				$table,
				array(
					'status'     => 'skipped',
					'last_error' => 'confirmation_recent',
					'locked_at'  => null,
				),
				array( 'id' => (int) $delivery['id'] )
			);
			return false;
		}

		try {
			$ok    = Mailer::sendCampaign( $campaign, $subscriber );
			$error = Mailer::lastError();
		} catch ( \Throwable $exception ) {
			$ok    = false;
			$error = substr( sanitize_text_field( $exception->getMessage() ), 0, 250 );
		}

		if ( $ok ) {
			$wpdb->update(
				$table,
				array(
					'status'     => 'sent',
					'sent_at'    => gmdate( 'Y-m-d H:i:s' ),
					'locked_at'  => null,
					'last_error' => '',
				),
				array( 'id' => (int) $delivery['id'] )
			);
			Subscribers::recordSend( (int) $subscriber['id'] );
			return true;
		}

		$attempts  = (int) $delivery['attempts'] + 1;
		$permanent = Mailer::isPermanentRecipientFailure( $error );
		if ( $permanent ) {
			Subscribers::setStatus( array( (int) $subscriber['id'] ), 'bounced' );
		}
		$retry = ! $permanent && $attempts < self::MAX_ATTEMPTS;
		$wpdb->update(
			$table,
			array(
				'status'       => $retry ? 'pending' : 'failed',
				'available_at' => gmdate( 'Y-m-d H:i:s', time() + $attempts * 5 * MINUTE_IN_SECONDS ),
				'locked_at'    => null,
				'last_error'   => '' !== $error ? $error : 'wp_mail_failed',
			),
			array( 'id' => (int) $delivery['id'] )
		);
		return false;
	}

	/**
	 * @return array{0: string, 1: array<int, string>}
	 */
	private static function audienceWhere( $audience, $language ) {
		$statuses = self::audienceStatuses( $audience );
		$where    = 'status IN (' . implode( ', ', array_fill( 0, count( $statuses ), '%s' ) ) . ')';
		$values   = $statuses;
		if ( in_array( $language, array( 'es', 'en' ), true ) ) {
			$where   .= ' AND language = %s';
			$values[] = $language;
		}
		return array( $where, $values );
	}

	private static function lockName() {
		global $wpdb;
		return 'mt_newsletter_dispatch_' . $wpdb->prefix;
	}
}
