<?php
/**
 * Destination hub and destination-request helpers.
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ME_TRANSFERS_DESTINATIONS_SYNC_VERSION' ) ) {
	define( 'ME_TRANSFERS_DESTINATIONS_SYNC_VERSION', '2026-07-20-3' );
}

/**
 * Returns the destination catalog used by the theme.
 *
 * @return array<string, array<string, mixed>>
 */
function me_transfers_get_destination_catalog() {
	static $catalog = null;

	if ( null !== $catalog ) {
		return $catalog;
	}

	$catalog = array();

	// 1. Destinos configurados manualmente con contenido específico y SEO local
	$catalog['salou'] = array(
		'title'       => 'Salou',
		'slug'        => 'salou',
		'h1'          => 'Transfer privado a Salou',
		'order'       => 1,
		'related'     => array( 'cambrils', 'tarragona', 'reus', 'portaventura', 'pineda-de-mar' ),
		'summary'     => 'Reserva tu transfer privado a Salou desde el Aeropuerto de Reus (a 10 km), el Aeropuerto de Barcelona (a 95 km), la estación del AVE Camp de Tarragona o cualquier hotel. Viaja cómodo, sin esperas y a precio cerrado.',
		'travel_note' => 'Salou es el epicentro de la Costa Dorada, conocido por sus playas, su animada vida turística y su cercanía a PortAventura World. Coordinamos tu llegada directa a tu hotel, apartamento o resort con la máxima puntualidad.',
		'highlights' => array(
			'Recogida puntual en el Aeropuerto de Reus, Aeropuerto de Barcelona o estación del AVE.',
			'Vehículos adecuados para familias con niños, grupos grandes o equipaje especial.',
			'Ruta directa por autopista (AP-7) para que llegues a Salou de forma rápida y segura.',
		),
	);

	$catalog['lloret-de-mar'] = array(
		'title'       => 'Lloret de Mar',
		'slug'        => 'lloret-de-mar',
		'h1'          => 'Transfer privado a Lloret de Mar',
		'order'       => 2,
		'related'     => array( 'tossa-de-mar', 'calella', 'malgrat', 'costa-brava', 'blanes' ),
		'summary'     => 'Reserva tu traslado privado a Lloret de Mar desde el Aeropuerto de Girona (a 30 km) o el Aeropuerto de Barcelona (a 90 km). Disfruta de un viaje directo a la capital de la Costa Brava Sur con chófer profesional.',
		'travel_note' => 'Lloret de Mar es uno de los destinos más populares de la Costa Brava, ideal para vacaciones de sol y playa. Ofrecemos traslados puerta a puerta para que no dependas de autobuses o trenes con transbordos.',
		'highlights' => array(
			'Conexión directa y privada desde el Aeropuerto de Girona o El Prat hasta tu hotel en Lloret.',
			'Flota de sedanes, minivans y minibuses para adaptarnos a parejas, familias o grupos de amigos.',
			'Tarifa plana sin sorpresas ni recargos por tráfico o retrasos en el vuelo.',
		),
	);

	// 2. Destinos genéricos que aún no tienen contenido específico
	$generic_titles = array(
		'Madrid', 'Palamos', 'Baqueira Beret', 'Tossa de Mar', 'La Escala', 'Cap de Creus',
		'Calella de Palafrugell', 'Begur', 'Costa Brava', 'Santa Susanna', 'Cadaques', 'Marbella',
		'Roses', 'Camping El Delfin Verde', 'Tarragona', 'Perpignan', 'Figueres', 'Almeria',
		'Sitges', 'Vall de Nuria', 'Calella', 'Cambrils', 'Lourdes', 'Santiago de Compostela',
		'Valencia', 'Granada', 'Andorra', 'Malgrat', 'San Sebastian', 'Bilbao', 'Benidorm',
		'Vigo', 'Sevilla', 'Reus', 'PortAventura', 'Pineda de Mar'
	);

	$index = 3;
	foreach ( $generic_titles as $title ) {
		$slug             = sanitize_title( $title );
		$catalog[ $slug ] = array(
			'title'       => $title,
			'slug'        => $slug,
			'h1'          => $title, // Fallback genérico para H1
			'order'       => $index++,
			'related'     => array(), // Vacío por defecto
			'summary'     => sprintf(
				/* translators: %s: destination title. */
				__( 'Solicita información para tu traslado privado a %s con recogida en Barcelona, aeropuerto, hotel o puerto y un servicio premium adaptado a tu itinerario.', 'me-transfers' ),
				$title
			),
			'travel_note' => sprintf(
				/* translators: %s: destination title. */
				__( 'Coordinamos traslados hacia %s para viajeros particulares, familias, empresas, hoteles y eventos, con chófer profesional y atención personalizada.', 'me-transfers' ),
				$title
			),
			'highlights'  => array(
				__( 'Recogida puerta a puerta y presupuesto personalizado según horario, punto de salida y número de pasajeros.', 'me-transfers' ),
				sprintf(
					/* translators: %s: destination title. */
					__( 'Opciones de ida, ida y vuelta o disposición por horas para viajes hacia %s.', 'me-transfers' ),
					$title
				),
				__( 'Vehículos premium, seguimiento operativo y asistencia para hoteles, aeropuertos, cruceros y reuniones corporativas.', 'me-transfers' ),
			),
		);
	}

	return $catalog;
}

