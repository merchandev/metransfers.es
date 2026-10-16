<?php
require __DIR__ . '/../app/Newsletter/WelcomeMonitor.php';
use MeTransfers\Newsletter\WelcomeMonitor as Monitor;

function monitor_assert( $condition, $message ) {
	if ( ! $condition ) { fwrite( STDERR, "FAILED: $message\n" ); exit( 1 ); }
}

$counts = Monitor::counters( array(
	array( 'status' => 'pending', 'has_sent' => 0, 'active_event' => 1, 'mail_status' => 'pending', 'total' => 7 ),
	array( 'status' => 'pending', 'has_sent' => 0, 'active_event' => 1, 'mail_status' => 'processing', 'total' => 2 ),
	array( 'status' => 'pending', 'has_sent' => 0, 'active_event' => 1, 'mail_status' => 'failed', 'total' => 3 ),
	array( 'status' => 'pending', 'has_sent' => 0, 'active_event' => 1, 'mail_status' => 'processed', 'total' => 4 ),
	array( 'status' => 'pending', 'has_sent' => 1, 'active_event' => 0, 'mail_status' => 'pending', 'total' => 5 ),
	array( 'status' => 'subscribed', 'has_sent' => 0, 'active_event' => 1, 'mail_status' => 'pending', 'total' => 6 ),
	array( 'status' => 'bounced', 'has_sent' => 0, 'active_event' => 1, 'mail_status' => 'processed', 'total' => 1 ),
	array( 'status' => 'unsubscribed', 'has_sent' => 0, 'active_event' => 1, 'mail_status' => 'pending', 'total' => 8 ),
) );
monitor_assert( 11 === $counts['mail_waiting'] && 2 === $counts['mail_processing'] && 4 === $counts['mail_failed'], 'Waiting, processing and failures must reflect the latest active event and subscriber suppression.' );
monitor_assert( 5 === $counts['mail_sent'] && 6 === $counts['subscribed'], 'A processed outbox event or consent-based subscription must never be counted as a sent confirmation.' );
monitor_assert( 0 === $counts['manual_waiting'], 'Manual backlog is independent of admitted subscriber totals.' );
$pending = array( 'status' => 'pending', 'confirmation_sent_at' => null );
monitor_assert( 'Sin envío registrado' === Monitor::mailState( $pending + array( 'mail_status' => 'processed' ) ), 'Skipped processed events must not claim delivery.' );
monitor_assert( 'Enviándose' === Monitor::mailState( $pending + array( 'mail_status' => 'processing' ) ), 'Processing must be visible as a real outbox state.' );
monitor_assert( 'Fallido: reintentos agotados' === Monitor::mailState( $pending + array( 'mail_status' => 'failed' ) ), 'Exhausted retries must remain visible.' );
monitor_assert( 'No requiere confirmación' === Monitor::mailState( array( 'status' => 'subscribed' ) ), 'Contacts subscribed by recorded consent do not imply a welcome email was sent.' );
$sent = array( 'status' => 'pending', 'confirmation_sent_at' => '2026-10-16 10:00:00', 'mail_created_at' => '2026-10-16 09:00:00', 'mail_status' => 'pending' );
monitor_assert( 'Aceptado por el transporte de correo' === Monitor::mailState( $sent ), 'An older pending event must not hide a more recent successful confirmation.' );
$sent['mail_created_at'] = '2026-10-16 11:00:00';
monitor_assert( 'En cola / esperando reintento' === Monitor::mailState( $sent ), 'A newer resend request must remain visible.' );
echo "Newsletter welcome monitor tests passed.\n";
