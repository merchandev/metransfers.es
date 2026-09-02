<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$booking = isset( $page_data ) && is_object( $page_data ) ? $page_data : null;
if ( ! $booking ) {
	return;
}
?>
<section><p class="mt-hotel-eyebrow"><?php esc_html_e( 'Reserva', 'me-transfers' ); ?></p><h1>#<?php echo esc_html( (string) $booking->id ); ?></h1><div class="mt-hotel-detail-grid">
<?php
foreach ( array(
	'Fecha'     => $booking->booking_date . ' ' . $booking->booking_time,
	'Estado'    => \MeTransfers\HotelPortal\Services\HotelDashboard::statusLabel( $booking->status ),
	'Cliente'   => $booking->customer_name,
	'Email'     => $booking->customer_email,
	'Teléfono'  => $booking->customer_phone,
	'Origen'    => $booking->origin,
	'Destino'   => $booking->destination,
	'Vehículo'  => \MeTransfers\HotelPortal\Services\HotelOperations::vehicleName( $booking ),
	'Distancia' => number_format_i18n( (float) $booking->distance_km, 2 ) . ' km',
	'Precio'    => number_format_i18n( null !== $booking->price_cents ? (int) $booking->price_cents / 100 : (float) $booking->price, 2 ) . ' €',
	'Pasajeros' => $booking->passengers,
	'Equipaje'  => $booking->suitcases,
	'Vuelo'     => $booking->flight_number,
	'Notas'     => $booking->notes,
) as $label => $value ) :
	?>
	<div><span><?php echo esc_html( $label ); ?></span><strong><?php echo esc_html( (string) $value ); ?></strong></div><?php endforeach; ?></div></section>
