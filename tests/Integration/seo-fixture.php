<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'MT_SEO_INTEGRATION' ) || ! MT_SEO_INTEGRATION || 'http://127.0.0.1:8080' !== home_url() ) {
	exit( 'Disposable integration fixture required.' );
}
$fixtures = array(
	array( 'ruta', 'barcelona-salou', '1' ),
	array( 'ruta', 'barcelona-andorra', '0' ),
	array( 'ruta', 'barcelona-cadaques', '' ),
	array( 'ruta', 'barcelona-reus', '' ),
	array( 'ruta', 'barcelona-montserrat', '' ),
	array( 'page', 'taxis-barcelona-salou', '' ),
	array( 'page', 'taxis-barcelona-vielha', '' ),
	array( 'page', 'costa-brava-taxis', '' ),
	array( 'page', 'reus-taxis', '' ),
	array( 'page', 'reus-traslados', '' ),
	array( 'page', 'montserrat-taxis', '' ),
	array( 'page', 'montserrat-traslados', '' ),
	array( 'page', 'barcelona-taxis', '' ),
	array( 'page', 'barcelona-traslados', '' ),
	array( 'page', 'destinos-salou-fixture', '' ),
);
foreach ( $fixtures as $fixture ) {
	$id = wp_insert_post( array( 'post_type' => $fixture[0], 'post_name' => $fixture[1], 'post_title' => $fixture[1], 'post_content' => '<p>Contenido editorial de prueba.</p>', 'post_status' => 'publish' ), true );
	if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
	if ( '' !== $fixture[2] ) { update_post_meta( $id, '_mt_seo_ready', $fixture[2] ); }
	if ( 'ruta' === $fixture[0] ) {
		update_post_meta( $id, '_mt_ruta_origen', 'Barcelona centro' );
		update_post_meta( $id, '_mt_ruta_destino', ucfirst( str_replace( 'barcelona-', '', $fixture[1] ) ) );
	}
}
flush_rewrite_rules();
WP_CLI::success( 'SEO fixture prepared.' );
