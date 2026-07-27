<?php
/**
 * Tour Bookings CPT and AJAX handler
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom post type for Tour Bookings.
 */
function me_transfers_register_tour_booking_cpt() {
	$labels = array(
		'name'               => 'Reservas Tours',
		'singular_name'      => 'Reserva Tour',
		'menu_name'          => 'Reservas Tours',
		'all_items'          => 'Todas las Reservas',
		'add_new'            => 'Añadir nueva',
		'add_new_item'       => 'Añadir nueva Reserva',
		'edit_item'          => 'Ver Reserva',
		'new_item'           => 'Nueva Reserva',
		'view_item'          => 'Ver Reserva',
		'search_items'       => 'Buscar Reservas',
		'not_found'          => 'No se encontraron reservas de tours',
		'not_found_in_trash' => 'No hay reservas en la papelera',
	);

	$args = array(
		'labels'              => $labels,
		'public'              => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-palmtree',
		'menu_position'       => 27,
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'capabilities'        => array(
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap'        => true,
		'has_archive'         => false,
		'hierarchical'        => false,
		'rewrite'             => false,
	);

	register_post_type( 'mt_tour_booking', $args );
}
add_action( 'init', 'me_transfers_register_tour_booking_cpt' );

/**
 * Handle tour booking AJAX submission.
 */
function me_transfers_ajax_tour_booking() {
	check_ajax_referer( 'mt_tour_booking_nonce', 'security' );

	// Honeypot
	if ( ! empty( $_POST['mt_website'] ) ) {
		wp_send_json_error( array( 'message' => 'Spam detected.' ) );
	}

	$tour_name = isset( $_POST['tour_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tour_name'] ) ) : '';
	$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$country   = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$tour_date = isset( $_POST['tour_date'] ) ? sanitize_text_field( wp_unslash( $_POST['tour_date'] ) ) : '';

	if ( empty( $name ) || empty( $email ) || empty( $phone ) || empty( $tour_name ) ) {
		wp_send_json_error( array( 'message' => 'Por favor, completa todos los campos requeridos.' ) );
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'El correo electrónico no es válido.' ) );
	}

	if ( strlen( $name ) > 100 || strlen( $phone ) > 50 || strlen( $tour_name ) > 150 ) {
		wp_send_json_error( array( 'message' => 'Alguno de los campos excede la longitud permitida.' ) );
	}

	// Create CPT entry
	$post_id = wp_insert_post( array(
		'post_type'   => 'mt_tour_booking',
		'post_title'  => sprintf( '%s â€” %s', $name, $tour_name ),
		'post_status' => 'private',
	) );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Error al guardar la reserva. IntÃ©ntalo de nuevo.' ) );
	}

	update_post_meta( $post_id, '_customer_name', $name );
	update_post_meta( $post_id, '_customer_country', $country );
	update_post_meta( $post_id, '_customer_phone', $phone );
	update_post_meta( $post_id, '_customer_email', $email );
	update_post_meta( $post_id, '_tour_name', $tour_name );
	update_post_meta( $post_id, '_tour_date', $tour_date );

	// Send email notification
	$admin_email = get_option( 'admin_email' );
	$subject     = 'ðŸ· Nueva Reserva de Tour: ' . $tour_name;
	$message     = "Has recibido una nueva reserva de tour.\n\n";
	$message    .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n";
	$message    .= "Tour: $tour_name\n";
	$message    .= "Nombre: $name\n";
	$message    .= "PaÃ­s: $country\n";
	$message    .= "TelÃ©fono: $phone\n";
	$message    .= "Email: $email\n";
	$message    .= "Fecha deseada: $tour_date\n";
	$message    .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n\n";
	$message    .= 'Ver en admin: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' );

	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
	$mail_sent = wp_mail( $admin_email, $subject, $message, $headers );

	if ( ! $mail_sent ) {
		error_log( 'MeTransfers: Error al enviar el correo de reserva de tour para ' . $email );
		wp_send_json_error( array( 'message' => 'Error al procesar la solicitud. Por favor, intenta de nuevo.' ) );
	}

	// Build WhatsApp URL
	$wa_number = '34662024136';
	$wa_text   = "ðŸ· *Reserva de Tour*\n";
	$wa_text  .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n";
	$wa_text  .= "ðŸ“ Tour: $tour_name\n";
	$wa_text  .= "ðŸ‘¤ Nombre: $name\n";
	if ( $country ) {
		$wa_text .= "ðŸŒ PaÃ­s: $country\n";
	}
	$wa_text  .= "ðŸ“ž TelÃ©fono: $phone\n";
	$wa_text  .= "ðŸ“§ Email: $email\n";
	if ( $tour_date ) {
		$wa_text .= "ðŸ“… Fecha deseada: $tour_date\n";
	}
	$wa_text  .= "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n";
	$wa_text  .= "Quiero reservar este tour. ¿Tienen disponibilidad?";

	$wa_url = 'https://wa.me/' . $wa_number . '?text=' . rawurlencode( $wa_text );

	wp_send_json_success( array(
		'message'      => '¡Reserva enviada correctamente! Te redirigimos a WhatsApp...',
		'whatsapp_url' => $wa_url,
	) );
}
add_action( 'wp_ajax_me_transfers_tour_booking', 'me_transfers_ajax_tour_booking' );
add_action( 'wp_ajax_nopriv_me_transfers_tour_booking', 'me_transfers_ajax_tour_booking' );

