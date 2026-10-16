<?php
namespace MeTransfers\Newsletter;

/**
 * The website's look, for the places that cannot load style.css: emails and
 * the confirm/unsubscribe pages. Values are taken from the live site (style.css
 * tokens and the booking form): navy sections, amber eyebrows, Outfit
 * headings, Inter text, white cards and the navy «Buscar vehículos» button.
 */
final class Brand {
	const NAVY       = '#0B1F35';
	const NAVY_DEEP  = '#051126';
	const AMBER      = '#FFB547';
	const BLUE       = '#075EA8';
	const TEXT       = '#0F172A';
	const TEXT_SOFT  = '#334155';
	const MUTED      = '#64748B';
	const PAGE       = '#F0F5FA';
	const BORDER     = '#DCE6F1';
	const ON_DARK    = '#C9D3DF';
	const FONT_TITLE = "'Outfit', 'Segoe UI', Arial, sans-serif";
	const FONT_BODY  = "'Inter', 'Segoe UI', Arial, sans-serif";
	const FONTS_URL  = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@600;700&display=swap';

	/**
	 * The white logo of the site header (Appearance → Customize → Logo),
	 * or the theme's copy.
	 */
	public static function logoUrl() {
		$logo_id = function_exists( 'get_theme_mod' ) ? (int) get_theme_mod( 'custom_logo' ) : 0;
		if ( $logo_id && function_exists( 'wp_get_attachment_image_url' ) ) {
			$url = wp_get_attachment_image_url( $logo_id, 'medium_large' );
			if ( $url ) {
				return (string) $url;
			}
		}
		return get_template_directory_uri() . '/assets/img/' . rawurlencode( 'MT - MeTransfers.png' );
	}

	/**
	 * Inline style of the main button, as the site's booking button.
	 */
	public static function buttonStyle() {
		return 'display:inline-block;background:' . self::NAVY . ';color:#ffffff;padding:15px 30px;border-radius:16px;font-family:' . self::FONT_BODY . ';font-size:16px;font-weight:700;letter-spacing:0.3px;text-decoration:none';
	}

	public static function button( $url, $label ) {
		return '<p style="text-align:center;margin:30px 0 8px"><a href="' . esc_url( $url ) . '" style="' . esc_attr( self::buttonStyle() ) . '">' . esc_html( $label ) . '</a></p>';
	}

	/**
	 * Header band: logo on navy, as the site header; an optional amber eyebrow.
	 */
	public static function header( $eyebrow = '', $email = false ) {
		return '<tr><td style="background:' . self::NAVY . ';padding:26px 24px 22px;text-align:center">'
			. ( $email
				? '<p style="margin:0;color:#ffffff;font-family:' . esc_attr( self::FONT_TITLE ) . ';font-size:24px;font-weight:700">MeTransfers</p>'
				: '<img src="' . esc_url( self::logoUrl() ) . '" width="190" alt="MeTransfers" style="display:inline-block;width:190px;max-width:70%;height:auto;border:0;color:#ffffff;font-family:' . esc_attr( self::FONT_TITLE ) . ';font-size:24px;font-weight:700">' )
			. ( '' !== $eyebrow ? '<p style="margin:14px 0 0;color:' . self::AMBER . ';font-family:' . esc_attr( self::FONT_BODY ) . ';font-size:12px;letter-spacing:2px;text-transform:uppercase">' . esc_html( $eyebrow ) . '</p>' : '' )
			. '</td></tr>';
	}
}
