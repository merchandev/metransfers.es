<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$import_result = isset( $import_result ) ? (array) $import_result : array();
?>
<section aria-labelledby="mt-hotel-import-title">
	<p class="mt-hotel-eyebrow"><?php esc_html_e( 'Carga de datos', 'me-transfers' ); ?></p>
	<h1 id="mt-hotel-import-title"><?php esc_html_e( 'Importar reservas', 'me-transfers' ); ?></h1>
	<p class="mt-hotel-lead"><?php esc_html_e( 'Añade reservas existentes al Dashboard del hotel activo mediante un archivo Excel o CSV.', 'me-transfers' ); ?></p>

	<?php if ( $import_result ) : ?>
		<?php if ( empty( $import_result['errors'] ) ) : ?>
			<div class="mt-hotel-alert mt-hotel-alert--success" role="status">
				<?php // translators: 1: imported reservations, 2: skipped duplicates. ?>
				<?php echo esc_html( sprintf( __( '%1$d reservas importadas. %2$d duplicadas omitidas.', 'me-transfers' ), (int) $import_result['imported'], (int) $import_result['skipped'] ) ); ?>
			</div>
		<?php else : ?>
			<div class="mt-hotel-alert" role="alert">
				<?php // translators: %d is the number of imported reservations. ?>
				<strong><?php echo esc_html( sprintf( __( '%d reservas importadas.', 'me-transfers' ), (int) $import_result['imported'] ) ); ?></strong>
				<ul>
				<?php
				foreach ( $import_result['errors'] as $import_error ) :
					?>
					<li><?php echo esc_html( $import_error ); ?></li><?php endforeach; ?></ul>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="mt-hotel-import-grid">
		<section class="mt-hotel-phase-card">
			<h2><?php esc_html_e( 'Seleccionar archivo', 'me-transfers' ); ?></h2>
			<form method="post" enctype="multipart/form-data" class="mt-hotel-login-form">
				<?php wp_nonce_field( \MeTransfers\HotelPortal\Services\BookingImporter::NONCE_ACTION, 'mt_import_nonce' ); ?>
				<input type="hidden" name="mt_import_bookings" value="1">
				<div class="mt-hotel-file-field">
					<label for="mt-booking-file"><?php esc_html_e( 'Archivo XLSX o CSV', 'me-transfers' ); ?></label>
					<input id="mt-booking-file" name="booking_file" type="file" accept=".xlsx,.csv" required>
					<small><?php esc_html_e( 'Máximo 5 MB y 2.000 reservas por archivo.', 'me-transfers' ); ?></small>
				</div>
				<button class="mt-hotel-primary-button" type="submit"><?php esc_html_e( 'Importar reservas', 'me-transfers' ); ?></button>
			</form>
		</section>

		<section class="mt-hotel-phase-card">
			<h2><?php esc_html_e( 'Columnas admitidas', 'me-transfers' ); ?></h2>
			<p><?php esc_html_e( 'La primera fila debe contener los títulos. Las columnas obligatorias están marcadas con un asterisco.', 'me-transfers' ); ?></p>
			<div class="mt-hotel-columns-list">
				<span>Nº Ref (ID)</span><span>Hora de Registro</span><strong>Estado</strong><strong>Nombre del Cliente *</strong>
				<span>Email</span><span>Teléfono</span><span>Precio (€)</span><span>Distancia (km)</span>
				<strong>Origen *</strong><strong>Destino *</strong><strong>Fecha Traslado *</strong><strong>Hora Traslado *</strong>
				<span>Vehículo</span><span>Pasajeros</span><span>Equipaje</span><span>Nº Vuelo</span>
				<span>Notas Adicionales</span><span>Token Hotel</span><span>Origen/Fuente</span>
			</div>
		</section>
	</div>
	<div class="mt-hotel-import-note"><strong><?php esc_html_e( 'Importante:', 'me-transfers' ); ?></strong> <?php esc_html_e( 'Las reservas se asignan al hotel seleccionado arriba. El token del Excel se ignora y nunca cambia el hotel de destino. La importación no genera cobros ni notificaciones.', 'me-transfers' ); ?></div>
</section>
