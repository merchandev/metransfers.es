<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$data = isset( $page_data ) && is_array( $page_data ) ? $page_data : array();
?>
<section><p class="mt-hotel-eyebrow"><?php esc_html_e( 'Operación', 'me-transfers' ); ?></p><h1><?php esc_html_e( 'Reservas', 'me-transfers' ); ?></h1>
<form method="get" class="mt-hotel-filter"><input name="q" value="<?php echo esc_attr( $data['search'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Cliente, email o trayecto', 'me-transfers' ); ?>"><select name="status"><option value=""><?php esc_html_e( 'Todos los estados', 'me-transfers' ); ?></option>
<?php
foreach ( array(
	'pending'         => 'Pendiente',
	'pending_payment' => 'Pago pendiente',
	'confirmed'       => 'Confirmada',
	'completed'       => 'Completada',
	'cancelled'       => 'Cancelada',
) as $value => $label ) :
	?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $data['status'] ?? '', $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><button class="mt-hotel-primary-button"><?php esc_html_e( 'Filtrar', 'me-transfers' ); ?></button></form>
<div class="mt-hotel-phase-card mt-hotel-dashboard-table-card"><div class="mt-hotel-table-wrap"><table class="mt-hotel-table"><thead><tr><th>ID</th><th><?php esc_html_e( 'Fecha', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Cliente', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Trayecto', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Estado', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Importe', 'me-transfers' ); ?></th></tr></thead><tbody>
<?php
foreach ( $data['rows'] ?? array() as $booking ) :
	?>
	<tr><td><a href="<?php echo esc_url( home_url( '/hoteles/reservas/' . absint( $booking->id ) . '/' ) ); ?>">#<?php echo esc_html( (string) $booking->id ); ?></a></td><td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $booking->booking_date . ' ' . $booking->booking_time ) ); ?></td><td><?php echo esc_html( $booking->customer_name ); ?></td><td><?php echo esc_html( $booking->origin . ' → ' . $booking->destination ); ?></td><td><?php echo esc_html( \MeTransfers\HotelPortal\Services\HotelDashboard::statusLabel( $booking->status ) ); ?></td><td>
	<?php
	$amount_cents = null !== $booking->price_cents ? (int) $booking->price_cents : (int) round( (float) $booking->price * 100 );
	echo esc_html( number_format_i18n( $amount_cents / 100, 2 ) . ' €' );
	?>
	</td></tr><?php endforeach; ?></tbody></table></div></div>
<?php
if ( (int) ( $data['pages'] ?? 1 ) > 1 ) :
	?>
	<nav class="mt-hotel-pagination" aria-label="<?php esc_attr_e( 'Páginas de reservas', 'me-transfers' ); ?>">
	<?php
	for ( $page_number = 1; $page_number <= (int) $data['pages']; $page_number++ ) :
		?>
	<a <?php echo $page_number === (int) $data['page'] ? 'aria-current="page" class="is-active"' : ''; ?> href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'pg'     => $page_number,
					'q'      => $data['search'],
					'status' => $data['status'],
				),
				\MeTransfers\HotelPortal\Routing\Router::url( 'bookings' )
			)
		);
		?>
	"><?php echo esc_html( (string) $page_number ); ?></a><?php endfor; ?></nav><?php endif; ?>
</section>
