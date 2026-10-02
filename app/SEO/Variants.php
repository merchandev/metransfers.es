<?php
namespace MeTransfers\SEO;

final class Variants {
	const SCHEMA_VERSION = 2;

	/** Material changes invalidate a review; saving an unchanged post does not. */
	public static function fingerprint( $post ): string {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}
		$custom   = (string) get_post_meta( $post->ID, '_wp_page_template', true );
		$template = \MeTransfers\I18n\Router::fixedTemplate( $post->post_name );
		if ( (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
			$template = 'front-page.php';
		} elseif ( $custom && 'default' !== $custom && is_file( get_template_directory() . '/' . $custom ) ) {
			$template = $custom;
		} elseif ( ! $template ) {
			$dedicated = 'page-' . $post->post_name . '.php';
			$template  = 'ruta' === $post->post_type ? 'single-ruta.php' : ( 'post' === $post->post_type ? 'single.php' : ( is_file( get_template_directory() . '/' . $dedicated ) ? $dedicated : 'page.php' ) );
		}
		$hashes = array();
		foreach ( array( $template, 'app/I18n/EditorialEnglish.php' ) as $relative ) {
			$file                = get_template_directory() . '/' . $relative;
			$hashes[ $relative ] = is_file( $file ) ? hash_file( 'sha256', $file ) : '';
		}
		return hash( 'sha256', wp_json_encode( array( $post->post_content, $post->post_title, $post->post_excerpt, $hashes, get_post_meta( $post->ID, '_yoast_wpseo_title', true ), get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ) ) ) );
	}

	/** Retain approvals created by the previous CLI tool until their source changes. */
	public static function legacyFingerprint( $post ): string {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}
		$template = 'ruta' === $post->post_type ? 'single-ruta.php' : 'page.php';
		$file     = get_template_directory() . '/' . $template;
		return hash( 'sha256', wp_json_encode( array( $post->post_content, $post->post_title, $post->post_modified, is_file( $file ) ? hash_file( 'sha256', $file ) : '', get_post_meta( $post->ID, '_yoast_wpseo_title', true ), get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ) ) ) );
	}

	public static function canonical( $post, string $language ): string {
		return \MeTransfers\I18n\Language::urlForLanguage( $language, trim( (string) parse_url( get_permalink( $post ), PHP_URL_PATH ), '/' ) );
	}

	public static function isApproved( $post, string $language ): bool {
		$post = get_post( $post );
		if ( ! $post || ! Indexability::isIndexableLanguage( $language ) ) {
			return false;
		}
		if ( 'es' === $language ) {
			return true;
		}
		$approval  = get_post_meta( $post->ID, '_mt_seo_variant_' . $language, true );
		$canonical = self::canonical( $post, $language );
		$hash      = is_array( $approval ) && self::SCHEMA_VERSION === ( $approval['schema_version'] ?? 0 ) ? self::fingerprint( $post ) : self::legacyFingerprint( $post );
		return is_array( $approval ) && ! empty( $approval['translated_reviewed'] )
			&& 200 === ( $approval['http_status'] ?? 0 )
			&& ( $approval['canonical'] ?? '' ) === $canonical
			&& ( $approval['source_hash'] ?? '' ) === $hash;
	}
}
