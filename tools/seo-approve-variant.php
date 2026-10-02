<?php
/** WP-CLI: wp eval-file tools/seo-approve-variant.php manifest.json [--apply] */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'WP-CLI required.' );
}
$manifest_path = $args[0] ?? '';
$apply         = in_array( '--apply', $args, true );
$manifest      = is_file( $manifest_path ) ? json_decode( file_get_contents( $manifest_path ), true ) : null;
if ( ! is_array( $manifest ) || empty( $manifest['variants'] ) || empty( $manifest['version'] ) ) {
	WP_CLI::error( 'Invalid manifest.' );
}
$batch = \MeTransfers\SEO\VariantApproval::prepare( $manifest['variants'], $apply );
if ( $batch['errors'] ) {
	WP_CLI::error( implode( "\n", $batch['errors'] ) );
}
if ( $apply ) {
	$errors = \MeTransfers\SEO\VariantApproval::apply( $batch );
	if ( $errors ) {
		WP_CLI::error( implode( "\n", $errors ) );
	}
}
foreach ( $batch['variants'] as $row ) {
	WP_CLI::log( $row['id'] . ':' . $row['language'] . ' ' . $row['approval']['canonical'] );
}
WP_CLI::success( count( $batch['variants'] ) . ( $apply ? ' variant(s) approved.' : ' variant(s) checked. Dry run only; no postmeta changed.' ) );
