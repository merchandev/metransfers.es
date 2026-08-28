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
    define(
        'MT_LANGS',
        array(
            'es' => array( 'label' => 'ES', 'name' => 'Español', 'google_code' => 'es' ),
            'en' => array( 'label' => 'EN', 'name' => 'English', 'google_code' => 'en' ),
            'fr' => array( 'label' => 'FR', 'name' => 'Français', 'google_code' => 'fr' ),
            'de' => array( 'label' => 'DE', 'name' => 'Deutsch', 'google_code' => 'de' ),
            'it' => array( 'label' => 'IT', 'name' => 'Italiano', 'google_code' => 'it' ),
            'pt' => array( 'label' => 'PT', 'name' => 'Português', 'google_code' => 'pt' ),
            'ca' => array( 'label' => 'CA', 'name' => 'Català', 'google_code' => 'ca' ),
            'ru' => array( 'label' => 'RU', 'name' => 'Русский', 'google_code' => 'ru' ),
            'zh' => array( 'label' => 'ZH', 'name' => '中文', 'google_code' => 'zh-CN' ),
            'ja' => array( 'label' => 'JA', 'name' => '日本語', 'google_code' => 'ja' ),
            'ar' => array( 'label' => 'AR', 'name' => 'العربية', 'google_code' => 'ar' ),
        )
    );
}

if ( ! defined( 'MT_ACTIVE_LANGS' ) ) {
    define( 'MT_ACTIVE_LANGS', array( 'es', 'en', 'fr', 'de', 'it', 'pt', 'ca', 'ru', 'zh', 'ja', 'ar' ) );
}

if ( ! defined( 'MT_SEO_LANGS' ) ) {
    // A language enters this allowlist only after human SEO/content acceptance.
    define( 'MT_SEO_LANGS', array( 'es', 'en', 'fr', 'ru', 'zh', 'de', 'it', 'pt', 'ca', 'nl' ) );
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
    return \MeTransfers\I18n\Language::url( $path );
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
