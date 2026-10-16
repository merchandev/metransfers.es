<?php
namespace MeTransfers\Newsletter;

/** Read-only view of subscriber confirmation records and their latest outbox event. */
final class WelcomeMonitor {
	const COUNTERS = array(
		'manual_waiting'  => 'En cola manual',
		'mail_waiting'    => 'Pendientes de correo',
		'mail_processing' => 'Enviándose ahora',
		'mail_sent'       => 'Con envío aceptado',
		'mail_failed'     => 'Fallidos o rebotados',
		'subscribed'      => 'Suscritos',
	);

	/** @param array<int, array<string, mixed>> $groups @return array<string, int> */
	public static function counters( array $groups ) {
		$counts = array_fill_keys( array_keys( self::COUNTERS ), 0 );
		foreach ( $groups as $row ) {
			$total                 = (int) $row['total'];
			$status                = (string) $row['status'];
			$has_sent              = ! empty( $row['has_sent'] );
			$active                = ! empty( $row['active_event'] );
			$event                 = (string) ( $row['mail_status'] ?? '' );
			$counts['mail_sent']  += $has_sent ? $total : 0;
			$counts['subscribed'] += 'subscribed' === $status ? $total : 0;
			if ( 'bounced' === $status || ( 'pending' === $status && $active && 'failed' === $event ) ) {
				$counts['mail_failed'] += $total;
			} elseif ( 'pending' === $status && $active && 'processing' === $event ) {
				$counts['mail_processing'] += $total;
			} elseif ( 'pending' === $status && ( ! $has_sent || ( $active && 'pending' === $event ) ) ) {
				$counts['mail_waiting'] += $total;
			}
		}
		return $counts;
	}

	/** @param array<string, mixed> $row */
	public static function mailState( array $row ) {
		if ( 'bounced' === $row['status'] ) {
			return 'Rebotado';
		}
		if ( 'unsubscribed' === $row['status'] ) {
			return 'Cancelado por baja';
		}
		$sent             = (string) ( $row['confirmation_sent_at'] ?? '' );
		$event_after_send = '' === $sent || (string) ( $row['mail_created_at'] ?? '' ) > $sent;
		if ( 'pending' === $row['status'] && $event_after_send ) {
			$labels = array(
				'pending'    => 'En cola / esperando reintento',
				'processing' => 'Enviándose',
				'failed'     => 'Fallido: reintentos agotados',
			);
			if ( isset( $labels[ $row['mail_status'] ?? '' ] ) ) {
				return $labels[ $row['mail_status'] ];
			}
		}
		if ( '' !== $sent ) {
			return 'Aceptado por el transporte de correo';
		}
		return 'subscribed' === $row['status'] ? 'No requiere confirmación' : 'Sin envío registrado';
	}

	private static function date( $utc ) {
		$timestamp = $utc ? strtotime( $utc . ' UTC' ) : false;
		return $timestamp ? wp_date( 'd/m/Y H:i:s', $timestamp ) : '—';
	}

	/** @return array<string, mixed> */
	public static function snapshot() {
		global $wpdb;
		// Only the latest confirmation event participates; older failures cannot override it.
		$groups                   = (array) $wpdb->get_results( $wpdb->prepare( "SELECT s.status, s.confirmation_sent_at IS NOT NULL AS has_sent, COALESCE(o.status, '') AS mail_status, (s.confirmation_sent_at IS NULL OR o.created_at > s.confirmation_sent_at) AS active_event, COUNT(*) AS total FROM %i s LEFT JOIN (SELECT aggregate_id, MAX(id) AS id FROM %i WHERE event_type = 'newsletter.confirm' GROUP BY aggregate_id) latest ON latest.aggregate_id = s.id LEFT JOIN %i o ON o.id = latest.id GROUP BY s.status, has_sent, o.status, active_event", Subscribers::table(), $wpdb->prefix . 'mt_outbox', $wpdb->prefix . 'mt_outbox' ), ARRAY_A );
		$counts                   = self::counters( $groups );
		$counts['manual_waiting'] = ManualQueue::count();
		$rows                     = (array) $wpdb->get_results( $wpdb->prepare( "SELECT s.id, s.email, s.status, s.source, s.confirmation_sent_at, o.status AS mail_status, o.created_at AS mail_created_at, o.attempts, o.available_at, o.last_error FROM %i s LEFT JOIN (SELECT aggregate_id, MAX(id) AS id FROM %i WHERE event_type = 'newsletter.confirm' GROUP BY aggregate_id) latest ON latest.aggregate_id = s.id LEFT JOIN %i o ON o.id = latest.id ORDER BY s.id DESC LIMIT 20", Subscribers::table(), $wpdb->prefix . 'mt_outbox', $wpdb->prefix . 'mt_outbox' ), ARRAY_A );
		$recent                   = array();
		foreach ( $rows as $row ) {
			$state    = self::mailState( $row );
			$recent[] = array(
				'email'    => (string) $row['email'],
				'state'    => $state,
				'attempts' => (int) ( $row['attempts'] ?? 0 ),
				'sent_at'  => self::date( $row['confirmation_sent_at'] ?? '' ),
				'retry_at' => 'En cola / esperando reintento' === $state ? self::date( $row['available_at'] ?? '' ) : '—',
				'error'    => (string) ( $row['last_error'] ?? '' ),
			);
		}
		$next = $counts['manual_waiting'] ? (int) get_option( ManualQueue::NEXT, 0 ) : 0;
		$last = get_option( ManualQueue::LAST, array() );
		return array(
			'counts'            => $counts,
			'next_manual'       => $next ? wp_date( 'd/m/Y H:i:s', $next ) : 'Sin tanda programada',
			'remaining_seconds' => $next ? max( 0, $next - time() ) : null,
			'last_batch'        => is_array( $last ) && ! empty( $last['at'] ) ? wp_date( 'd/m/Y H:i:s', $last['at'] ) . ' · ' . (int) $last['added'] . ' incorporados · ' . (int) $last['failed'] . ' errores' : 'Todavía no se ha procesado ninguna tanda manual.',
			'updated_at'        => wp_date( 'd/m/Y H:i:s' ),
			'recent'            => $recent,
		);
	}
}
