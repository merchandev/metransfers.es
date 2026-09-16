<?php
namespace MeTransfers\SEO;

final class Variants {
	public static function fingerprint( $post ): string {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}
		$template = 'ruta' === $post->post_type ? 'single-ruta.php' : 'page.php';
		$file     = get_template_directory() . '/' . $template;
		return hash( 'sha256', wp_json_encode( array( $post->post_content, $post->post_title, $post->post_modified, is_file( $file ) ? hash_file( 'sha256', $file ) : '', get_post_meta( $post->ID, '_yoast_wpseo_title', true ), get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ) ) ) );
	}

	public static function isApproved( $post, string $language ): bool {
		$post = get_post( $post );
		if ( ! $post || ! Indexability::isIndexableLanguage( $language ) ) {
			return false;
		}
		if ( 'es' === $language ) {
			return true;
		}
		$approval = get_post_meta( $post->ID, '_mt_seo_variant_' . $language, true );

		// Grandfathered: si no existe ningún metadato de aprobación/rechazo, la
		// variante se considera válida para indexación. Solo un registro explícito
		// con datos inválidos (http_status != 200, translated_reviewed vacío, etc.)
		// bloquea la indexación. Esto es consistente con el comportamiento de las
		// rutas históricas en Indexability::isIndexable().
		if ( ! is_array( $approval ) || empty( $approval ) ) {
			return true;
		}

		$canonical = \MeTransfers\I18n\Language::urlForLanguage( $language, trim( (string) parse_url( get_permalink( $post ), PHP_URL_PATH ), '/' ) );
		return ! empty( $approval['translated_reviewed'] )
			&& 200 === ( $approval['http_status'] ?? 0 )
			&& ( $approval['canonical'] ?? '' ) === $canonical
			&& self::fingerprint( $post ) === ( $approval['source_hash'] ?? '' );
	}
}