/**
 * Returns one destination item by slug or title.
 *
 * @param string $slug_or_title Slug or title.
 * @return array<string, mixed>|false
 */
function me_transfers_get_destination_item( $slug_or_title ) {
	$key     = sanitize_title( (string) $slug_or_title );
	$catalog = me_transfers_get_destination_catalog();

	return isset( $catalog[ $key ] ) ? $catalog[ $key ] : false;
}

/**
 * Determines if a page is the destinations hub.
 *
 * @param WP_Post|int|null $post Post object or ID.
 * @return bool
 */
function me_transfers_is_destinations_hub( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || 'page' !== $post->post_type ) {
		return false;
	}

	return 'destinos' === sanitize_title( $post->post_name ? $post->post_name : $post->post_title );
}

/**
 * Returns the current destination context for a page.
 *
 * @param WP_Post|int|null $post Post object or ID.
 * @return array<string, mixed>|false
 */
function me_transfers_get_current_destination( $post = null ) {
	$post = get_post( $post );

	if ( ! $post || 'page' !== $post->post_type || me_transfers_is_destinations_hub( $post ) ) {
		return false;
	}

	$slug = sanitize_title( $post->post_name ? $post->post_name : $post->post_title );

	return me_transfers_get_destination_item( $slug );
}

/**
 * Returns the hub page object when available.
 *
 * @return WP_Post|false
 */
function me_transfers_get_destinations_hub_page() {
	$page = get_page_by_path( 'destinos', OBJECT, 'page' );

	return $page instanceof WP_Post ? $page : false;
}

/**
 * Finds an existing page for one destination.
 *
 * @param string $slug Destination slug.
 * @return WP_Post|false
 */
function me_transfers_find_destination_page( $slug ) {
	static $cache = array();

	$slug = sanitize_title( (string) $slug );

	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}

	$page = get_page_by_path( $slug, OBJECT, 'page' );

	if ( ! $page ) {
		$page = get_page_by_path( 'destinos/' . $slug, OBJECT, 'page' );
	}

	$cache[ $slug ] = $page instanceof WP_Post ? $page : false;

	return $cache[ $slug ];
}

/**
 * Returns the destinations hub URL.
 *
 * @return string
 */
function me_transfers_get_destinations_hub_url() {
	$page = me_transfers_get_destinations_hub_page();

	return $page ? get_permalink( $page ) : home_url( '/destinos/' );
}

/**
 * Returns the public URL for one destination.
 *
 * @param array<string, mixed>|string $destination Destination item or slug.
 * @param bool                        $with_anchor Whether to append the form anchor.
 * @return string
 */
function me_transfers_get_destination_url( $destination, $with_anchor = false ) {
	$slug = is_array( $destination ) ? $destination['slug'] : sanitize_title( (string) $destination );
	$page = me_transfers_find_destination_page( $slug );
	$url  = $page ? get_permalink( $page ) : home_url( '/destinos/' . $slug . '/' );

	if ( $with_anchor ) {
		$url .= '#destination-request';
	}

	return $url;
}

/**
 * Creates the destinations hub and any missing destination pages.
 *
 * @return void
 */
