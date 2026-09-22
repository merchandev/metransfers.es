<?php
namespace MeTransfers\SEO;

final class Redirects {
	/**
	 * Única lista de códigos de idioma retirados en todo el tema (ver
	 * includes/i18n.php, donde MT_LANGS documenta que solo es/en son
	 * idiomas reales). Estos 9 códigos no tienen contenido, selector,
	 * traducción ni hreflang -- solo se reconocen aquí para poder
	 * consolidar con 301 cualquier URL antigua ya indexada o enlazada
	 * bajo su prefijo hacia el canónico español. Añadir o quitar un
	 * idioma retirado se hace únicamente en esta constante.
	 *
	 * @var string[]
	 */
	private const RETIRED_LANGUAGES = array( 'fr', 'de', 'it', 'pt', 'ca', 'ru', 'zh', 'ja', 'ar' );

	public function register() {
		add_action( 'template_redirect', array( $this, 'processRedirects' ), 0 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'protectLocalizedCanonical' ), 10, 2 );
	}

	public static function protectLocalizedCanonical( $redirect_url, $requested_url ) {
		$languages = defined( 'MT_ACTIVE_LANGS' ) ? MT_ACTIVE_LANGS : array( 'es' );
		$match     = \MeTransfers\I18n\Router::matchRequest( (string) $requested_url, $languages );
		return null !== $match ? false : $redirect_url;
	}

	public static function targetForRequest( string $request_uri, array $languages ): ?string {
		$path     = trim( (string) parse_url( $request_uri, PHP_URL_PATH ), '/' );
		$segments = '' === $path ? array() : explode( '/', $path );
		$language = 'es';

		if ( ! empty( $segments ) && in_array( $segments[0], self::RETIRED_LANGUAGES, true ) ) {
			array_shift( $segments );
			$slug   = implode( '/', $segments );
			$target = LegacyUrlMap::getTarget( $slug );
			$target = null !== $target ? $target : $slug;
			$query  = parse_url( $request_uri, PHP_URL_QUERY );
			$url    = '/' . trim( $target, '/' );
			$url    = '/' === $url ? '/' : $url . '/';
			return $url . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
		}

		if ( ! empty( $segments ) && in_array( $segments[0], $languages, true ) ) {
			$language = array_shift( $segments );
		}

		$slug   = implode( '/', $segments );
		$target = LegacyUrlMap::getTarget( $slug );
		$seen   = array( $slug );
		while ( null !== $target ) {
			if ( in_array( $target, $seen, true ) ) {
				return null;
			}
			$seen[] = $target;
			$next   = LegacyUrlMap::getTarget( $target );
			if ( null === $next ) {
				$target = rtrim( '/' . ( 'es' === $language ? '' : $language . '/' ) . trim( $target, '/' ), '/' ) . '/';
				$query  = parse_url( $request_uri, PHP_URL_QUERY );
				return $target . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
			}
			$target = $next;
		}
		return null;
	}

	public function processRedirects() {
		if ( is_admin() || wp_doing_ajax() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) {
			return;
		}
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$target  = self::verifiedTarget( $request );
		if ( null !== $target ) {
			wp_safe_redirect( home_url( $target ), 301 );
			exit;
		}
	}

	public static function verifiedTarget( string $request ): ?string {
		$languages = defined( 'MT_ACTIVE_LANGS' ) ? MT_ACTIVE_LANGS : array( 'es' );
		$target    = self::targetForRequest( $request, $languages );
		if ( null === $target ) {
			return null;
		}

		$path        = \MeTransfers\I18n\Language::pathWithoutLanguage( $target );
		$target_lang = \MeTransfers\I18n\Language::detectFromUri( $target, $languages );
		if ( UrlPolicy::eligibleTarget( $path, $target_lang ) ) {
			return $target;
		}

		// Una variante traducida no aprobada nunca debe convertir un alias
		// histórico válido en 404. Se consolida hacia el canónico español.
		if ( 'es' !== $target_lang && UrlPolicy::eligibleTarget( $path, 'es' ) ) {
			$query = parse_url( $target, PHP_URL_QUERY );
			$url   = '/' . trim( $path, '/' );
			$url   = '/' === $url ? '/' : $url . '/';
			return $url . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
		}

		return null;
	}
}
