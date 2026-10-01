<?php
/** WP-CLI: wp eval-file tools/restore-blog-slugs.php manifest.json [--apply] */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'WP-CLI required.' );
}
$manifest_path = $args[0] ?? '';
$apply = in_array( '--apply', $args, true );
$manifest = is_file( $manifest_path ) ? json_decode( file_get_contents( $manifest_path ), true ) : null;
if ( ! is_array( $manifest ) || empty( $manifest['posts'] ) || empty( $manifest['version'] ) ) {
	WP_CLI::error( 'Invalid manifest.' );
}
$backup_key = 'mt_blog_slugs_backup_' . sanitize_key( $manifest['version'] );
$snapshot = get_option( $backup_key );
$previous_redirects = get_option( $backup_key . '_redirects', null );
$redirects = get_option( \MeTransfers\SEO\BlogSlugRedirects::OPTION, array() );
if ( ! is_array( $snapshot ) || ! is_array( $previous_redirects ) || ! is_array( $redirects ) ) {
	WP_CLI::error( 'Complete backup required.' );
}
$restores = array();
foreach ( $manifest['posts'] as $row ) {
	$id = (int) ( $row['id'] ?? 0 );
	$post = get_post( $id );
	$old = $snapshot[ $id ]['post'] ?? null;
	if ( ! $post || ! $old || $post->post_status !== $old['post_status'] || $post->post_type !== $old['post_type'] ) {
		WP_CLI::error( 'Post missing or status changed: ' . $id );
	}
	$expected = $old;
	$expected['post_name'] = sanitize_title( $row['new_slug'] );
	if ( ! empty( $row['new_content'] ) ) {
		$expected['post_title'] = $row['new_title'];
		$expected['post_content'] = $row['new_content'];
	}
	if ( isset( $row['new_excerpt'] ) ) {
		$expected['post_excerpt'] = $row['new_excerpt'];
	}
	$original = true;
	$applied = true;
	foreach ( array( 'post_name', 'post_title', 'post_content', 'post_excerpt' ) as $field ) {
		$original = $original && $post->{$field} === $old[ $field ];
		$applied = $applied && $post->{$field} === $expected[ $field ];
	}
	if ( ! $original && ! $applied ) {
		WP_CLI::error( 'Post ' . $id . ' has a newer edit. Rollback refused.' );
	}
	$collision = get_page_by_path( $old['post_name'], OBJECT, array( 'post', 'page', 'ruta' ) );
	if ( $collision && (int) $collision->ID !== $id ) {
		WP_CLI::error( 'Original slug now belongs to another post: ' . $old['post_name'] );
	}
	$key = $row['old_slug'];
	if ( isset( $redirects[ $key ] ) && $redirects[ $key ] !== $row['new_slug'] && $redirects[ $key ] !== ( $previous_redirects[ $key ] ?? null ) ) {
		WP_CLI::error( 'Redirect changed since migration: ' . $key );
	}
	$canonical = get_post_meta( $id, '_yoast_wpseo_canonical', true );
	$old_canonical = $snapshot[ $id ]['meta']['_yoast_wpseo_canonical'][0] ?? '';
	$old_url = $snapshot[ $id ]['permalink'];
	$restore_canonical = $old_canonical && rtrim( $old_canonical, '/' ) === rtrim( $old_url, '/' );
	if ( $restore_canonical && $canonical !== $old_canonical && $canonical !== get_permalink( $post ) ) {
		WP_CLI::error( 'Manual canonical edited since migration: ' . $id );
	}
	$restores[] = array( 'id' => $id, 'old' => $old, 'key' => $key, 'canonical' => $restore_canonical ? $old_canonical : null );
	WP_CLI::log( 'Restore post ' . $id . ' to /' . $old['post_name'] . '/' );
}
if ( ! $apply ) {
	WP_CLI::success( 'Dry run only. No changes.' );
	return;
}
foreach ( $restores as $restore ) {
	$old = $restore['old'];
	$result = wp_update_post( array(
		'ID' => $restore['id'],
		'post_name' => $old['post_name'],
		'post_title' => wp_slash( $old['post_title'] ),
		'post_content' => wp_slash( $old['post_content'] ),
		'post_excerpt' => wp_slash( $old['post_excerpt'] ),
	), true );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
	if ( null !== $restore['canonical'] ) {
		update_post_meta( $restore['id'], '_yoast_wpseo_canonical', $restore['canonical'] );
	}
	if ( array_key_exists( $restore['key'], $previous_redirects ) ) {
		$redirects[ $restore['key'] ] = $previous_redirects[ $restore['key'] ];
	} else {
		unset( $redirects[ $restore['key'] ] );
	}
	update_option( \MeTransfers\SEO\BlogSlugRedirects::OPTION, $redirects, false );
}
WP_CLI::success( 'Original URLs and copy restored; backup retained.' );
