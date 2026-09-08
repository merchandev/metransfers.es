<?php
namespace MeTransfers\SEO;

final class UrlPolicy {
	public static function postForPath( string $path ) {
		$path = trim( $path, '/' );
		if ( '' === $path ) {
			return get_post( (int) get_option( 'page_on_front' ) );
		}
		$id = url_to_postid( home_url( '/' . $path . '/' ) );
		return $id ? get_post( $id ) : get_page_by_path( $path, OBJECT, array( 'page', 'post', 'ruta' ) );
	}

	public static function eligibleTarget( string $path, string $language ): bool {
		$path = trim( $path, '/' );
		if ( ! Indexability::isIndexableLanguage( $language ) ) {
			return false;
		}
		if ( 'rutas' === $path ) {
			return 'es' === $language && post_type_exists( 'ruta' );
		}
		$post = self::postForPath( $path );
		if ( ! $post || ! Indexability::isIndexable( $post, false ) ) {
			return false;
		}
		// A transition exemption preserves URLs; it must not authorize consolidation.
		if ( 'ruta' === $post->post_type && '1' !== get_post_meta( $post->ID, '_mt_seo_ready', true ) ) {
			return false;
		}
		return Variants::isApproved( $post, $language );
	}
}
