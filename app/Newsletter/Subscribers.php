<?php
namespace MeTransfers\Newsletter;

/**
 * Newsletter subscribers: signup with consent, double opt-in, unsubscribe,
 * the pages of the visit in which they subscribed, and admin changes.
 *
 * Confirmation and unsubscribe links carry an HMAC of the subscriber, so no
 * secret is stored and a link only works for its own subscriber and purpose.
 */
final class Subscribers {
	const STATUSES = array(
		'subscribed'   => 'Suscrito',
		'pending'      => 'Pendiente de confirmar',
		'unsubscribed' => 'Dado de baja',
		'bounced'      => 'Rebotado',
	);

	// Statuses a campaign may target. Unsubscribed and bounced addresses never receive campaigns.
	const SENDABLE = array( 'subscribed', 'pending' );

	const MAX_PAGES      = 30;
	const RESEND_SECONDS = 600;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'mt_newsletter_subscribers';
	}

	public static function normalizeEmail( $email ) {
		$email = strtolower( trim( sanitize_email( (string) $email ) ) );
		return '' !== $email && strlen( $email ) <= 191 && is_email( $email ) ? $email : '';
	}

	public static function find( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), (int) $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	public static function findByEmail( $email ) {
		global $wpdb;
		$email = self::normalizeEmail( $email );
		if ( '' === $email ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE email = %s', self::table(), $email ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Popup signup. The answer never reveals whether the address was already
	 * subscribed.
	 *
	 * @param array<string, mixed> $data email, name, language, source (popup or footer), signup_page, referrer, pages, consent_text.
	 * @return array{result: string, subscriber: array|null, send_confirmation: bool}
	 */
	public static function subscribe( array $data ) {
		global $wpdb;
		$email = self::normalizeEmail( $data['email'] ?? '' );
		if ( '' === $email ) {
			return array(
				'result'            => 'invalid',
				'subscriber'        => null,
				'send_confirmation' => false,
			);
		}

		$now      = gmdate( 'Y-m-d H:i:s' );
		$double   = (bool) Settings::get( 'double_opt_in' );
		$status   = $double ? 'pending' : 'subscribed';
		$name     = self::clip( sanitize_text_field( (string) ( $data['name'] ?? '' ) ), 120 );
		$pages    = self::sanitizePages( $data['pages'] ?? array() );
		$language = Settings::language( (string) ( $data['language'] ?? 'es' ) );
		$existing = self::findByEmail( $email );
		// A public request must never reactivate a mailbox suppressed for bouncing.
		if ( $existing && 'bounced' === $existing['status'] ) {
			return array(
				'result'            => 'bounced',
				'subscriber'        => $existing,
				'send_confirmation' => false,
			);
		}

		if ( ! $existing ) {
			$inserted = $wpdb->insert(
				self::table(),
				array(
					'email'        => $email,
					'name'         => $name,
					'status'       => $status,
					'language'     => $language,
					'source'       => in_array( $data['source'] ?? '', array( 'popup', 'footer' ), true ) ? $data['source'] : 'popup',
					'signup_page'  => self::clip( self::path( (string) ( $data['signup_page'] ?? '' ) ), 500 ),
					'referrer'     => self::clip( esc_url_raw( (string) ( $data['referrer'] ?? '' ) ), 500 ),
					'pages'        => wp_json_encode( $pages ),
					'consent_text' => self::clip( sanitize_text_field( (string) ( $data['consent_text'] ?? '' ) ), 255 ),
					'consent_at'   => $now,
					'confirmed_at' => $double ? null : $now,
					'created_at'   => $now,
					'updated_at'   => $now,
				)
			);
			if ( false !== $inserted ) {
				$subscriber = self::find( (int) $wpdb->insert_id );
				return array(
					'result'            => $status,
					'subscriber'        => $subscriber,
					'send_confirmation' => $double && null !== $subscriber,
				);
			}
			// Two submissions at once: the other one created the row first.
			$existing = self::findByEmail( $email );
			if ( ! $existing ) {
				return array(
					'result'            => 'error',
					'subscriber'        => null,
					'send_confirmation' => false,
				);
			}
		}

		$changes = array(
			'pages'      => wp_json_encode( self::mergePages( self::pages( $existing ), $pages ) ),
			'updated_at' => $now,
		);
		if ( '' !== $name ) {
			$changes['name'] = $name;
		}

		$current  = (string) $existing['status'];
		$reopened = 'subscribed' !== $current && 'pending' !== $current;
		if ( $reopened ) {
			// A former subscriber who signs up again opts in again, with fresh consent.
			$changes += array(
				'status'          => $status,
				'language'        => $language,
				'consent_text'    => self::clip( sanitize_text_field( (string) ( $data['consent_text'] ?? '' ) ), 255 ),
				'consent_at'      => $now,
				'confirmed_at'    => $double ? null : $now,
				'unsubscribed_at' => null,
			);
			$current  = $status;
		}

		$wpdb->update( self::table(), $changes, array( 'id' => (int) $existing['id'] ) );
		$subscriber = self::find( (int) $existing['id'] );
		// Repeated popup signups of a pending address get one email per RESEND_SECONDS;
		// someone opting in again after leaving always gets a fresh one.
		$recent = ! $reopened && ! empty( $existing['confirmation_sent_at'] )
			&& strtotime( $existing['confirmation_sent_at'] . ' UTC' ) > time() - self::RESEND_SECONDS;

		return array(
			'result'            => $current,
			'subscriber'        => $subscriber,
			'send_confirmation' => 'pending' === $current && ! $recent,
		);
	}

	/**
	 * Admin form and imports. An address that unsubscribed or bounced is never
	 * changed here: only the person can opt in again.
	 *
	 * @return string added, updated, unchanged, blocked or invalid.
	 */
	public static function upsert( $email, $name, $language, $status, $source, $consent_text, $update_name = true ) {
		global $wpdb;
		$email  = self::normalizeEmail( $email );
		$status = in_array( $status, self::SENDABLE, true ) ? $status : 'pending';
		if ( '' === $email ) {
			return 'invalid';
		}

		$now      = gmdate( 'Y-m-d H:i:s' );
		$name     = self::clip( sanitize_text_field( (string) $name ), 120 );
		$existing = self::findByEmail( $email );
		if ( ! $existing ) {
			$inserted = $wpdb->insert(
				self::table(),
				array(
					'email'        => $email,
					'name'         => $name,
					'status'       => $status,
					'language'     => Settings::language( (string) $language ),
					'source'       => self::clip( sanitize_key( (string) $source ), 20 ),
					'pages'        => '[]',
					'consent_text' => self::clip( sanitize_text_field( (string) $consent_text ), 255 ),
					'consent_at'   => $now,
					'confirmed_at' => 'subscribed' === $status ? $now : null,
					'created_at'   => $now,
					'updated_at'   => $now,
				)
			);
			return false === $inserted ? 'invalid' : 'added';
		}

		if ( ! in_array( $existing['status'], self::SENDABLE, true ) ) {
			return 'blocked';
		}

		$changes = array();
		if ( $update_name && '' !== $name && $name !== $existing['name'] ) {
			$changes['name'] = $name;
		}
		if ( 'subscribed' === $status && 'pending' === $existing['status'] ) {
			$changes['status']       = 'subscribed';
			$changes['confirmed_at'] = $now;
		}
		if ( ! $changes ) {
			return 'unchanged';
		}
		$changes['updated_at'] = $now;
		$wpdb->update( self::table(), $changes, array( 'id' => (int) $existing['id'] ) );
		return 'updated';
	}

	/**
	 * @return string confirmed, already or invalid.
	 */
	public static function confirm( $id, $token ) {
		global $wpdb;
		$subscriber = self::find( $id );
		if ( ! $subscriber || ! self::verify( 'confirm', $subscriber, $token ) ) {
			return 'invalid';
		}
		if ( 'pending' !== $subscriber['status'] ) {
			// An old confirmation email must not re-subscribe someone who left.
			return 'subscribed' === $subscriber['status'] ? 'already' : 'invalid';
		}

		$now = gmdate( 'Y-m-d H:i:s' );
		$wpdb->update(
			self::table(),
			array(
				'status'       => 'subscribed',
				'confirmed_at' => $now,
				'updated_at'   => $now,
			),
			array(
				'id'     => (int) $subscriber['id'],
				'status' => 'pending',
			)
		);
		return 'confirmed';
	}

	/**
	 * @return string unsubscribed, already or invalid.
	 */
	public static function unsubscribe( $id, $token ) {
		$subscriber = self::find( $id );
		if ( ! $subscriber || ! self::verify( 'unsubscribe', $subscriber, $token ) ) {
			return 'invalid';
		}
		if ( 'unsubscribed' === $subscriber['status'] ) {
			return 'already';
		}
		self::setStatus( array( (int) $subscriber['id'] ), 'unsubscribed' );
		return 'unsubscribed';
	}

	/**
	 * @param array<int, int> $ids
	 */
	public static function setStatus( array $ids, $status ) {
		global $wpdb;
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( ! $ids || ! isset( self::STATUSES[ $status ] ) ) {
			return 0;
		}

		$now          = gmdate( 'Y-m-d H:i:s' );
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		// The id placeholders are built from the id count; every value goes through prepare().
		$sql = "UPDATE %i SET status = %s, updated_at = %s,
                    confirmed_at = CASE WHEN %s = 'subscribed' THEN COALESCE(confirmed_at, %s) ELSE confirmed_at END,
                    unsubscribed_at = CASE WHEN %s = 'unsubscribed' THEN %s ELSE unsubscribed_at END
                 WHERE id IN ($placeholders)";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built above from fixed fragments; every value is a placeholder.
		$updated = $wpdb->query( $wpdb->prepare( $sql, array_merge( array( self::table(), $status, $now, $status, $now, $status, $now ), $ids ) ) );

		if ( ! in_array( $status, self::SENDABLE, true ) ) {
			// Pending deliveries of a running campaign are dropped at once, not at send time.
			$sql = "UPDATE %i SET status = 'skipped', last_error = %s WHERE status = 'pending' AND subscriber_id IN ($placeholders)";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built above from fixed fragments; every value is a placeholder.
			$wpdb->query( $wpdb->prepare( $sql, array_merge( array( Campaigns::deliveriesTable(), 'status:' . $status ), $ids ) ) );
		}
		return (int) $updated;
	}

	/**
	 * Deletes subscribers and their delivery history (right to erasure).
	 *
	 * @param array<int, int> $ids
	 */
	public static function delete( array $ids ) {
		global $wpdb;
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( ! $ids ) {
			return 0;
		}
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$deliveries   = "DELETE FROM %i WHERE subscriber_id IN ($placeholders)";
		$subscribers  = "DELETE FROM %i WHERE id IN ($placeholders)";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built above from fixed fragments; every value is a placeholder.
		$wpdb->query( $wpdb->prepare( $deliveries, array_merge( array( Campaigns::deliveriesTable() ), $ids ) ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built above from fixed fragments; every value is a placeholder.
		return (int) $wpdb->query( $wpdb->prepare( $subscribers, array_merge( array( self::table() ), $ids ) ) );
	}

	/**
	 * @return array<string, int> Subscribers per status, plus 'all'.
	 */
	public static function counts() {
		global $wpdb;
		$counts = array_fill_keys( array_keys( self::STATUSES ), 0 );
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM %i GROUP BY status', self::table() ), ARRAY_A ) as $row ) {
			$counts[ $row['status'] ] = (int) $row['total'];
		}
		$counts['all'] = array_sum( $counts );
		return $counts;
	}

	/**
	 * @param array<string, mixed> $args status, language, search, page, per_page.
	 * @return array{rows: array<int, array>, total: int}
	 */
	public static function search( array $args ) {
		global $wpdb;
		list( $where, $values ) = self::where( $args );
		$per_page               = max( 1, min( 500, (int) ( $args['per_page'] ?? 50 ) ) );
		$offset                 = ( max( 1, (int) ( $args['page'] ?? 1 ) ) - 1 ) * $per_page;

		// The where clause is built from fixed fragments; every value goes through prepare().
		$count_sql = "SELECT COUNT(*) FROM %i WHERE $where";
		$list_sql  = "SELECT * FROM %i WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built above from fixed fragments; every value is a placeholder.
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, array_merge( array( self::table() ), $values ) ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Built above from fixed fragments; every value is a placeholder.
		$rows = (array) $wpdb->get_results( $wpdb->prepare( $list_sql, array_merge( array( self::table() ), $values, array( $per_page, $offset ) ) ), ARRAY_A );
		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}

	/**
	 * @return array{0: string, 1: array<int, mixed>}
	 */
	public static function where( array $args ) {
		global $wpdb;
		$where  = array( '1=1' );
		$values = array();
		$status = (string) ( $args['status'] ?? '' );
		if ( isset( self::STATUSES[ $status ] ) ) {
			$where[]  = 'status = %s';
			$values[] = $status;
		}
		$language = (string) ( $args['language'] ?? '' );
		if ( in_array( $language, array( 'es', 'en' ), true ) ) {
			$where[]  = 'language = %s';
			$values[] = $language;
		}
		$search = trim( (string) ( $args['search'] ?? '' ) );
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(email LIKE %s OR name LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}
		return array( implode( ' AND ', $where ), $values );
	}

	public static function markConfirmationSent( $id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'confirmation_sent_at' => gmdate( 'Y-m-d H:i:s' ) ), array( 'id' => (int) $id ) );
	}

	public static function recordSend( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE %i SET sends = sends + 1, last_sent_at = %s WHERE id = %d', self::table(), gmdate( 'Y-m-d H:i:s' ), (int) $id ) );
	}

	public static function token( $purpose, array $subscriber ) {
		return hash_hmac( 'sha256', $purpose . '|' . (int) $subscriber['id'] . '|' . strtolower( (string) $subscriber['email'] ), wp_salt( 'auth' ) . '|mt-newsletter' );
	}

	public static function verify( $purpose, array $subscriber, $token ) {
		return is_string( $token ) && 64 === strlen( $token ) && hash_equals( self::token( $purpose, $subscriber ), $token );
	}

	public static function confirmUrl( array $subscriber ) {
		return self::link( 'mt_nl_confirm', 'confirm', $subscriber );
	}

	public static function unsubscribeUrl( array $subscriber ) {
		return self::link( 'mt_nl_unsubscribe', 'unsubscribe', $subscriber );
	}

	/**
	 * @return array<int, array{u: string, t: string, at: string}>
	 */
	public static function pages( array $subscriber ) {
		$pages = json_decode( (string) ( $subscriber['pages'] ?? '' ), true );
		return is_array( $pages ) ? $pages : array();
	}

	/**
	 * Keeps only paths of this site (no other domains), short titles and real
	 * dates, the last MAX_PAGES visits.
	 *
	 * @param mixed $raw Decoded JSON from the popup.
	 * @return array<int, array{u: string, t: string, at: string}>
	 */
	public static function sanitizePages( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = json_decode( wp_unslash( $raw ), true );
		}
		$pages = array();
		foreach ( is_array( $raw ) ? array_slice( $raw, -self::MAX_PAGES ) : array() as $page ) {
			if ( ! is_array( $page ) ) {
				continue;
			}
			$path = self::path( (string) ( $page['u'] ?? '' ) );
			if ( '' === $path ) {
				continue;
			}
			$time    = strtotime( (string) ( $page['at'] ?? '' ) );
			$pages[] = array(
				'u'  => self::clip( $path, 300 ),
				't'  => self::clip( sanitize_text_field( (string) ( $page['t'] ?? '' ) ), 150 ),
				'at' => $time ? gmdate( 'Y-m-d H:i:s', $time ) : '',
			);
		}
		return $pages;
	}

	/**
	 * @param array<int, array> $earlier
	 * @param array<int, array> $latest
	 */
	public static function mergePages( array $earlier, array $latest ) {
		return array_slice( array_merge( $earlier, $latest ), -self::MAX_PAGES );
	}

	/**
	 * Path and query of a URL on this site, or '' for other sites.
	 */
	public static function path( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return '';
		}
		if ( isset( $parts['host'] ) ) {
			$home = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
			if ( strtolower( (string) $parts['host'] ) !== strtolower( (string) $home ) ) {
				return '';
			}
		}
		$path = '/' . ltrim( (string) ( $parts['path'] ?? '/' ), '/' );
		return sanitize_text_field( $path . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' ) );
	}

	private static function link( $action, $purpose, array $subscriber ) {
		return add_query_arg(
			array(
				'action' => $action,
				's'      => (int) $subscriber['id'],
				't'      => self::token( $purpose, $subscriber ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	private static function clip( $text, $limit ) {
		return function_exists( 'mb_substr' ) ? mb_substr( (string) $text, 0, $limit, 'UTF-8' ) : substr( (string) $text, 0, $limit );
	}
}
