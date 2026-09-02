<?php
namespace MeTransfers\I18n;

final class Seo {
	public function register() {
		add_filter( 'wpseo_canonical', array( __CLASS__, 'filterCanonical' ) );
		add_action( 'wp_head', array( __CLASS__, 'renderHead' ), 2 );

		// Yoast SEO Translation Filters
		add_filter( 'wpseo_title', array( __CLASS__, 'translateSeo' ), 99 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'translateSeo' ), 99 );
		add_filter( 'wpseo_opengraph_title', array( __CLASS__, 'translateSeo' ), 99 );
		add_filter( 'wpseo_opengraph_desc', array( __CLASS__, 'translateSeo' ), 99 );
		add_filter( 'wpseo_twitter_title', array( __CLASS__, 'translateSeo' ), 99 );
		add_filter( 'wpseo_twitter_description', array( __CLASS__, 'translateSeo' ), 99 );
	}

	public static function translateSeo( $text ) {
		if ( ! Language::isTranslated() || empty( $text ) ) {
			return $text;
		}

		// First try translating the exact whole string (if user manually entered a custom Yoast title/desc)
		$translated = Translation::translate( $text );
		if ( $translated !== $text ) {
			return $translated;
		}

		// If it's a generated Yoast title (e.g. "Page Name - Site Name"), it won't be in the exact cache.
		// We split by common separators and translate the individual pieces (which ARE in the cache).
		$separators = array( ' - ', ' | ', ' &ndash; ', ' &mdash; ', ' &#8211; ', ' &#8212; ', ' / ', ' &raquo; ' );
		foreach ( $separators as $sep ) {
			if ( strpos( $text, $sep ) !== false ) {
				$parts = explode( $sep, $text );
				foreach ( $parts as &$part ) {
					$part = Translation::translate( trim( $part ) );
				}
				return implode( $sep, $parts );
			}
		}

		return $text;
	}

	public static function canonicalForRequest( $canonical, $request_uri, $language ) {
		if ( 'es' === $language ) {
			return $canonical;
		}
		$path = (string) parse_url( (string) $request_uri, PHP_URL_PATH );
		$path = '/' . ltrim( $path, '/' );
		return home_url( $path );
	}

	public static function filterCanonical( $canonical ) {

		if ( \MeTransfers\HotelPortal\HotelPortal::isPortalRequest() ) {
				return $canonical;
		}
		return self::canonicalForRequest(
			$canonical,
			isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/',
			Language::get()
		);
	}

	public static function alternatesForRequest( $request_uri, array $seo_languages ) {
		if ( count( $seo_languages ) <= 1 ) {
			return array();
		}
		$slug  = Language::pathWithoutLanguage( $request_uri );
		$links = array();
		foreach ( $seo_languages as $language ) {
			if ( ! defined( 'MT_LANGS' ) || ! isset( MT_LANGS[ $language ] ) ) {
				continue;
			}
			$links[ self::hreflang( $language ) ] = Language::urlForLanguage( $language, $slug );
		}
		$links['x-default'] = Language::urlForLanguage( 'es', $slug );
		return $links;
	}

	public static function renderHead() {

		if ( \MeTransfers\HotelPortal\HotelPortal::isPortalRequest() ) {
			return;
		}
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		if ( ! defined( 'WPSEO_VERSION' ) && Language::isTranslated() ) {
			echo '<link rel="canonical" href="' . esc_url( self::canonicalForRequest( '', $request_uri, Language::get() ) ) . '" />' . "\n";
		}
		$seo_languages = defined( 'MT_SEO_LANGS' ) ? MT_SEO_LANGS : array( 'es' );
		foreach ( self::alternatesForRequest( $request_uri, $seo_languages ) as $language => $url ) {
			echo '<link rel="alternate" hreflang="' . esc_attr( $language ) . '" href="' . esc_url( $url ) . '" />' . "\n";
		}
	}

	private static function hreflang( $language ) {
		return 'zh' === $language ? 'zh-Hans' : $language;
	}
}
