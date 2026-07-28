<?php
/**
 * FAQ page helpers.
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ME_TRANSFERS_FAQ_SYNC_VERSION' ) ) {
	define( 'ME_TRANSFERS_FAQ_SYNC_VERSION', '2026-07-20-2' );
}

/**
 * Returns the FAQ items for the FAQ page.
 *
 * @return array<int, array<string, mixed>>
 */
function me_transfers_get_faq_items() {
	return array(
		array(
			'question' => '¿Cómo puedo reservar un traslado privado?',
			'answer'   => array(
				'Puedes reservar tu traslado directamente en nuestra web en menos de 2 minutos.',
				'Solo elige el origen, destino y vehículo. Recibirás confirmación inmediata y podrás pagar de forma 100% segura.',
			),
		),
		array(
			'question' => '¿Qué tipos de vehículos ofrecen?',
			'answer'   => array(
				'Contamos con una flota moderna y diversa: sedanes estándar, minivans familiares, sedanes ejecutivos y minivans V-Class de lujo.',
				'Todos nuestros vehículos disponen de aire acondicionado, limpieza impecable y conductores profesionales.',
			),
		),
		array(
			'question' => '¿Puedo cancelar mi reserva?',
			'answer'   => array(
				'Sí. Puedes cancelar tu traslado sin coste hasta 24 horas antes.',
				'Es una opción ideal si cambian tus planes de viaje.',
			),
		),
		array(
			'question' => '¿Dónde me encuentro con el conductor en el aeropuerto?',
			'answer'   => array(
				'Tu conductor te esperará en la sala de llegadas con un cartel con tu nombre.',
				'Si lo prefieres, también puedes indicar otro punto de encuentro personalizado.',
			),
		),
		array(
			'question' => '¿Ofrecen traslados a otras ciudades fuera de Barcelona?',
			'answer'   => array(
				'Sí. Realizamos transfers privados desde Barcelona a ciudades como Madrid, Valencia, Andorra, Sitges y Costa Brava, entre otras.',
			),
		),
		array(
			'question' => '¿Qué pasa si mi vuelo se retrasa?',
			'answer'   => array(
				'No te preocupes. Monitorizamos los vuelos en tiempo real.',
				'Si tu vuelo se retrasa, ajustamos la recogida automáticamente sin recargos.',
			),
		),
		array(
			'question' => '¿Puedo solicitar una silla para niños?',
			'answer'   => array(
				'Sí. Ofrecemos sillas de bebé y elevadores para niños sin coste adicional.',
				'Solo tienes que indicarlo durante la reserva.',
			),
		),
		array(
			'question' => '¿Cómo puedo contactar con atención al cliente?',
			'answer'   => array(
				'Estamos disponibles 24/7 por teléfono, email y WhatsApp.',
				'Recibirás una atención rápida y personalizada.',
			),
		),
		array(
			'question' => '¿Qué métodos de pago aceptan?',
			'answer'   => array(
				'Puedes pagar de forma segura con tarjetas de crédito o débito, Apple Pay, Google Pay y transferencias electrónicas seguras.',
			),
		),
		array(
			'question' => '¿Ofrecen servicio de chófer por horas?',
			'answer'   => array(
				'Sí. Ofrecemos alquiler de coche con conductor por horas para eventos, reuniones o visitas privadas.',
				'Tú decides el horario y el itinerario.',
			),
		),
		array(
			'question' => '¿Cómo gestionan mis datos personales?',
			'answer'   => array(
				'En Metransfers Barcelona nos tomamos muy en serio la privacidad de tus datos.',
				'Puedes consultar nuestra polÍtica de privacidad completa desde el enlace correspondiente del sitio.',
			),
		),
	);
}

/**
 * Determines whether the current page is the FAQ page.
 *
 * @param WP_Post|int|null $post Post object or ID.
 * @return bool
 */
function me_transfers_is_faq_page( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || 'page' !== $post->post_type ) {
		return false;
	}

	return 'preguntas-frecuentes' === sanitize_title( $post->post_name ? $post->post_name : $post->post_title );
}

/**
 * Returns the FAQ page URL.
 *
 * @return string
 */
function me_transfers_get_faq_page_url() {
	$page = get_page_by_path( 'preguntas-frecuentes', OBJECT, 'page' );

	return $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/preguntas-frecuentes/' );
}

/**
 * Creates the FAQ page if it does not exist.
 *
 * @return void
 */
function me_transfers_sync_faq_page() {
	if ( function_exists( 'wp_installing' ) && wp_installing() ) {
		return;
	}

	$stored_version = get_option( 'me_transfers_faq_sync_version' );

	if ( ME_TRANSFERS_FAQ_SYNC_VERSION === $stored_version ) {
		return;
	}

	$page = get_page_by_path( 'preguntas-frecuentes', OBJECT, 'page' );

	if ( ! $page instanceof WP_Post ) {
		$page_result = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'MeTransfers Barcelona - Preguntas Frecuentes',
				'post_name'    => 'preguntas-frecuentes',
				'post_content' => '',
			),
			true
		);

		$page = is_wp_error( $page_result ) ? false : get_post( (int) $page_result );
	}

	if ( $page instanceof WP_Post ) {
		update_post_meta( $page->ID, '_me_transfers_page_role', 'faq' );
	}

	update_option( 'me_transfers_faq_sync_version', ME_TRANSFERS_FAQ_SYNC_VERSION, false );
}
add_action( 'init', 'me_transfers_sync_faq_page', 20 );

/**
 * Syncs FAQ page in admin if required.
 *
 * @return void
 */
function me_transfers_maybe_sync_faq_page() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	me_transfers_sync_faq_page();
}
add_action( 'admin_init', 'me_transfers_maybe_sync_faq_page' );

/**
 * Forces FAQ page sync on theme activation.
 *
 * @return void
 */
function me_transfers_force_faq_page_sync() {
	delete_option( 'me_transfers_faq_sync_version' );
	me_transfers_sync_faq_page();
}
add_action( 'after_switch_theme', 'me_transfers_force_faq_page_sync' );

