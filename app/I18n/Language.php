<?php
namespace MeTransfers\I18n;

final class Language {
	private static $current = 'es';

	public static function boot() {
		self::set( self::detectFromUri( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' ) );
		add_filter( 'locale', array( __CLASS__, 'filterLocale' ) );
		add_filter( 'language_attributes', array( __CLASS__, 'filterAttributes' ) );
	}

	public static function detectFromUri( $request_uri, ?array $active_languages = null ) {
		$active_languages = null === $active_languages
			? ( defined( 'MT_ACTIVE_LANGS' ) ? MT_ACTIVE_LANGS : array( 'es' ) )
			: $active_languages;
		$path             = trim( (string) parse_url( (string) $request_uri, PHP_URL_PATH ), '/' );
		$segments         = '' === $path ? array() : explode( '/', $path );
		$candidate        = isset( $segments[0] ) ? strtolower( (string) $segments[0] ) : '';

		return 'es' !== $candidate && in_array( $candidate, $active_languages, true ) ? $candidate : 'es';
	}

	public static function set( $language ) {
		$language                   = strtolower( preg_replace( '/[^a-z]/', '', (string) $language ) );
		$active                     = defined( 'MT_ACTIVE_LANGS' ) ? MT_ACTIVE_LANGS : array( 'es' );
		self::$current              = in_array( $language, $active, true ) ? $language : 'es';
		$GLOBALS['mt_current_lang'] = self::$current;
	}

	public static function get() {
		return self::$current;
	}

	public static function isTranslated() {
		return 'es' !== self::$current;
	}

	public static function filterLocale( $locale ) {
		$map = array(
			'en' => 'en_US',
			'fr' => 'fr_FR',
			'de' => 'de_DE',
			'it' => 'it_IT',
			'pt' => 'pt_PT',
			'ca' => 'ca',
			'ru' => 'ru_RU',
			'zh' => 'zh_CN',
			'ja' => 'ja',
			'ar' => 'ar',
		);
		return isset( $map[ self::$current ] ) ? $map[ self::$current ] : $locale;
	}

	public static function filterAttributes( $output ) {
		return self::isTranslated() ? 'lang="' . esc_attr( self::$current ) . '"' : $output;
	}

	public static function pathWithoutLanguage( $request_uri ) {
		$path = trim( (string) parse_url( (string) $request_uri, PHP_URL_PATH ), '/' );
		if ( '' === $path ) {
			return '';
		}
		$segments = explode( '/', $path );
		if ( defined( 'MT_LANGS' ) && isset( MT_LANGS[ $segments[0] ] ) ) {
			array_shift( $segments );
		}
		return implode( '/', $segments );
	}

	public static function url( $path = '' ) {
		return self::urlForLanguage( self::$current, $path );
	}

	public static function urlForLanguage( $language, $path = '' ) {
		$path   = trim( (string) $path, '/' );
		$suffix = $path ? $path . '/' : '';
		if ( 'es' === $language ) {
			return home_url( '/' . $suffix );
		}
		return home_url( '/' . $language . '/' . $suffix );
	}
}
