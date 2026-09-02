<?php

namespace MeTransfers\Admin;

use MeTransfers\HotelPortal\Access\HotelAccess;

final class HotelUsersPage {
	const NONCE_ACTION = 'mt_admin_hotel_user_access';

	public static function render() {
		if ( ! current_user_can( Capabilities::HOTEL_MANAGE_USERS ) ) {
			wp_die( esc_html__( 'No tienes permisos para gestionar accesos de hoteles.', 'me-transfers' ) );
		}

		$notice = self::process();
		$users  = get_users(
			array(
				'role'    => 'check_hoteles',
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Usuarios / Accesos de Hoteles', 'me-transfers' ); ?></h1>
			<p><?php esc_html_e( 'Administra el acceso al Portal de Hoteles sin eliminar cuentas, cambiar contraseñas ni modificar sus hoteles asignados.', 'me-transfers' ); ?></p>
			<?php if ( $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
			<?php endif; ?>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Hotel', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Usuario', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Correo', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Perfil', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Acceso al portal', 'me-transfers' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $users ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No hay usuarios de hoteles registrados.', 'me-transfers' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $users as $user ) : ?>
						<?php
						$global      = HotelAccess::hasGlobalAccess( (int) $user->ID );
						$hotel_names = self::hotelNames( (int) $user->ID, $global );
						$blocked     = HotelAccess::isBlocked( (int) $user->ID );
						?>
						<tr>
							<td><?php echo esc_html( implode( ', ', $hotel_names ) ); ?></td>
							<td><strong><?php echo esc_html( $user->user_login ); ?></strong><br><?php echo esc_html( $user->display_name ); ?></td>
							<td><a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a></td>
							<td><?php echo esc_html( $global ? __( 'Supervisor global', 'me-transfers' ) : __( 'Responsable de hotel', 'me-transfers' ) ); ?></td>
							<td>
								<form method="post">
									<?php wp_nonce_field( self::NONCE_ACTION, 'mt_hotel_access_nonce' ); ?>
									<input type="hidden" name="mt_hotel_access_action" value="toggle">
									<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>">
									<input type="hidden" name="blocked" value="<?php echo $blocked ? '0' : '1'; ?>">
									<button type="submit" class="button <?php echo $blocked ? 'button-primary' : ''; ?>" aria-label="<?php echo esc_attr( $blocked ? __( 'Activar acceso', 'me-transfers' ) : __( 'Bloquear acceso', 'me-transfers' ) ); ?>">
										<?php echo esc_html( $blocked ? __( 'Bloqueado — Activar', 'me-transfers' ) : __( 'Activo — Bloquear', 'me-transfers' ) ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function process() {
		if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || 'toggle' !== ( $_POST['mt_hotel_access_action'] ?? '' ) ) {
			return null;
		}
		$nonce = isset( $_POST['mt_hotel_access_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mt_hotel_access_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || ! current_user_can( Capabilities::HOTEL_MANAGE_USERS ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'No se pudo validar la solicitud.', 'me-transfers' ),
			);
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$user    = $user_id ? get_userdata( $user_id ) : false;
		if ( ! $user || ! in_array( 'check_hoteles', (array) $user->roles, true ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'La cuenta seleccionada no es un usuario de Hotel válido.', 'me-transfers' ),
			);
		}

		$blocked = ! empty( $_POST['blocked'] );
		if ( $blocked ) {
			update_user_meta( $user_id, HotelAccess::BLOCKED_META_KEY, '1' );
		} else {
			delete_user_meta( $user_id, HotelAccess::BLOCKED_META_KEY );
		}
		AuditLog::record( $blocked ? 'hotel.user.blocked' : 'hotel.user.unblocked', 'user', $user_id );

		return array(
			'type'    => 'success',
			'message' => $blocked ? __( 'El acceso al Portal de Hoteles fue bloqueado.', 'me-transfers' ) : __( 'El acceso al Portal de Hoteles fue reactivado.', 'me-transfers' ),
		);
	}

	private static function hotelNames( $user_id, $has_global_access ) {
		if ( $has_global_access ) {
			return array( __( 'Todos los hoteles', 'me-transfers' ) );
		}
		$ids   = get_user_meta( $user_id, 'mt_hotel_ids', true );
		$ids   = is_array( $ids ) ? $ids : array( $ids );
		$names = array();
		foreach ( array_unique( array_filter( array_map( 'absint', $ids ) ) ) as $hotel_id ) {
			if ( 'hotel_partner' === get_post_type( $hotel_id ) ) {
				$names[] = get_the_title( $hotel_id );
			}
		}
		return $names ? $names : array( __( 'Sin hotel asignado', 'me-transfers' ) );
	}
}
