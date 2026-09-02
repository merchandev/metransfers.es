<?php
/**
 * SEO page title synchronization helpers.
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ME_TRANSFERS_SEO_PAGE_TITLES_VERSION' ) ) {
	define( 'ME_TRANSFERS_SEO_PAGE_TITLES_VERSION', '2026-07-20-1' );
}

/**
 * Returns the SEO-oriented page title for one destination.
 *
 * @param string $destination_title Destination title.
 * @return string
 */
function me_transfers_get_destination_seo_page_title( $destination_title ) {
	return sprintf( 'MeTransfers Barcelona - Traslado privado a %s desde Barcelona', $destination_title );
}

/**
 * Returns a slug-to-title map for non-destination pages.
 *
 * @return array<string, string>
 */
function me_transfers_get_static_seo_page_titles() {
	return array(
		'destinos'               => 'MeTransfers Barcelona - Destinos de traslados privados desde Barcelona',
		'preguntas-frecuentes'   => 'MeTransfers Barcelona - Preguntas frecuentes sobre traslados privados en Barcelona',
		'finalizar-pago'         => 'MeTransfers Barcelona - Pago seguro de reserva de traslado privado',
		'finalizar-reserva'      => 'MeTransfers Barcelona - Confirmar reserva de traslado privado',
		'reservas-hotel'         => 'MeTransfers Barcelona - Reservas de traslados para hoteles en Barcelona',
	);
}

/**
 * Syncs SEO-focused WordPress page titles for generated and key site pages.
 *
 * @return void
 */
function me_transfers_sync_seo_page_titles() {
	$stored_version = get_option( 'me_transfers_seo_page_titles_version' );

	if ( ME_TRANSFERS_SEO_PAGE_TITLES_VERSION === $stored_version ) {
		return;
	}

	foreach ( me_transfers_get_static_seo_page_titles() as $slug => $seo_title ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $page instanceof WP_Post && $page->post_title !== $seo_title ) {
			wp_update_post(
				array(
					'ID'         => $page->ID,
					'post_title' => $seo_title,
				)
			);
		}
	}

	if ( function_exists( 'me_transfers_get_destination_catalog' ) ) {
		foreach ( me_transfers_get_destination_catalog() as $destination ) {
			$page = me_transfers_find_destination_page( $destination['slug'] );

			if ( $page instanceof WP_Post ) {
				$seo_title = me_transfers_get_destination_seo_page_title( $destination['title'] );

				if ( $page->post_title !== $seo_title ) {
					wp_update_post(
						array(
							'ID'         => $page->ID,
							'post_title' => $seo_title,
						)
					);
				}
			}
		}
	}

	update_option( 'me_transfers_seo_page_titles_version', ME_TRANSFERS_SEO_PAGE_TITLES_VERSION, false );
}
// add_action( 'init', 'me_transfers_sync_seo_page_titles', 30 );

/**
 * Forces a resync of page titles on theme activation.
 *
 * @return void
 */
function me_transfers_force_seo_page_title_sync() {
	delete_option( 'me_transfers_seo_page_titles_version' );
	me_transfers_sync_seo_page_titles();
}
// add_action( 'after_switch_theme', 'me_transfers_force_seo_page_title_sync' );
