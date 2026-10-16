<?php
/** Exercise the exiting public link controllers in separate PHP processes. */
if ( isset( $argv[1] ) ) {
	ob_start();
	require __DIR__ . '/test-newsletter.php';
	ob_end_clean();
	function nocache_headers() {}
	function status_header( $code ) { $GLOBALS['nl_status'] = $code; }
	$scenario = $argv[1];
	$subscriber = \MeTransfers\Newsletter\Subscribers::findByEmail( 'eve@example.com' );
	$id = (int) $subscriber['id'];
	$purpose = str_starts_with( $scenario, 'unsubscribe' ) ? 'unsubscribe' : 'confirm';
	$_SERVER['REQUEST_METHOD'] = str_contains( $scenario, 'post' ) ? 'POST' : 'GET';
	$_GET = array( 's' => $id, 't' => \MeTransfers\Newsletter\Subscribers::token( $purpose, $subscriber ) );
	$_POST = $_GET;
	if ( str_contains( $scenario, 'invalid' ) ) { $_POST['t'] = str_repeat( '0', 64 ); $_GET['t'] = $_POST['t']; }
	if ( str_contains( $scenario, 'oneclick' ) ) {
		// RFC 8058 posts only this field; the signed identity remains in the URL.
		$_POST = array( 'List-Unsubscribe' => 'One-Click' );
	}
	register_shutdown_function( static function () use ( $id ) {
		echo '\nSTATE:' . json_encode( array( 'status' => \MeTransfers\Newsletter\Subscribers::find( $id )['status'], 'http' => $GLOBALS['nl_status'] ?? 200 ) );
	} );
	\MeTransfers\Newsletter\PublicController::$purpose();
	exit( 1 );
}

$scenarios = array(
	'confirm-get' => array( 'pending', '<form method="post"' ),
	'confirm-post' => array( 'subscribed', '¡Suscripción confirmada!' ),
	'confirm-post-invalid' => array( 'pending', 'Enlace no válido' ),
	'unsubscribe-get' => array( 'pending', '<form method="post"' ),
	'unsubscribe-post' => array( 'unsubscribed', 'Te has dado de baja' ),
	'unsubscribe-post-oneclick' => array( 'unsubscribed', '"http":200' ),
	'unsubscribe-post-oneclick-invalid' => array( 'pending', '"http":400' ),
);
foreach ( $scenarios as $scenario => $expected ) {
	$process = proc_open( array( PHP_BINARY, __FILE__, $scenario ), array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	$output = stream_get_contents( $pipes[1] );
	$errors = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	$exit = proc_close( $process );
	if ( 0 !== $exit || '' !== $errors || ! str_contains( $output, '"status":"' . $expected[0] . '"' ) || ! str_contains( $output, $expected[1] ) ) {
		fwrite( STDERR, "FAILED $scenario: $errors\n$output\n" );
		exit( 1 );
	}
}
echo "Newsletter public link tests passed.\n";
