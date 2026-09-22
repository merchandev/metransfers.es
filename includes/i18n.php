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
    // Única lista de idiomas reales del tema: contenido propio, selector,
    // traducción automática, hreflang y sitemap. Español e inglés son los
    // únicos idiomas soportados; no queda ningún otro código documentado
    // aquí a propósito, para que esta constante sea la fuente de verdad
    // exacta y no se pueda confundir con un idioma "medio soportado".
    //
    // Los 9 códigos que existieron entre el 16 y el 21 de septiembre de 2026
    // (ar, ca, de, fr, it, ja, pt, ru, zh) se retiraron por completo del
    // tema: no tienen contenido, no aparecen en el selector, no se traducen
    // y no se anuncian por hreflang. Las URLs antiguas bajo esos prefijos
    // (ya indexadas o enlazadas desde fuera) se siguen reconociendo y
    // consolidando con 301 hacia su equivalente español -- exclusivamente
    // en Redirects::RETIRED_LANGUAGES, la única lista que debe tocarse si
    // algún día hay que añadir o quitar un idioma retirado.
    define(
        'MT_LANGS',
        array(
            'es' => array( 'label' => 'ES', 'name' => 'Español', 'google_code' => 'es' ),
            'en' => array( 'label' => 'EN', 'name' => 'English (US)', 'google_code' => 'en' ),
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
