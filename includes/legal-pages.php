<?php
/**
 * Legal pages helpers.
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ME_TRANSFERS_LEGAL_PAGES_SYNC_VERSION' ) ) {
	define( 'ME_TRANSFERS_LEGAL_PAGES_SYNC_VERSION', '2026-07-20-2' );
}

/**
 * Returns legal pages managed by the theme.
 *
 * @return array<string, string>
 */
function me_transfers_get_legal_pages_catalog() {
	return array(
		'privacidad'             => 'MeTransfers Barcelona - PolÍticas de privacidad',
		'terminos-y-condiciones' => 'MeTransfers Barcelona - Términos y Condiciones regulan la contratación',
		'aviso-legal'            => 'MeTransfers Barcelona - Aviso Legal',
		'cookie'                 => 'MeTransfers Barcelona - PolÍtica de Cookies',
	);
}

/**
 * Creates legal pages if they do not exist.
 *
 * @return void
 */
function me_transfers_sync_legal_pages() {
	if ( function_exists( 'wp_installing' ) && wp_installing() ) {
		return;
	}

	$stored_version = get_option( 'me_transfers_legal_pages_sync_version' );

	if ( ME_TRANSFERS_LEGAL_PAGES_SYNC_VERSION === $stored_version ) {
		return;
	}

	foreach ( me_transfers_get_legal_pages_catalog() as $slug => $title ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( ! $page instanceof WP_Post ) {
			$page_result = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => '',
				),
				true
			);

			$page = is_wp_error( $page_result ) ? false : get_post( (int) $page_result );
		}

		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_me_transfers_page_role', 'legal' );
		}
	}

	update_option( 'me_transfers_legal_pages_sync_version', ME_TRANSFERS_LEGAL_PAGES_SYNC_VERSION, false );
}
// add_action( 'init', 'me_transfers_sync_legal_pages', 20 );

/**
 * Syncs legal pages in admin when required.
 *
 * @return void
 */
function me_transfers_maybe_sync_legal_pages() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	me_transfers_sync_legal_pages();
}
add_action( 'admin_init', 'me_transfers_maybe_sync_legal_pages' );

/**
 * Forces legal pages sync on theme activation.
 *
 * @return void
 */
function me_transfers_force_legal_pages_sync() {
	delete_option( 'me_transfers_legal_pages_sync_version' );
	me_transfers_sync_legal_pages();
}
add_action( 'after_switch_theme', 'me_transfers_force_legal_pages_sync' );

/**
 * Redirects the legacy cookies slug to the active route.
 *
 * @return void
 */
function me_transfers_redirect_legacy_cookies_page() {
	if ( is_admin() || ! is_page( 'cookies' ) ) {
		return;
	}

	wp_safe_redirect( home_url( '/cookie' ), 301 );
	exit;
}
add_action( 'template_redirect', 'me_transfers_redirect_legacy_cookies_page' );

/**
 * Repairs mojibake UTF-8 errors in legal page titles.
 * To be removed once run.
 */
function me_transfers_repair_legal_titles_utf8() {
	if ( get_option( 'me_transfers_legal_titles_utf8_v2' ) ) {
		return;
	}

	$catalog = me_transfers_get_legal_pages_catalog();

	foreach ( $catalog as $slug => $correct_title ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( ! $page instanceof WP_Post ) {
			continue;
		}

		$role = get_post_meta( $page->ID, '_me_transfers_page_role', true );

		if ( 'legal' !== $role ) {
			continue;
		}

		if ( $page->post_title !== $correct_title ) {
			wp_update_post(
				array(
					'ID'         => $page->ID,
					'post_title' => $correct_title,
				)
			);
		}
	}

	update_option( 'me_transfers_legal_titles_utf8_v2', 1, false );
}
add_action( 'init', 'me_transfers_repair_legal_titles_utf8' );
