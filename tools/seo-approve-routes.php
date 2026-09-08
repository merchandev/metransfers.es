<?php
/** WP-CLI: wp eval-file tools/seo-approve-routes.php manifest.json [--apply] */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'WP-CLI required.' );
}
$manifest_path = $args[0] ?? '';
$apply = in_array( '--apply', $args, true );
$manifest = is_file( $manifest_path ) ? json_decode( file_get_contents( $manifest_path ), true ) : null;
if ( ! is_array( $manifest ) || empty( $manifest['routes'] ) || empty( $manifest['version'] ) ) {
	WP_CLI::error( 'Invalid manifest.' );
}
$snapshot = array();
$errors = array();
$ids = array();
foreach ( $manifest['routes'] as $row ) {
	$post = get_post( $row['id'] ?? 0 );
	if ( ! $post || 'ruta' !== $post->post_type || 'publish' !== $post->post_status || $post->post_name !== ( $row['slug'] ?? '' ) ) {
		$errors[] = 'Missing or changed route: ' . ( $row['id'] ?? 0 );
		continue;
	}
	if ( in_array( $post->ID, $ids, true ) || $post->post_modified !== $row['expected_modified'] || $post->post_content !== $row['expected_content'] ) {
		$errors[] = 'Concurrent change or duplicate: ' . $post->ID;
	}
	$ids[] = $post->ID;
	foreach ( array( 'title', 'description', 'h1', 'content' ) as $field ) {
		if ( empty( trim( $row[ $field ] ?? '' ) ) ) { $errors[] = 'Empty ' . $field . ': ' . $post->ID; }
	}
	if ( false === strpos( $row['content'] ?? '', '<h3>' ) ) { $errors[] = 'Missing FAQ: ' . $post->ID; }
	if ( $apply && empty( $row['editorial_approved'] ) ) { $errors[] = 'Editorial approval missing: ' . $post->ID; }
	$snapshot[ $post->ID ] = array( 'post' => $post->to_array(), 'meta' => get_post_meta( $post->ID ) );
	WP_CLI::log( $post->ID . ' ' . $post->post_name . ' -> content, H1, title, description, _mt_seo_ready=1' );
}
if ( $errors ) { WP_CLI::error( implode( "\n", $errors ) ); }
if ( ! $apply ) {
	WP_CLI::success( 'Dry run only. No posts or options changed. Individual editorial approval is required for --apply.' );
	return;
}
$backup_key = 'mt_seo_backup_' . sanitize_key( $manifest['version'] );
if ( ! add_option( $backup_key, $snapshot, '', false ) ) {
	WP_CLI::error( 'Backup already exists; refusing to overwrite it or reapply the migration.' );
}
foreach ( $manifest['routes'] as $row ) {
	$result = wp_update_post( array( 'ID' => $row['id'], 'post_content' => wp_slash( $row['content'] ) ), true );
	if ( is_wp_error( $result ) ) { WP_CLI::error( 'Migration stopped. Restore from ' . $backup_key . ': ' . $result->get_error_message() ); }
	update_post_meta( $row['id'], '_mt_ruta_h1', $row['h1'] );
	update_post_meta( $row['id'], '_yoast_wpseo_title', $row['title'] );
	update_post_meta( $row['id'], '_yoast_wpseo_metadesc', $row['description'] );
	update_post_meta( $row['id'], '_mt_seo_ready', '1' );
}
WP_CLI::success( 'Only the selected routes were updated. Backup: ' . $backup_key . '. Global strict enforcement remains unchanged.' );
