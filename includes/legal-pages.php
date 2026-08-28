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
        'politica-de-privacidad' => 'Política de privacidad',
        'terminos-y-condiciones' => 'Términos y condiciones',
        'aviso-legal'            => 'Aviso legal',
        'politica-de-cookies'    => 'Política de cookies',
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
		$page = get_page_by_path( $slug, 'OBJECT', 'page' );

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
// [MIGRACIÓN MANUAL] Hook desactivado
// add_action( 'admin_init', 'me_transfers_maybe_sync_legal_pages' );

/**
 * Forces legal pages sync on theme activation.
 *
 * @return void
 */
function me_transfers_force_legal_pages_sync() {
	delete_option( 'me_transfers_legal_pages_sync_version' );
	me_transfers_sync_legal_pages();
}
// [MIGRACIÓN MANUAL] Hook desactivado
// add_action( 'after_switch_theme', 'me_transfers_force_legal_pages_sync' );

/**
 * Redirige slugs legales históricos hacia las URLs legales actuales.
 *
 * Funciona incluso cuando la URL antigua ya devuelve 404.
 */
function me_transfers_redirect_legacy_legal_pages() {

    if ( is_admin() ) {
        return;
    }

    $request_path = wp_parse_url(
        wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ),
        PHP_URL_PATH
    );

    $path = trailingslashit(
        '/' . trim( (string) $request_path, '/' )
    );

    $redirects = array(
        '/cookie/'     => '/politica-de-cookies/',
        '/cookies/'    => '/politica-de-cookies/',
        '/privacidad/' => '/politica-de-privacidad/',
    );

    if ( ! isset( $redirects[ $path ] ) ) {
        return;
    }

    wp_safe_redirect(
        home_url( $redirects[ $path ] ),
        301
    );

    exit;
}

add_action(
    'template_redirect',
    'me_transfers_redirect_legacy_legal_pages',
    1
);

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
		$page = get_page_by_path( $slug, 'OBJECT', 'page' );

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
// [MIGRACIÓN MANUAL] Hook desactivado
// add_action( 'init', 'me_transfers_repair_legal_titles_utf8' );
