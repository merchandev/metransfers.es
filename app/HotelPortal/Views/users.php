<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$notice           = isset( $notice ) ? (string) $notice : '';
$error            = isset( $error ) ? (string) $error : '';
$user_groups      = isset( $user_groups ) ? (array) $user_groups : array();
$hotel_id         = isset( $hotel_id ) ? absint( $hotel_id ) : 0;
$unassigned_users = isset( $unassigned_users ) ? (array) $unassigned_users : array();
?>
<section aria-labelledby="mt-hotel-users-title">
	<p class="mt-hotel-eyebrow"><?php esc_html_e( 'Accesos del hotel', 'me-transfers' ); ?></p>
	<h1 id="mt-hotel-users-title"><?php esc_html_e( 'Usuarios', 'me-transfers' ); ?></h1>
	<p class="mt-hotel-lead"><?php esc_html_e( 'Crea accesos para el personal de este hotel y administra tu contraseña.', 'me-transfers' ); ?></p>

	<?php if ( $notice ) : ?>
		<div class="mt-hotel-alert mt-hotel-alert--success" role="status"><?php echo esc_html( $notice ); ?></div>
	<?php endif; ?>
	<?php if ( $error ) : ?>
		<div class="mt-hotel-alert" role="alert"><?php echo esc_html( $error ); ?></div>
	<?php endif; ?>

	<div class="mt-hotel-users-grid">
		<section class="mt-hotel-phase-card mt-hotel-users-overview">
			<h2><?php esc_html_e( 'Usuarios por hotel', 'me-transfers' ); ?></h2>
			<div class="mt-hotel-hotel-groups">
				<?php foreach ( $user_groups as $group ) : ?>
					<section class="mt-hotel-hotel-group" aria-labelledby="mt-hotel-group-<?php echo esc_attr( (string) $group['hotel_id'] ); ?>">
						<div class="mt-hotel-hotel-group-heading">
							<h3 id="mt-hotel-group-<?php echo esc_attr( (string) $group['hotel_id'] ); ?>"><?php echo esc_html( $group['hotel_name'] ); ?></h3>
							<?php // translators: %d is the number of users assigned to the hotel. ?>
							<span><?php echo esc_html( sprintf( _n( '%d usuario', '%d usuarios', count( $group['users'] ), 'me-transfers' ), count( $group['users'] ) ) ); ?></span>
						</div>
						<?php if ( empty( $group['users'] ) ) : ?>
							<p class="mt-hotel-empty"><?php esc_html_e( 'Este hotel todavía no tiene un responsable asignado.', 'me-transfers' ); ?></p>
						<?php else : ?>
							<div class="mt-hotel-user-list">
								<?php foreach ( $group['users'] as $hotel_user ) : ?>
									<div class="mt-hotel-user-row">
										<div>
											<strong><?php echo esc_html( $hotel_user->display_name ); ?></strong>
											<span>@<?php echo esc_html( $hotel_user->user_login ); ?> · <?php echo esc_html( $hotel_user->user_email ); ?></span>
										</div>
										<small><?php echo get_current_user_id() === (int) $hotel_user->ID ? esc_html__( 'Tu cuenta', 'me-transfers' ) : esc_html__( 'Responsable', 'me-transfers' ); ?></small>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="mt-hotel-phase-card">
			<h2><?php esc_html_e( 'Crear responsable', 'me-transfers' ); ?></h2>
			<?php // translators: %s is the active hotel name. ?>
			<p><?php echo esc_html( sprintf( __( 'El nuevo acceso se asignará al hotel activo: %s.', 'me-transfers' ), get_the_title( $hotel_id ) ) ); ?></p>
			<form method="post" class="mt-hotel-login-form">
				<?php wp_nonce_field( \MeTransfers\HotelPortal\Services\HotelUserManager::NONCE_ACTION, 'mt_hotel_users_nonce' ); ?>
				<input type="hidden" name="mt_hotel_user_action" value="create">
				<div class="mt-hotel-field"><label for="mt-user-name"><?php esc_html_e( 'Nombre o título', 'me-transfers' ); ?></label><input id="mt-user-name" name="display_name" required maxlength="100"></div>
				<div class="mt-hotel-field"><label for="mt-user-login"><?php esc_html_e( 'Usuario', 'me-transfers' ); ?></label><input id="mt-user-login" name="user_login" required maxlength="60" autocomplete="off"></div>
				<div class="mt-hotel-field"><label for="mt-user-email"><?php esc_html_e( 'Correo electrónico', 'me-transfers' ); ?></label><input id="mt-user-email" name="user_email" type="email" required autocomplete="off"></div>
				<div class="mt-hotel-field"><label for="mt-user-password"><?php esc_html_e( 'Contraseña temporal', 'me-transfers' ); ?></label><input id="mt-user-password" name="user_password" type="password" minlength="12" required autocomplete="new-password"></div>
				<button class="mt-hotel-primary-button" type="submit"><?php esc_html_e( 'Crear acceso', 'me-transfers' ); ?></button>
			</form>
		</section>

		<?php if ( $unassigned_users ) : ?>
			<section class="mt-hotel-phase-card">
				<h2><?php esc_html_e( 'Usuarios sin hotel asignado', 'me-transfers' ); ?></h2>
				<form method="post" class="mt-hotel-login-form">
					<?php wp_nonce_field( \MeTransfers\HotelPortal\Services\HotelUserManager::NONCE_ACTION, 'mt_hotel_users_nonce' ); ?>
					<input type="hidden" name="mt_hotel_user_action" value="assign-existing">
					<div class="mt-hotel-field"><label for="mt-existing-user"><?php esc_html_e( 'Cuenta Hotel', 'me-transfers' ); ?></label><select id="mt-existing-user" name="user_id" required>
					<?php
					foreach ( $unassigned_users as $unassigned_user ) :
						?>
						<option value="<?php echo esc_attr( (string) $unassigned_user->ID ); ?>"><?php echo esc_html( $unassigned_user->display_name . ' — ' . $unassigned_user->user_email ); ?></option><?php endforeach; ?></select></div>
					<button class="mt-hotel-primary-button" type="submit"><?php esc_html_e( 'Asignar al hotel activo', 'me-transfers' ); ?></button>
				</form>
			</section>
		<?php endif; ?>

		<section class="mt-hotel-phase-card">
			<h2><?php esc_html_e( 'Cambiar mi contraseña', 'me-transfers' ); ?></h2>
			<form method="post" class="mt-hotel-login-form">
				<?php wp_nonce_field( \MeTransfers\HotelPortal\Services\HotelUserManager::NONCE_ACTION, 'mt_hotel_users_nonce' ); ?>
				<input type="hidden" name="mt_hotel_user_action" value="change-password">
				<div class="mt-hotel-field"><label for="mt-new-password"><?php esc_html_e( 'Nueva contraseña', 'me-transfers' ); ?></label><input id="mt-new-password" name="new_password" type="password" minlength="12" required autocomplete="new-password"></div>
				<div class="mt-hotel-field"><label for="mt-confirm-password"><?php esc_html_e( 'Confirmar contraseña', 'me-transfers' ); ?></label><input id="mt-confirm-password" name="confirm_password" type="password" minlength="12" required autocomplete="new-password"></div>
				<button class="mt-hotel-primary-button" type="submit"><?php esc_html_e( 'Actualizar contraseña', 'me-transfers' ); ?></button>
			</form>
		</section>
	</div>
</section>
