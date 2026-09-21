<?php
/**
 * WP-CLI: wp eval-file tools/fix-blog-slugs.php manifest.json [--apply]
 *
 * Renames blog post slugs that were left over from an earlier, unrelated
 * topic after the post's title/content were overwritten with new content
 * (see HISTORIAL.md, 21 sep 2026). Only renames post_name; title, content,
 * excerpt and all other fields are untouched. Registers each old slug in
 * the `mt_blog_slug_redirects` option so BlogSlugRedirects 301s it to the
 * new one instead of 404ing.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'WP-CLI required.' );
}
$manifest_path = $args[0] ?? '';
$apply         = in_array( '--apply', $args, true );
$manifest      = is_file( $manifest_path ) ? json_decode( file_get_contents( $manifest_path ), true ) : null;
if ( ! is_array( $manifest ) || empty( $manifest['posts'] ) || empty( $manifest['version'] ) ) {
	WP_CLI::error( 'Invalid manifest.' );
}

$snapshot   = array();
$errors     = array();
$seen_ids   = array();
$seen_slugs = array();
foreach ( $manifest['posts'] as $row ) {
	$post = get_post( $row['id'] ?? 0 );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
		$errors[] = 'Missing or unpublished post: ' . ( $row['id'] ?? 0 );
		continue;
	}
	if ( $post->post_name !== ( $row['old_slug'] ?? '' ) || $post->post_title !== ( $row['title'] ?? '' ) ) {
		$errors[] = 'Post ' . $post->ID . ' changed since the manifest was built (slug or title no longer match) -- skipping to avoid overwriting a newer edit.';
		continue;
	}
	if ( in_array( $post->ID, $seen_ids, true ) ) {
		$errors[] = 'Duplicate id in manifest: ' . $post->ID;
	}
	$seen_ids[] = $post->ID;

	$new_slug = sanitize_title( (string) ( $row['new_slug'] ?? '' ) );
	if ( '' === $new_slug ) {
		$errors[] = 'Empty new_slug for post ' . $post->ID;
		continue;
	}
	if ( isset( $seen_slugs[ $new_slug ] ) ) {
		$errors[] = 'new_slug collides within the manifest itself: ' . $new_slug . ' (posts ' . $seen_slugs[ $new_slug ] . ' and ' . $post->ID . ')';
	}
	$seen_slugs[ $new_slug ] = $post->ID;

	$existing = get_page_by_path( $new_slug, OBJECT, array( 'post', 'page', 'ruta' ) );
	if ( $existing && (int) $existing->ID !== (int) $post->ID ) {
		$errors[] = 'new_slug already used by another post: ' . $new_slug . ' (post ' . $existing->ID . ')';
	}

	$snapshot[ $post->ID ] = array( 'post' => $post->to_array(), 'meta' => get_post_meta( $post->ID ) );
	WP_CLI::log( $post->ID . ' /' . $post->post_name . '/ -> /' . $new_slug . '/' );
}
if ( $errors ) {
	WP_CLI::error( implode( "\n", $errors ) );
}
if ( ! $apply ) {
	WP_CLI::success( 'Dry run only (' . count( $manifest['posts'] ) . ' posts). No posts or options changed. Re-run with --apply once this looks right.' );
	return;
}

$backup_key = 'mt_blog_slugs_backup_' . sanitize_key( $manifest['version'] );
if ( ! add_option( $backup_key, $snapshot, '', false ) ) {
	WP_CLI::error( 'Backup already exists; refusing to overwrite it or reapply this migration. Bump the manifest version to run it again.' );
}

$redirect_map = get_option( \MeTransfers\SEO\BlogSlugRedirects::OPTION, array() );
if ( ! is_array( $redirect_map ) ) {
	$redirect_map = array();
}

foreach ( $manifest['posts'] as $row ) {
	$new_slug = sanitize_title( (string) $row['new_slug'] );
	$result   = wp_update_post( array( 'ID' => $row['id'], 'post_name' => $new_slug ), true );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( 'Migration stopped at post ' . $row['id'] . '. Restore from ' . $backup_key . ': ' . $result->get_error_message() );
	}
	$redirect_map[ $row['old_slug'] ] = $new_slug;
}

update_option( \MeTransfers\SEO\BlogSlugRedirects::OPTION, $redirect_map, false );

WP_CLI::success(
	count( $manifest['posts'] ) . ' post slugs renamed. Backup: ' . $backup_key .
	'. ' . count( $redirect_map ) . ' redirects now registered in the ' . \MeTransfers\SEO\BlogSlugRedirects::OPTION . ' option ' .
	'(active once the theme release containing BlogSlugRedirects is deployed).'
);
