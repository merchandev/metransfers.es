<?php
/**
 * WP-CLI: wp eval-file tools/seo-approve-variant.php manifest.json [--apply]
 *
 * Marks a non-Spanish language variant of a post/route as reviewed and ready
 * for indexing, by writing the `_mt_seo_variant_{lang}` postmeta that
 * Variants::isApproved() requires (see HISTORIAL.md, 21 sep 2026, Ronda 7).
 * Without this, every non-ES variant stays permanently noindex regardless of
 * translation quality, because nothing else in the codebase ever sets that
 * meta. This script verifies the variant URL resolves with 200 and captures
 * a content fingerprint, but cannot judge translation quality -- --apply
 * refuses any row without an explicit editorial_approved=true, which stays a
 * human decision.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'WP-CLI required.' );
}
$manifest_path = $args[0] ?? '';
$apply         = in_array( '--apply', $args, true );
$manifest      = is_file( $manifest_path ) ? json_decode( file_get_contents( $manifest_path ), true ) : null;
if ( ! is_array( $manifest ) || empty( $manifest['variants'] ) || empty( $manifest['version'] ) ) {
	WP_CLI::error( 'Invalid manifest.' );
}

$errors = array();
$seen   = array();
foreach ( $manifest['variants'] as $row ) {
	$post = get_post( $row['id'] ?? 0 );
	$lang = (string) ( $row['language'] ?? '' );
	if ( ! $post || 'publish' !== $post->post_status ) {
		$errors[] = 'Missing or unpublished post: ' . ( $row['id'] ?? 0 );
		continue;
	}
	$key = $post->ID . ':' . $lang;
	if ( in_array( $key, $seen, true ) ) {
		$errors[] = 'Duplicate row in manifest: ' . $key;
	}
	$seen[] = $key;

	if ( ! \MeTransfers\SEO\Indexability::isIndexableLanguage( $lang ) ) {
		$errors[] = 'Language not active/eligible for SEO: ' . $lang . ' (post ' . $post->ID . ')';
		continue;
	}
	if ( ! \MeTransfers\SEO\Indexability::isIndexable( $post ) ) {
		$errors[] = 'Post is not indexable in Spanish, so its variant cannot be approved either: ' . $post->ID;
		continue;
	}
	if ( $apply && empty( $row['editorial_approved'] ) ) {
		$errors[] = 'Editorial approval missing for ' . $key . ' -- a human must confirm the translation is publish-ready before --apply.';
	}

	$path      = trim( (string) parse_url( get_permalink( $post ), PHP_URL_PATH ), '/' );
	$canonical = \MeTransfers\I18n\Language::urlForLanguage( $lang, $path );
	$response  = wp_remote_get(
		$canonical,
		array(
			'timeout'     => 15,
			'redirection' => 0,
		)
	);
	$status    = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
	if ( 200 !== $status ) {
		$errors[] = 'Variant URL did not respond 200 (got ' . $status . '): ' . $canonical;
		continue;
	}

	WP_CLI::log( $key . ' ' . $canonical . ' -> ' . ( $apply ? 'approved for indexing' : 'would be approved for indexing' ) );

	if ( ! $apply ) {
		continue;
	}
	update_post_meta(
		$post->ID,
		'_mt_seo_variant_' . $lang,
		array(
			'translated_reviewed' => true,
			'http_status'         => $status,
			'canonical'           => $canonical,
			'source_hash'         => \MeTransfers\SEO\Variants::fingerprint( $post ),
			'approved_at'         => gmdate( 'c' ),
		)
	);
}
if ( $errors ) {
	WP_CLI::error( implode( "\n", $errors ) );
}
if ( ! $apply ) {
	WP_CLI::success( 'Dry run only (' . count( $manifest['variants'] ) . ' variants). No postmeta changed. Re-run with --apply once each row has editorial_approved=true.' );
	return;
}
WP_CLI::success( count( $manifest['variants'] ) . ' variant(s) approved for indexing.' );
