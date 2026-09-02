<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$route = isset( $route ) ? sanitize_key( (string) $route ) : '';
?>
<aside id="mt-hotel-sidebar" class="mt-hotel-sidebar" aria-label="<?php esc_attr_e( 'Navegación del Portal de Hoteles', 'me-transfers' ); ?>">
	<a class="mt-hotel-sidebar-brand" href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'dashboard' ) ); ?>">
		<img src="https://metransfers.es/wp-content/uploads/2026/03/MT-MeTransfers-scaled.png" alt="MeTransfers">
	</a>
	<nav class="mt-hotel-nav">
		<!-- Sección MeTransfers -->
		<div class="mt-hotel-nav-group mt-hotel-nav-group--mt">
			<span class="mt-hotel-nav-label"><?php esc_html_e( 'MeTransfers', 'me-transfers' ); ?></span>
			<a <?php echo 'dashboard' === $route ? 'class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'dashboard' ) ); ?>"><?php esc_html_e( 'Dashboard', 'me-transfers' ); ?></a>
		</div>
		<!-- Sección Hotel -->
		<div class="mt-hotel-nav-group mt-hotel-nav-group--hotel">
			<span class="mt-hotel-nav-label"><?php esc_html_e( 'Hotel', 'me-transfers' ); ?></span>
			<a <?php echo in_array( $route, array( 'bookings', 'booking-detail' ), true ) ? 'class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'bookings' ) ); ?>"><?php esc_html_e( 'Reservas', 'me-transfers' ); ?></a>
			<a <?php echo 'booking-new' === $route ? 'class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'booking-new' ) ); ?>"><?php esc_html_e( 'Nueva reserva', 'me-transfers' ); ?></a>
			<a <?php echo 'customers' === $route ? 'class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'customers' ) ); ?>"><?php esc_html_e( 'Clientes', 'me-transfers' ); ?></a>
			<a <?php echo 'statistics' === $route ? 'class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'statistics' ) ); ?>"><?php esc_html_e( 'Estadísticas', 'me-transfers' ); ?></a>
			<a <?php echo 'profile' === $route ? 'class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'profile' ) ); ?>"><?php esc_html_e( 'Mi Hotel', 'me-transfers' ); ?></a>
			<?php if ( current_user_can( \MeTransfers\Admin\Capabilities::HOTEL_MANAGE_USERS ) ) : ?>
			<a <?php echo 'users' === $route ? 'class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'users' ) ); ?>"><?php esc_html_e( 'Usuarios', 'me-transfers' ); ?></a>
			<?php endif; ?>
			<?php if ( current_user_can( \MeTransfers\Admin\Capabilities::HOTEL_IMPORT_BOOKINGS ) ) : ?>
			<a <?php echo 'import' === $route ? 'class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'import' ) ); ?>"><?php esc_html_e( 'Importar reservas', 'me-transfers' ); ?></a>
			<?php endif; ?>
		</div>
	</nav>
	<a class="mt-hotel-logout" href="<?php echo esc_url( \MeTransfers\HotelPortal\Auth\AuthController::logoutUrl() ); ?>"><?php esc_html_e( 'Cerrar sesión', 'me-transfers' ); ?></a>
</aside>
