<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$stats = isset( $page_data ) && is_array( $page_data ) ? $page_data : array();
?>
<section><p class="mt-hotel-eyebrow"><?php esc_html_e( 'Rendimiento', 'me-transfers' ); ?></p><h1><?php esc_html_e( 'Estadísticas', 'me-transfers' ); ?></h1><div class="mt-hotel-users-grid"><div class="mt-hotel-phase-card"><h2><?php esc_html_e( 'Últimos 12 meses', 'me-transfers' ); ?></h2>
<?php
foreach ( $stats['monthly'] ?? array() as $month ) :
	?>
	<div class="mt-hotel-stat-row"><strong><?php echo esc_html( $month->period ); ?></strong><span><?php echo esc_html( (string) $month->total ); ?> reservas · <?php echo esc_html( number_format_i18n( (int) $month->revenue_cents / 100, 2 ) . ' €' ); ?></span></div><?php endforeach; ?></div><div class="mt-hotel-phase-card"><h2><?php esc_html_e( 'Trayectos principales', 'me-transfers' ); ?></h2>
	<?php
	foreach ( $stats['routes'] ?? array() as $route_item ) :
		?>
	<div class="mt-hotel-stat-row"><span><?php echo esc_html( $route_item->origin . ' → ' . $route_item->destination ); ?></span><strong><?php echo esc_html( (string) $route_item->total ); ?></strong></div><?php endforeach; ?></div></div></section>
