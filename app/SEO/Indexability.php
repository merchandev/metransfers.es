<?php
namespace MeTransfers\SEO;

final class Indexability {

	/**
	 * Determina si un post (ID o objeto) es apto para ser indexado y añadido al sitemap.
	 *
	 * Condiciones:
	 * - Post publicado (HTTP 200)
	 * - No es una redirección / alias legacy (301)
	 * - No es un contenido duplicado (self-canonical)
	 * - No es 404, 410 ni está marcado como noindex.
	 * - No es una página transaccional (checkout, gracias, reservas-hotel)
	 */
	public static function isIndexable( $post = null ): bool {
		$post = get_post( $post );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}

		// Filtrar páginas transaccionales o de sistema
		$excluded_slugs = array(
			'reservaciones',
			'reservas-hotel',
			'reservas-metransfers',
			'seleccionar-vehiculo',
			'pago',
			'finalizar-pago',
			'gracias',
		);
		if ( in_array( $post->post_name, $excluded_slugs, true ) ) {
			return false;
		}

		// Filtrar si es un alias legacy que debe ser redirigido
		$path = trim( (string) parse_url( get_permalink( $post ), PHP_URL_PATH ), '/' );
		if ( LegacyUrlMap::hasRedirect( $path ) ) {
			return false;
		}

		// Excluir si tiene meta noindex (manual desde WP u otro sistema)
		if ( ! empty( $post->post_password ) || '1' === get_post_meta( $post->ID, '_mt_seo_noindex', true ) || '1' === get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex', true ) ) {
			return false;
		}
		$canonical = get_post_meta( $post->ID, '_yoast_wpseo_canonical', true );
		if ( $canonical && rtrim( $canonical, '/' ) !== rtrim( get_permalink( $post ), '/' ) ) {
			return false;
		}
		if ( 'page' === $post->post_type && function_exists( 'me_transfers_get_current_destination' ) ) {
			$destination = me_transfers_get_current_destination( $post );
			if ( $destination && ! self::isIndexableDestination( $destination['slug'] ) ) {
				return false;
			}
		}

		// Si es una ruta, debe estar lista para SEO (fallback para lógica previa)
		if ( 'ruta' === $post->post_type ) {
			if ( '1' !== get_post_meta( $post->ID, '_mt_seo_ready', true ) ) {
				return false;
			}
		}

		return true;
	}

	public static function isIndexableLanguage( string $language ): bool {
		return in_array( $language, defined( 'MT_ACTIVE_LANGS' ) ? MT_ACTIVE_LANGS : array( 'es' ), true )
			&& in_array( $language, defined( 'MT_SEO_LANGS' ) ? MT_SEO_LANGS : array( 'es' ), true );
	}

	public static function isIndexableDestination( string $slug ): bool {
		return in_array( $slug, array( 'salou', 'lloret-de-mar' ), true );
	}

	public static function languagesForPost( $post, array $languages ): array {
		return self::isIndexable( $post )
			? array_values( array_filter( $languages, array( __CLASS__, 'isIndexableLanguage' ) ) )
			: array();
	}

	public static function isIndexableRequest(): bool {
		if ( '0' === (string) get_option( 'blog_public', '1' ) || wp_get_environment_type() !== 'production' ) {
			return false;
		}
		if ( is_404() || is_search() || is_tag() || is_author() || is_date() || is_attachment() ) {
			return false;
		}
		if ( ! self::isIndexableLanguage( \MeTransfers\I18n\Language::get() ) ) {
			return false;
		}
		return ! is_singular() || self::isIndexable( get_queried_object_id() );
	}
}
