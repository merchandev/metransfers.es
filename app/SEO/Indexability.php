<?php
namespace MeTransfers\SEO;

final class Indexability {
	/**
	 * Determina si un post (ID u objeto) es apto para indexación y sitemap.
	 */
	public static function isIndexable( $post = null, bool $check_redirect = true ): bool {
		$post = get_post( $post );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}

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

		$permalink = get_permalink( $post );
		$path      = trim( (string) parse_url( $permalink, PHP_URL_PATH ), '/' );

		// Las URLs legacy nunca deben volver al sitemap. Se comprueba el ledger
		// directamente, no el resultado final de verifiedTarget(), para que una
		// incidencia temporal del destino no convierta otra vez el alias en 200 SEO.
		if ( $check_redirect && LegacyUrlMap::hasRedirect( $path ) ) {
			return false;
		}

		// Toda la antigua familia /destinos/ queda consolidada bajo /rutas/.
		if ( 0 === strpos( $path, 'destinos/' ) ) {
			return false;
		}

		if ( ! empty( $post->post_password )
			|| '1' === get_post_meta( $post->ID, '_mt_seo_noindex', true )
			|| '1' === get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex', true ) ) {
			return false;
		}

		$canonical = get_post_meta( $post->ID, '_yoast_wpseo_canonical', true );
		if ( $canonical && rtrim( $canonical, '/' ) !== rtrim( $permalink, '/' ) ) {
			return false;
		}

		// Una ruta publicada se considera heredada/apta cuando el metadato no
		// existe. Solo el valor explícito "0" bloquea la indexación. Esto evita
		// desindexaciones masivas al migrar rutas creadas antes del checklist SEO.
		if ( 'ruta' === $post->post_type && '0' === (string) get_post_meta( $post->ID, '_mt_seo_ready', true ) ) {
			return false;
		}

		return true;
	}

	public static function isIndexableLanguage( string $language ): bool {
		return in_array( $language, defined( 'MT_ACTIVE_LANGS' ) ? MT_ACTIVE_LANGS : array( 'es' ), true )
			&& in_array( $language, defined( 'MT_SEO_LANGS' ) ? MT_SEO_LANGS : array( 'es' ), true );
	}

	/**
	 * Las landings antiguas /destinos/ ya no son canónicas.
	 */
	public static function isIndexableDestination( string $slug ): bool {
		return false;
	}

	public static function languagesForPost( $post, array $languages ): array {
		return self::isIndexable( $post )
			? array_values(
				array_filter(
					$languages,
					static function ( $language ) use ( $post ) {
						return Variants::isApproved( $post, $language );
					}
				)
			)
			: array();
	}

	public static function isProduction(): bool {
		return wp_get_environment_type() === 'production'
			&& in_array(
				parse_url( home_url(), PHP_URL_HOST ),
				apply_filters( 'mt_seo_production_hosts', array( 'metransfers.es', 'www.metransfers.es' ) ),
				true
			);
	}

	public static function isIndexableRequest(): bool {
		if ( '0' === (string) get_option( 'blog_public', '1' ) || ! self::isProduction() ) {
			return false;
		}
		if ( is_404() || is_search() || is_tag() || is_author() || is_date() || is_attachment() ) {
			return false;
		}
		if ( ! self::isIndexableLanguage( \MeTransfers\I18n\Language::get() ) ) {
			return false;
		}
		if ( \MeTransfers\I18n\Language::isTranslated() ) {
			$path = \MeTransfers\I18n\Language::pathWithoutLanguage( $_SERVER['REQUEST_URI'] ?? '/' );
			$post = UrlPolicy::postForPath( $path );
			return $post
				&& self::isIndexable( $post )
				&& Variants::isApproved( $post, \MeTransfers\I18n\Language::get() );
		}
		return ! is_singular() || self::isIndexable( get_queried_object_id() );
	}
}
