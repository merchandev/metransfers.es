<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hotel_id   = isset( $hotel_id ) ? absint( $hotel_id ) : 0;
$hotel_name = get_the_title( $hotel_id );
$dashboard  = isset( $dashboard ) ? (array) $dashboard : array();
$recent     = isset( $dashboard['recent'] ) ? (array) $dashboard['recent'] : array();
?>
<section aria-labelledby="mt-hotel-dashboard-title">
	<p class="mt-hotel-eyebrow"><?php esc_html_e( 'Portal de Hoteles', 'me-transfers' ); ?></p>
	<?php // translators: %s is the current Hotel Portal user's display name. ?>
	<h1 id="mt-hotel-dashboard-title"><?php echo esc_html( sprintf( __( 'Hola, %s', 'me-transfers' ), wp_get_current_user()->display_name ) ); ?></h1>
	<?php // translators: %s is the active Hotel name. ?>
	<p class="mt-hotel-lead"><?php echo esc_html( sprintf( __( 'Estás trabajando con %s.', 'me-transfers' ), $hotel_name ) ); ?></p>

	<div class="mt-hotel-dashboard-stats" aria-label="<?php esc_attr_e( 'Resumen del hotel', 'me-transfers' ); ?>">
		<div class="mt-hotel-stat"><span><?php esc_html_e( 'Servicios hoy', 'me-transfers' ); ?></span><strong><?php echo esc_html( (string) ( $dashboard['today'] ?? 0 ) ); ?></strong></div>
		<div class="mt-hotel-stat"><span><?php esc_html_e( 'Próximas', 'me-transfers' ); ?></span><strong><?php echo esc_html( (string) ( $dashboard['upcoming'] ?? 0 ) ); ?></strong></div>
		<div class="mt-hotel-stat"><span><?php esc_html_e( 'Pendientes', 'me-transfers' ); ?></span><strong><?php echo esc_html( (string) ( $dashboard['pending'] ?? 0 ) ); ?></strong></div>
		<div class="mt-hotel-stat"><span><?php esc_html_e( 'Confirmadas', 'me-transfers' ); ?></span><strong><?php echo esc_html( (string) ( $dashboard['confirmed'] ?? 0 ) ); ?></strong></div>
		<div class="mt-hotel-stat"><span><?php esc_html_e( 'Producción del mes', 'me-transfers' ); ?></span><strong><?php echo esc_html( number_format_i18n( (int) ( $dashboard['month_revenue_cents'] ?? 0 ) / 100, 2 ) . ' €' ); ?></strong></div>
	</div>

	<div class="mt-hotel-phase-card mt-hotel-dashboard-table-card">
		<div class="mt-hotel-card-heading">
			<div><h2><?php esc_html_e( 'Reservas recientes', 'me-transfers' ); ?></h2><p><?php esc_html_e( 'Actividad vinculada exclusivamente a este hotel.', 'me-transfers' ); ?></p></div>
			<a href="<?php echo esc_url( \MeTransfers\HotelPortal\Routing\Router::url( 'bookings' ) ); ?>"><?php esc_html_e( 'Ver todas', 'me-transfers' ); ?></a>
		</div>
		<?php if ( empty( $recent ) ) : ?>
			<p class="mt-hotel-empty"><?php esc_html_e( 'Este hotel todavía no tiene reservas registradas.', 'me-transfers' ); ?></p>
		<?php else : ?>
			<div class="mt-hotel-table-wrap">
				<table class="mt-hotel-table">
					<thead><tr><th><?php esc_html_e( 'Reserva', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Fecha', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Cliente', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Trayecto', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Estado', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Importe', 'me-transfers' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $recent as $booking ) : ?>
						<?php $amount_cents = null !== $booking->price_cents ? (int) $booking->price_cents : (int) round( (float) $booking->price * 100 ); ?>
						<tr>
							<td><a href="<?php echo esc_url( home_url( '/hoteles/reservas/' . absint( $booking->id ) . '/' ) ); ?>">#<?php echo esc_html( (string) absint( $booking->id ) ); ?></a></td>
							<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $booking->booking_date . ' ' . $booking->booking_time ) ); ?></td>
							<td><?php echo esc_html( $booking->customer_name ); ?></td>
							<td><span class="mt-hotel-route-text"><?php echo esc_html( $booking->origin ); ?> → <?php echo esc_html( $booking->destination ); ?></span></td>
							<td><span class="mt-hotel-badge mt-hotel-badge--<?php echo esc_attr( sanitize_html_class( $booking->status ) ); ?>"><?php echo esc_html( \MeTransfers\HotelPortal\Services\HotelDashboard::statusLabel( $booking->status ) ); ?></span></td>
							<td><?php echo esc_html( number_format_i18n( $amount_cents / 100, 2 ) . ' €' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</section>
