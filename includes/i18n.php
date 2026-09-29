<?php
/**
 * Backward-compatible facade for the modular MeTransfers i18n services.
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'MT_LANGS' ) ) {
    // Los dos únicos idiomas del tema. El español de España (es_ES) es la
    // lengua nativa y de origen de todo el contenido; el inglés es la única
    // traducción. El traductor solo trabaja español -> inglés e
    // inglés -> español (Translation::PAIRS); ningún otro idioma tiene
    // contenido, selector, traducción, hreflang ni sitemap.
    //
    // Los 9 códigos que existieron entre el 16 y el 21 de septiembre de 2026
    // (ar, ca, de, fr, it, ja, pt, ru, zh) se retiraron por completo, y sus
    // datos guardados (caché de traducción, aprobaciones SEO) los borra la
    // migración 20260928_001. Lo único que queda de ellos es la redirección
    // 301 de sus URLs antiguas, ya indexadas por Google, hacia el español
    // (Redirects::RETIRED_LANGUAGES): quitarla convertiría esas URLs en 404.
    define(
        'MT_LANGS',
        array(
            'es' => array(
                'label'       => 'ES',
                'name'        => 'Español (España)',
                'locale'      => 'es_ES',
                'hreflang'    => 'es-ES',
                'google_code' => 'es',
            ),
            'en' => array(
                'label'       => 'EN',
                'name'        => 'English',
                'locale'      => 'en_US',
                'hreflang'    => 'en-US',
                'google_code' => 'en',
            ),
        )
    );
}

if ( ! defined( 'MT_ACTIVE_LANGS' ) ) {
    define(
        'MT_ACTIVE_LANGS',
        array( 'es', 'en' )
    );
}

if ( ! defined( 'MT_SEO_LANGS' ) ) {
    define(
        'MT_SEO_LANGS',
        array( 'es', 'en' )
    );
}

function mt_get_current_lang(): string {
    return \MeTransfers\I18n\Language::detectFromUri( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
}

function mt_lang(): string {
    return \MeTransfers\I18n\Language::get();
}

function mt_is_translated(): bool {
    return \MeTransfers\I18n\Language::isTranslated();
}

function mt_translate( string $text, string $lang = '' ): string {
    return (string) \MeTransfers\I18n\Translation::translate( $text, $lang );
}

function mt_translate_batch( array $texts, string $lang = '' ): array {
    return \MeTransfers\I18n\Translation::batch( $texts, $lang );
}

function mt_translate_batch_remote( array $texts, string $lang ): array {
    return \MeTransfers\I18n\Translation::remoteBatch( $texts, $lang );
}

function mt_localized_url( string $path = '' ): string {
    return \MeTransfers\SEO\Links::normalize( \MeTransfers\I18n\Language::url( $path ) );
}

function gct_render_language_switcher(): void {
    \MeTransfers\I18n\Switcher::render();
}

function mt_i18n_settings_page(): void {
    \MeTransfers\I18n\Admin::render();
}

function mt_translate_content( $content ) {
    return \MeTransfers\I18n\Translation::translate( $content );
}

function mt_translate_title( $title, $id = null ) {
    return \MeTransfers\I18n\Translation::translateTitle( $title, $id );
}

function mt_translate_excerpt( $excerpt ) {
    return \MeTransfers\I18n\Translation::translate( $excerpt );
}

add_filter( 'wp_nav_menu_objects', function( $items ) {
	foreach ( $items as $item ) {
		$item->title = \MeTransfers\I18n\Translation::translate( $item->title );
	}
	return $items;
}, 99 );

\MeTransfers\I18n\Language::boot();
( new \MeTransfers\I18n\Router() )->register();
( new \MeTransfers\I18n\Translation() )->register();
( new \MeTransfers\I18n\Switcher() )->register();
( new \MeTransfers\I18n\Seo() )->register();
( new \MeTransfers\I18n\Admin() )->register();
