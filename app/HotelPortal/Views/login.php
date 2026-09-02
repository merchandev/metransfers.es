<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="mt-hotel-auth-card" aria-labelledby="mt-hotel-login-title">
	<div class="mt-hotel-brand">
		<img src="https://metransfers.es/wp-content/uploads/2026/03/MT-MeTransfers-scaled.png" alt="MeTransfers">
	</div>
	<p class="mt-hotel-eyebrow"><?php esc_html_e( 'Portal privado para hoteles', 'me-transfers' ); ?></p>
	<h1 id="mt-hotel-login-title"><?php esc_html_e( 'Bienvenido de nuevo', 'me-transfers' ); ?></h1>
	<p class="mt-hotel-auth-copy"><?php esc_html_e( 'Accede con tu usuario de Hotel autorizado por MeTransfers.', 'me-transfers' ); ?></p>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="mt-hotel-alert" role="alert"><?php echo esc_html( $error ); ?></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url() ); ?>" class="mt-hotel-login-form">
		<?php wp_nonce_field( \MeTransfers\HotelPortal\Auth\AuthController::LOGIN_NONCE_ACTION, 'mt_hotel_login_nonce' ); ?>
		<input type="hidden" name="mt_hotel_login" value="1">
		<div class="mt-hotel-field">
			<label for="mt-hotel-login"><?php esc_html_e( 'Usuario o correo electrónico', 'me-transfers' ); ?></label>
			<input id="mt-hotel-login" name="log" type="text" autocomplete="username" maxlength="100" required autofocus>
		</div>
		<div class="mt-hotel-field">
			<label for="mt-hotel-password"><?php esc_html_e( 'Contraseña', 'me-transfers' ); ?></label>
			<div class="mt-hotel-password-wrap">
				<input id="mt-hotel-password" name="pwd" type="password" autocomplete="current-password" required>
				<button class="mt-hotel-password-toggle" type="button" aria-controls="mt-hotel-password" aria-pressed="false"><?php esc_html_e( 'Mostrar', 'me-transfers' ); ?></button>
			</div>
		</div>
		<label class="mt-hotel-check">
			<input type="checkbox" name="rememberme" value="1">
			<span><?php esc_html_e( 'Mantener la sesión iniciada', 'me-transfers' ); ?></span>
		</label>
		<button class="mt-hotel-primary-button" type="submit"><?php esc_html_e( 'Entrar al portal', 'me-transfers' ); ?></button>
	</form>
	<a class="mt-hotel-help-link" href="<?php echo esc_url( wp_lostpassword_url( \MeTransfers\HotelPortal\Routing\Router::url() ) ); ?>"><?php esc_html_e( '¿Has olvidado tu contraseña?', 'me-transfers' ); ?></a>
</section>
