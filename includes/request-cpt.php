<?php
/**
 * Request Custom Post Type and AJAX handler
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom post type for Requests (Solicitudes).
 */
function me_transfers_register_request_cpt() {
	$labels = array(
		'name'                  => 'Solicitudes',
		'singular_name'         => 'Solicitud',
		'menu_name'             => 'Solicitudes',
		'all_items'             => 'Todas las Solicitudes',
		'add_new'               => 'Añadir nueva',
		'add_new_item'          => 'Añadir nueva Solicitud',
		'edit_item'             => 'Editar Solicitud',
		'new_item'              => 'Nueva Solicitud',
		'view_item'             => 'Ver Solicitud',
		'search_items'          => 'Buscar Solicitudes',
		'not_found'             => 'No se encontraron solicitudes',
		'not_found_in_trash'    => 'No hay solicitudes en la papelera',
	);

	$args = array(
		'labels'              => $labels,
		'public'              => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-email-alt',
		'menu_position'       => 26, // Below Comments
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'capabilities'        => array(
			'create_posts' => 'do_not_allow', // Prevents manual creation from admin
		),
		'map_meta_cap'        => true,
		'has_archive'         => false,
		'hierarchical'        => false,
		'rewrite'             => false,
	);

	register_post_type( 'mt_request', $args );
}
add_action( 'init', 'me_transfers_register_request_cpt' );

/**
 * Handle destination AJAX request submission.
 */
function me_transfers_ajax_destination_request() {
	check_ajax_referer( 'mt_destination_request', 'security' );

	// Verify Honeypot
	if ( ! empty( $_POST['mt_subject'] ) ) {
		wp_send_json_error( array( 'message' => 'Spam detected.' ) );
	}

	$destination = isset( $_POST['destination'] ) ? sanitize_text_field( wp_unslash( $_POST['destination'] ) ) : '';
	$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( empty( $destination ) || empty( $name ) || empty( $email ) ) {
		wp_send_json_error( array( 'message' => 'Por favor, completa todos los campos requeridos.' ) );
	}

	// Create CPT entry
	$post_id = wp_insert_post( array(
		'post_type'   => 'mt_request',
		'post_title'  => sprintf( 'Solicitud de %s: %s', $name, $destination ),
		'post_status' => 'publish',
	) );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Error al guardar la solicitud. IntÃ©ntalo de nuevo.' ) );
	}

	update_post_meta( $post_id, '_customer_name', $name );
	update_post_meta( $post_id, '_customer_email', $email );
	update_post_meta( $post_id, '_destination', $destination );

	// Send Email
	$admin_email = get_option( 'admin_email' );
	$subject     = 'Nueva solicitud de destino: ' . $destination;
	$message     = "Has recibido una nueva solicitud de destino.\n\n";
	$message    .= "Nombre: $name\n";
	$message    .= "Email: $email\n";
	$message    .= "Destino solicitado: $destination\n";
	
	$headers     = array('Reply-To: ' . $name . ' <' . $email . '>');

	wp_mail( $admin_email, $subject, $message, $headers );

	wp_send_json_success( array( 'message' => '¡Solicitud enviada correctamente! Nos pondremos en contacto contigo pronto.' ) );
}
add_action( 'wp_ajax_me_transfers_ajax_destination_request', 'me_transfers_ajax_destination_request' );
add_action( 'wp_ajax_nopriv_me_transfers_ajax_destination_request', 'me_transfers_ajax_destination_request' );

/**
 * Add meta boxes to display the request details in the admin.
 */
function me_transfers_add_request_meta_boxes() {
	add_meta_box(
		'mt_request_details',
		'Detalles de la Solicitud',
		'me_transfers_render_request_meta_box',
		'mt_request',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'me_transfers_add_request_meta_boxes' );

function me_transfers_render_request_meta_box( $post ) {
	$name        = get_post_meta( $post->ID, '_customer_name', true );
	$email       = get_post_meta( $post->ID, '_customer_email', true );
	$destination = get_post_meta( $post->ID, '_destination', true );
	?>
	<table class="form-table">
		<tr>
			<th><label>Nombre</label></th>
			<td><?php echo esc_html( $name ); ?></td>
		</tr>
		<tr>
			<th><label>Email</label></th>
			<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
		</tr>
		<tr>
			<th><label>Destino</label></th>
			<td><strong><?php echo esc_html( $destination ); ?></strong></td>
		</tr>
	</table>
	<?php
}


/**
 * Handle contact form AJAX submission from the home page.
 */
function me_transfers_ajax_contact_request() {
check_ajax_referer( 'mt_contact_request', 'security' );

// Honeypot
if ( ! empty( $_POST['mt_subject'] ) ) {
wp_send_json_error( array( 'message' => 'Spam detected.' ) );
}

$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
wp_send_json_error( array( 'message' => 'Por favor, completa todos los campos requeridos.' ) );
}

if ( ! is_email( $email ) ) {
wp_send_json_error( array( 'message' => 'El correo electrónico no es válido.' ) );
}

if ( strlen( $name ) > 100 || strlen( $phone ) > 50 || strlen( $message ) > 2000 ) {
wp_send_json_error( array( 'message' => 'Alguno de los campos excede la longitud permitida.' ) );
}

// Create CPT entry
$post_id = wp_insert_post( array(
'post_type'   => 'mt_request',
'post_title'  => sprintf( 'Contacto de %s', $name ),
'post_status' => 'private',
) );

if ( is_wp_error( $post_id ) ) {
wp_send_json_error( array( 'message' => 'Error al enviar el mensaje. Inténtalo de nuevo.' ) );
}

update_post_meta( $post_id, '_customer_name', $name );
update_post_meta( $post_id, '_customer_email', $email );
update_post_meta( $post_id, '_customer_phone', $phone );
update_post_meta( $post_id, '_contact_message', $message );
update_post_meta( $post_id, '_contact_source', 'Contacto Home' );

// Send email
$admin_email  = get_option( 'admin_email' );
$subject      = 'Nuevo mensaje de contacto: ' . $name;
$email_body   = "Has recibido un nuevo mensaje de contacto desde la web.\n\n";
$email_body  .= "Nombre: $name\n";
$email_body  .= "Email: $email\n";
if ( $phone ) {
$email_body .= "Teléfono: $phone\n";
}
$email_body  .= "\nMensaje:\n$message\n";

$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
$mail_sent = wp_mail( $admin_email, $subject, $email_body, $headers );

if ( ! $mail_sent ) {
error_log( 'MeTransfers: Error al enviar el correo de contacto para ' . $email );
wp_send_json_error( array( 'message' => 'Error al procesar la solicitud. Por favor, intenta de nuevo más tarde.' ) );
}

wp_send_json_success( array( 'message' => '¡Mensaje enviado correctamente! Nos pondremos en contacto contigo pronto.' ) );
}
add_action( 'wp_ajax_me_transfers_contact_request', 'me_transfers_ajax_contact_request' );
add_action( 'wp_ajax_nopriv_me_transfers_contact_request', 'me_transfers_ajax_contact_request' );