function me_transfers_sync_destination_pages() {
	if ( function_exists( 'wp_installing' ) && wp_installing() ) {
		return;
	}

	$stored_version = get_option( 'me_transfers_destinations_sync_version' );

	if ( ME_TRANSFERS_DESTINATIONS_SYNC_VERSION === $stored_version ) {
		return;
	}

	$hub_page = me_transfers_get_destinations_hub_page();
	$hub_id   = $hub_page ? (int) $hub_page->ID : 0;

	if ( ! $hub_id ) {
		// Do not recreate the hub if it was intentionally trashed.
		$hub_trashed = get_page_by_path( 'destinos__trashed', OBJECT, 'page' );
		if ( ! $hub_trashed ) {
			$hub_result = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'MeTransfers Barcelona - Destinos de traslados privados desde Barcelona',
					'post_name'    => 'destinos',
					'post_content' => '',
				),
				true
			);

			$hub_id = is_wp_error( $hub_result ) ? 0 : (int) $hub_result;
		}
	}

	if ( $hub_id > 0 ) {
		$hub_post = get_post( $hub_id );
		$hub_new_title = 'MeTransfers Barcelona - Destinos de traslados privados desde Barcelona';
		if ( $hub_post && $hub_post->post_title !== $hub_new_title ) {
			wp_update_post( array(
				'ID'         => $hub_id,
				'post_title' => $hub_new_title,
			) );
		}
		update_post_meta( $hub_id, '_me_transfers_page_role', 'destinations_hub' );
	}

	foreach ( me_transfers_get_destination_catalog() as $destination ) {
		$top_level_page = get_page_by_path( $destination['slug'], OBJECT, 'page' );
		$child_page     = get_page_by_path( 'destinos/' . $destination['slug'], OBJECT, 'page' );
		$page_id        = 0;

		if ( $top_level_page instanceof WP_Post ) {
			$page_id = (int) $top_level_page->ID;
		} elseif ( $child_page instanceof WP_Post ) {
			$page_id = (int) $child_page->ID;
		} elseif ( $hub_id > 0 ) {
			// Do not recreate the destination page if it was intentionally trashed.
			$dest_trashed = get_page_by_path( $destination['slug'] . '__trashed', OBJECT, 'page' );
			if ( $dest_trashed ) {
				continue;
			}
			$page_result = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_parent'  => $hub_id,
					'post_title'   => 'MeTransfers Barcelona - Traslado privado a ' . $destination['title'] . ' desde Barcelona',
					'post_name'    => $destination['slug'],
					'post_content' => '',
				),
				true
			);

			$page_id = is_wp_error( $page_result ) ? 0 : (int) $page_result;
		}

		if ( $page_id > 0 ) {
			$new_title = 'MeTransfers Barcelona - Traslado privado a ' . $destination['title'] . ' desde Barcelona';
			$existing_post = get_post( $page_id );
			if ( $existing_post && $existing_post->post_title !== $new_title ) {
				wp_update_post( array(
					'ID'         => $page_id,
					'post_title' => $new_title,
				) );
			}
			update_post_meta( $page_id, '_me_transfers_page_role', 'destination' );
		}
	}

	update_option( 'me_transfers_destinations_sync_version', ME_TRANSFERS_DESTINATIONS_SYNC_VERSION, false );
}
// add_action( 'init', 'me_transfers_sync_destination_pages', 20 );

/**
 * Runs the destination sync after theme activation and in wp-admin when needed.
 *
 * @return void
 */
function me_transfers_maybe_sync_destination_pages() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	me_transfers_sync_destination_pages();
}
// DESACTIVADO POR AUDITORÍA SEO: El contenido debe publicarse manualmente
// add_action( 'admin_init', 'me_transfers_maybe_sync_destination_pages' );

/**
 * Forces a destination sync when the theme is activated.
 *
 * @return void
 */
function me_transfers_force_destination_page_sync() {
	delete_option( 'me_transfers_destinations_sync_version' );
	me_transfers_sync_destination_pages();
}
// add_action( 'after_switch_theme', 'me_transfers_force_destination_page_sync' );

/**
 * Returns the request notice for one destination page.
 *
 * @param string $slug Destination slug.
 * @return array<string, string>|false
 */
function me_transfers_get_destination_request_notice( $slug ) {
	$status          = isset( $_GET['mt_request'] ) ? sanitize_key( wp_unslash( $_GET['mt_request'] ) ) : '';
	$destination_key = isset( $_GET['mt_destination'] ) ? sanitize_title( wp_unslash( $_GET['mt_destination'] ) ) : '';

	if ( ! $status || $destination_key !== sanitize_title( $slug ) ) {
		return false;
	}

	if ( 'success' === $status ) {
		return array(
			'type'    => 'success',
			'message' => __( 'Hemos recibido tu solicitud. Te contactaremos lo antes posible con la información del traslado.', 'me-transfers' ),
		);
	}

	return array(
		'type'    => 'error',
		'message' => __( 'No pudimos enviar la solicitud. Revisa los campos obligatorios e intÃ©ntalo de nuevo.', 'me-transfers' ),
	);
}