/**
 * Add meta boxes to display tour booking details in admin.
 */
function me_transfers_add_tour_booking_meta_boxes() {
	add_meta_box(
		'mt_tour_booking_details',
		'Detalles de la Reserva',
		'me_transfers_render_tour_booking_meta_box',
		'mt_tour_booking',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'me_transfers_add_tour_booking_meta_boxes' );

function me_transfers_render_tour_booking_meta_box( $post ) {
	$name     = get_post_meta( $post->ID, '_customer_name', true );
	$country  = get_post_meta( $post->ID, '_customer_country', true );
	$phone    = get_post_meta( $post->ID, '_customer_phone', true );
	$email    = get_post_meta( $post->ID, '_customer_email', true );
	$tour     = get_post_meta( $post->ID, '_tour_name', true );
	$date     = get_post_meta( $post->ID, '_tour_date', true );
	?>
	<table class="form-table" style="margin-top:0;">
		<tr>
			<th style="width:140px;"><label>ðŸ· Tour</label></th>
			<td><strong style="font-size:1.1em;"><?php echo esc_html( $tour ); ?></strong></td>
		</tr>
		<tr>
			<th><label>ðŸ‘¤ Nombre</label></th>
			<td><?php echo esc_html( $name ); ?></td>
		</tr>
		<tr>
			<th><label>ðŸŒ PaÃ­s</label></th>
			<td><?php echo esc_html( $country ?: 'â€”' ); ?></td>
		</tr>
		<tr>
			<th><label>ðŸ“ž TelÃ©fono</label></th>
			<td>
				<?php if ( $phone ) : ?>
					<a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a>
				<?php else : ?>
					â€”
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><label>ðŸ“§ Email</label></th>
			<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
		</tr>
		<tr>
			<th><label>ðŸ“… Fecha deseada</label></th>
			<td><?php echo esc_html( $date ?: 'â€”' ); ?></td>
		</tr>
		<tr>
			<th><label>ðŸ“‹ Recibido</label></th>
			<td><?php echo esc_html( get_the_date( 'd/m/Y H:i', $post ) ); ?></td>
		</tr>
	</table>
	<?php
	// WhatsApp quick-reply link
	if ( $phone ) :
		$wa_text = rawurlencode( "Hola $name, gracias por tu interÃ©s en el $tour. Te confirmamos disponibilidad para la fecha solicitada." );
		?>
		<p style="margin-top:1rem;">
			<a href="https://wa.me/<?php echo esc_attr( preg_replace('/[^0-9]/', '', $phone ) ); ?>?text=<?php echo esc_attr( $wa_text ); ?>" target="_blank" class="button button-primary" style="background:#25D366;border-color:#25D366;">
				ðŸ’¬ Responder por WhatsApp
			</a>
		</p>
	<?php endif;
}

/**
 * Customize admin columns for Tour Bookings.
 */
function me_transfers_tour_booking_columns( $columns ) {
	$new = array(
		'cb'        => $columns['cb'],
		'title'     => 'Reserva',
		'tour'      => 'Tour',
		'phone'     => 'TelÃ©fono',
		'email'     => 'Email',
		'tour_date' => 'Fecha Tour',
		'date'      => 'Fecha Solicitud',
	);
	return $new;
}
add_filter( 'manage_mt_tour_booking_posts_columns', 'me_transfers_tour_booking_columns' );

function me_transfers_tour_booking_column_data( $column, $post_id ) {
	switch ( $column ) {
		case 'tour':
			echo esc_html( get_post_meta( $post_id, '_tour_name', true ) );
			break;
		case 'phone':
			$phone = get_post_meta( $post_id, '_customer_phone', true );
			echo $phone ? '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>' : 'â€”';
			break;
		case 'email':
			$email = get_post_meta( $post_id, '_customer_email', true );
			echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			break;
		case 'tour_date':
			echo esc_html( get_post_meta( $post_id, '_tour_date', true ) ?: 'â€”' );
			break;
	}
}
add_action( 'manage_mt_tour_booking_posts_custom_column', 'me_transfers_tour_booking_column_data', 10, 2 );
