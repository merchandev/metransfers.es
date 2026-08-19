<?php
namespace MeTransfers\I18n;

final class Seo {
    public function register() {
        add_filter( 'wpseo_canonical', array( __CLASS__, 'filterCanonical' ) );
        add_action( 'wp_head', array( __CLASS__, 'renderHead' ), 2 );
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
        $slug = Language::pathWithoutLanguage( $request_uri );
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