/**
 * Builds one destination request form.
 *
 * @param array<string, mixed> $destination Destination item.
 * @return string
 */
function me_transfers_render_destination_request_form( $destination ) {
	$privacy_url = get_privacy_policy_url();
	$notice      = me_transfers_get_destination_request_notice( $destination['slug'] );

	ob_start();
	?>
		<div class="destination-request-widget">
			<?php if ( $notice ) : ?>
				<div class="destination-form-notice destination-form-notice--<?php echo esc_attr( $notice['type'] ); ?>">
					<?php echo esc_html( $notice['message'] ); ?>
				</div>
			<?php endif; ?>

			<form class="destination-request-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="me_transfers_destination_request">
				<input type="hidden" name="destination_slug" value="<?php echo esc_attr( $destination['slug'] ); ?>">
				<?php wp_nonce_field( 'me_transfers_destination_request', 'me_transfers_destination_nonce' ); ?>

				<div class="destination-request-grid">
					<div class="destination-request-field">
						<label for="mt-name"><?php esc_html_e( 'Nombre completo', 'me-transfers' ); ?></label>
						<input id="mt-name" type="text" name="full_name" required>
					</div>

					<div class="destination-request-field">
						<label for="mt-email"><?php esc_html_e( 'Email', 'me-transfers' ); ?></label>
						<input id="mt-email" type="email" name="email" required>
					</div>

					<div class="destination-request-field">
						<label for="mt-phone"><?php esc_html_e( 'TelÃ©fono', 'me-transfers' ); ?></label>
						<input id="mt-phone" type="tel" name="phone">
					</div>

					<div class="destination-request-field">
						<label for="mt-origin"><?php esc_html_e( 'Origen', 'me-transfers' ); ?></label>
						<input id="mt-origin" type="text" name="origin" placeholder="<?php esc_attr_e( 'Barcelona, aeropuerto, hotel o puerto', 'me-transfers' ); ?>" required>
					</div>

					<div class="destination-request-field">
						<label for="mt-destination"><?php esc_html_e( 'Destino', 'me-transfers' ); ?></label>
						<input id="mt-destination" type="text" value="<?php echo esc_attr( $destination['title'] ); ?>" readonly>
					</div>

					<div class="destination-request-field">
						<label for="mt-date"><?php esc_html_e( 'Fecha estimada', 'me-transfers' ); ?></label>
						<input id="mt-date" type="date" name="travel_date">
					</div>

					<div class="destination-request-field">
						<label for="mt-passengers"><?php esc_html_e( 'Pasajeros', 'me-transfers' ); ?></label>
						<input id="mt-passengers" type="number" name="passengers" min="1" step="1" placeholder="1">
					</div>

					<div class="destination-request-field">
						<label for="mt-trip-type"><?php esc_html_e( 'Tipo de servicio', 'me-transfers' ); ?></label>
						<select id="mt-trip-type" name="trip_type">
							<option value=""><?php esc_html_e( 'Selecciona una opciÃ³n', 'me-transfers' ); ?></option>
							<option value="solo-ida"><?php esc_html_e( 'Solo ida', 'me-transfers' ); ?></option>
							<option value="ida-vuelta"><?php esc_html_e( 'Ida y vuelta', 'me-transfers' ); ?></option>
							<option value="por-horas"><?php esc_html_e( 'Servicio por horas', 'me-transfers' ); ?></option>
						</select>
					</div>
				</div>

				<div class="destination-request-field destination-request-field--full">
					<label for="mt-message"><?php esc_html_e( 'Detalles del traslado', 'me-transfers' ); ?></label>
					<textarea id="mt-message" name="message" rows="6" placeholder="<?php esc_attr_e( 'IndÃ­canos horarios, puntos de recogida, equipaje, niÃ±os, necesidades especiales o cualquier dato relevante.', 'me-transfers' ); ?>" required></textarea>
				</div>

				<div class="destination-request-consent">
					<label>
						<input type="checkbox" name="privacy_consent" value="1" required>
						<span>
							<?php esc_html_e( 'Acepto que mis datos se utilicen para responder a esta solicitud.', 'me-transfers' ); ?>
							<?php if ( $privacy_url ) : ?>
								<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Política de privacidad', 'me-transfers' ); ?></a>
							<?php endif; ?>
						</span>
					</label>
				</div>

				<div class="destination-request-honeypot" aria-hidden="true">
					<label for="mt-company"><?php esc_html_e( 'Empresa', 'me-transfers' ); ?></label>
					<input id="mt-company" type="text" name="company" tabindex="-1" autocomplete="off">
				</div>

				<button type="submit" class="btn btn-primary destination-request-submit"><?php esc_html_e( 'Solicitar información', 'me-transfers' ); ?></button>
			</form>
		</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Handles the destination request form submission.
 *
 * @return void
 */
function me_transfers_handle_destination_request() {
	$referer = wp_get_referer();

	if ( ! $referer ) {
		$referer = me_transfers_get_destinations_hub_url();
	}

	$referer = remove_query_arg( array( 'mt_request', 'mt_destination' ), $referer );

	if ( ! isset( $_POST['me_transfers_destination_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['me_transfers_destination_nonce'] ) ), 'me_transfers_destination_request' ) ) {
		wp_safe_redirect( add_query_arg( array( 'mt_request' => 'error' ), $referer ) );
		exit;
	}

	$destination = me_transfers_get_destination_item( isset( $_POST['destination_slug'] ) ? wp_unslash( $_POST['destination_slug'] ) : '' );

	if ( ! $destination ) {
		wp_safe_redirect( add_query_arg( array( 'mt_request' => 'error' ), $referer ) );
		exit;
	}

	$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$origin    = isset( $_POST['origin'] ) ? sanitize_text_field( wp_unslash( $_POST['origin'] ) ) : '';
	$date      = isset( $_POST['travel_date'] ) ? sanitize_text_field( wp_unslash( $_POST['travel_date'] ) ) : '';
	$trip_type = isset( $_POST['trip_type'] ) ? sanitize_text_field( wp_unslash( $_POST['trip_type'] ) ) : '';
	$passenger = isset( $_POST['passengers'] ) ? absint( $_POST['passengers'] ) : 0;
	$message   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$company   = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
	$consent   = ! empty( $_POST['privacy_consent'] );

	if ( $company || ! $full_name || ! is_email( $email ) || ! $origin || ! $message || ! $consent ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'mt_request'     => 'error',
					'mt_destination' => $destination['slug'],
				),
				$referer
			) . '#destination-request'
		);
		exit;
	}

	$trip_labels = array(
		''           => __( 'No especificado', 'me-transfers' ),
		'solo-ida'   => __( 'Solo ida', 'me-transfers' ),
		'ida-vuelta' => __( 'Ida y vuelta', 'me-transfers' ),
		'por-horas'  => __( 'Servicio por horas', 'me-transfers' ),
	);
	$trip_label  = isset( $trip_labels[ $trip_type ] ) ? $trip_labels[ $trip_type ] : $trip_labels[''];
	$admin_email = get_option( 'admin_email' );
	$subject     = sprintf( '[%s] Solicitud de traslado a %s', get_bloginfo( 'name' ), $destination['title'] );
	$body        = implode(
		"\n",
		array(
			'Nueva solicitud de traslado',
			'',
			'Destino: ' . $destination['title'],
			'Nombre: ' . $full_name,
			'Email: ' . $email,
			'TelÃ©fono: ' . ( $phone ? $phone : __( 'No indicado', 'me-transfers' ) ),
			'Origen: ' . $origin,
			'Fecha estimada: ' . ( $date ? $date : __( 'No indicada', 'me-transfers' ) ),
			'Pasajeros: ' . ( $passenger ? (string) $passenger : __( 'No indicado', 'me-transfers' ) ),
			'Tipo de servicio: ' . $trip_label,
			'',
			'Mensaje:',
			$message,
		)
	);
	$headers     = array( 'Content-Type: text/plain; charset=UTF-8' );

	if ( $email ) {
		$headers[] = sprintf( 'Reply-To: %s <%s>', $full_name, $email );
	}

	$sent = wp_mail( $admin_email, $subject, $body, $headers );

	wp_safe_redirect(
		add_query_arg(
			array(
				'mt_request'     => $sent ? 'success' : 'error',
				'mt_destination' => $destination['slug'],
			),
			$referer
		) . '#destination-request'
	);
	exit;
}
add_action( 'admin_post_nopriv_me_transfers_destination_request', 'me_transfers_handle_destination_request' );
add_action( 'admin_post_me_transfers_destination_request', 'me_transfers_handle_destination_request' );
