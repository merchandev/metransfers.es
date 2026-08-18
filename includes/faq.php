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
                'Puedes iniciar la reserva directamente desde nuestra web indicando origen, destino, fecha, hora y número de pasajeros.',
                'Antes de finalizar podrás revisar las opciones disponibles, el precio y las condiciones aplicables. La reserva se considera confirmada cuando recibes la confirmación correspondiente.',
            ),
        ),

        array(
            'question' => '¿Qué tipos de vehículos ofrecen?',
            'answer'   => array(
                'Disponemos de berlinas y minivans para traslados privados, servicios corporativos, familias y grupos.',
                'El vehículo se asigna según el número de pasajeros, el equipaje y las características del servicio.',
            ),
        ),

        array(
            'question' => '¿Puedo cancelar mi reserva?',
            'answer'   => array(
                'Las reservas estándar pueden cancelarse gratuitamente hasta 24 horas antes del servicio, salvo que durante la contratación se indiquen condiciones particulares diferentes.',
            ),
        ),

        array(
            'question' => '¿Dónde me encuentro con el conductor en el aeropuerto?',
            'answer'   => array(
                'En las recogidas de aeropuerto, el chófer te espera en el punto de encuentro indicado en la confirmación del servicio.',
                'Cuando corresponda, podrá esperarte en la zona de llegadas con un cartel identificativo.',
            ),
        ),

        array(
            'question' => '¿Realizan traslados fuera de Barcelona?',
            'answer'   => array(
                'Sí. Organizamos traslados privados de corta y larga distancia desde y hacia Barcelona, incluyendo destinos de Cataluña, Andorra y otras ciudades.',
            ),
        ),

        array(
            'question' => '¿Qué ocurre si mi vuelo se retrasa?',
            'answer'   => array(
                'Cuando facilitas correctamente el número de vuelo podemos revisar su estado y adaptar la coordinación de la recogida a la hora real de llegada.',
                'Los retrasos prolongados, tiempos de espera adicionales o cambios que afecten a la disponibilidad se gestionan según las condiciones aplicables al servicio.',
            ),
        ),

        array(
            'question' => '¿Puedo solicitar una silla infantil?',
            'answer'   => array(
                'Sí. Puedes solicitar silla infantil o elevador durante la reserva.',
                'Indica la edad y el peso aproximado del menor para poder confirmar la opción adecuada y su disponibilidad.',
            ),
        ),

        array(
            'question' => '¿Cómo puedo contactar con atención al cliente?',
            'answer'   => array(
                'Puedes contactar con MeTransfers por teléfono, correo electrónico o WhatsApp.',
                'El servicio de atención está disponible 24 horas para consultas relacionadas con los servicios contratados.',
            ),
        ),

        array(
            'question' => '¿Qué métodos de pago aceptan?',
            'answer'   => array(
                'Los métodos de pago disponibles se muestran durante el proceso de contratación antes de confirmar la reserva.',
                'Los pagos online se procesan mediante una pasarela segura.',
            ),
        ),

        array(
            'question' => '¿Ofrecen servicio de chófer por horas?',
            'answer'   => array(
                'Sí. Puedes contratar un vehículo con chófer por horas para reuniones, eventos, compras, cenas, visitas privadas o itinerarios con varias paradas.',
                'Indícanos el horario y el recorrido aproximado para preparar el servicio.',
            ),
        ),

        array(
            'question' => '¿Cómo gestionan mis datos personales?',
            'answer'   => array(
                'Tratamos los datos necesarios para gestionar tus solicitudes y prestar los servicios contratados.',
                'Puedes consultar toda la información en nuestra Política de Privacidad.',
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
// [MIGRACIÓN MANUAL] Hook desactivado
// add_action( 'init', 'me_transfers_sync_faq_page', 20 );

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
// [MIGRACIÓN MANUAL] Hook desactivado
// add_action( 'admin_init', 'me_transfers_maybe_sync_faq_page' );

/**
 * Forces FAQ page sync on theme activation.
 *
 * @return void
 */
function me_transfers_force_faq_page_sync() {
	delete_option( 'me_transfers_faq_sync_version' );
	me_transfers_sync_faq_page();
}
// [MIGRACIÓN MANUAL] Hook desactivado
// add_action( 'after_switch_theme', 'me_transfers_force_faq_page_sync' );

