<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$profile = isset( $page_data ) && is_array( $page_data ) ? $page_data : array();
?>
<section><p class="mt-hotel-eyebrow"><?php esc_html_e( 'Configuración', 'me-transfers' ); ?></p><h1><?php echo esc_html( $profile['name'] ?? '' ); ?></h1>
<?php
if ( ! empty( $profile['notice'] ) ) :
	?>
	<div class="mt-hotel-alert mt-hotel-alert--success"><?php echo esc_html( $profile['notice'] ); ?></div><?php endif; ?><div class="mt-hotel-users-grid"><form method="post" class="mt-hotel-phase-card mt-hotel-login-form"><?php wp_nonce_field( \MeTransfers\HotelPortal\Services\HotelOperations::NONCE_ACTION, 'mt_operations_nonce' ); ?><input type="hidden" name="mt_update_hotel" value="1">
	<?php
	foreach ( array(
		'address'       => 'Dirección',
		'phone'         => 'Teléfono',
		'contact_name'  => 'Responsable',
		'contact_email' => 'Email de contacto',
	) as $name => $label ) :
		?>
	<div class="mt-hotel-field"><label for="mt-profile-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label><input id="mt-profile-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $profile[ $name ] ?? '' ); ?>"></div><?php endforeach; ?><button class="mt-hotel-primary-button"><?php esc_html_e( 'Guardar datos', 'me-transfers' ); ?></button></form><div class="mt-hotel-phase-card"><h2>QR Hotel</h2><p><?php esc_html_e( 'Descarga los materiales vinculados a este hotel.', 'me-transfers' ); ?></p><a class="mt-hotel-secondary-button" href="<?php echo esc_url( $profile['qr_url'] ?? '' ); ?>"><?php esc_html_e( 'Descargar QR', 'me-transfers' ); ?></a><a class="mt-hotel-secondary-button" href="<?php echo esc_url( $profile['flyer_url'] ?? '' ); ?>"><?php esc_html_e( 'Descargar Flyer', 'me-transfers' ); ?></a></div></div></section>
