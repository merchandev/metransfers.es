<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$customers = isset( $page_data ) && is_array( $page_data ) ? $page_data : array();
?>
<section><p class="mt-hotel-eyebrow"><?php esc_html_e( 'Histórico', 'me-transfers' ); ?></p><h1><?php esc_html_e( 'Clientes', 'me-transfers' ); ?></h1><div class="mt-hotel-phase-card mt-hotel-dashboard-table-card"><div class="mt-hotel-table-wrap"><table class="mt-hotel-table"><thead><tr><th><?php esc_html_e( 'Cliente', 'me-transfers' ); ?></th><th>Email</th><th><?php esc_html_e( 'Teléfono', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Reservas', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Última reserva', 'me-transfers' ); ?></th><th><?php esc_html_e( 'Producción', 'me-transfers' ); ?></th></tr></thead><tbody>
<?php
foreach ( $customers as $customer ) :
	?>
	<tr><td><?php echo esc_html( $customer->customer_name ); ?></td><td><?php echo esc_html( $customer->customer_email ); ?></td><td><?php echo esc_html( $customer->customer_phone ); ?></td><td><?php echo esc_html( (string) $customer->bookings ); ?></td><td><?php echo esc_html( mysql2date( 'd/m/Y', $customer->last_booking ) ); ?></td><td><?php echo esc_html( number_format_i18n( (int) $customer->total_cents / 100, 2 ) . ' €' ); ?></td></tr><?php endforeach; ?></tbody></table></div></div></section>
