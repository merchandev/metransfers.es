<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title = isset( $page_title ) ? (string) $page_title : esc_html__( 'Acceso no disponible', 'me-transfers' );
$message    = isset( $message ) ? (string) $message : '';
?>
<section class="mt-hotel-state-card" aria-labelledby="mt-hotel-state-title">
	<span class="mt-hotel-state-icon" aria-hidden="true">!</span>
	<h1 id="mt-hotel-state-title"><?php echo esc_html( $page_title ); ?></h1>
	<p><?php echo esc_html( $message ); ?></p>
	<?php if ( is_user_logged_in() ) : ?>
		<a class="mt-hotel-secondary-button" href="<?php echo esc_url( \MeTransfers\HotelPortal\Auth\AuthController::logoutUrl() ); ?>"><?php esc_html_e( 'Cerrar sesión', 'me-transfers' ); ?></a>
	<?php else : ?>
		<a class="mt-hotel-secondary-button" href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url() ); ?>"><?php esc_html_e( 'Volver al acceso', 'me-transfers' ); ?></a>
	<?php endif; ?>
</section>
