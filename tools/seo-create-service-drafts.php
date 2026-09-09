<?php
/** WP-CLI: wp eval-file tools/seo-create-service-drafts.php [apply] */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 'WP-CLI required.' ); }
$apply = in_array( 'apply', $args ?? array(), true );
$pages = array(
    'traslado-estacion-sants-barcelona' => array(
        'title' => 'Traslado privado desde la estación de Sants en Barcelona',
        'description' => 'Organiza tu recogida en Barcelona Sants y el traslado al hotel, aeropuerto, puerto u otro destino. Consulta disponibilidad y confirma el punto de encuentro.',
    ),
    'traslados-hoteles-barcelona' => array(
        'title' => 'Traslados privados a hoteles de Barcelona',
        'description' => 'Reserva tu traslado privado entre hoteles de Barcelona, aeropuerto, puerto y estaciones. Confirma direcciones, horarios y equipaje antes de tu viaje.',
    ),
);
foreach ( $pages as $slug => $page ) {
    if ( get_page_by_path( $slug ) ) { WP_CLI::log( 'Preserved existing page: ' . $slug ); continue; }
    $template = 'page-' . $slug . '.php';
    if ( ! file_exists( get_template_directory() . '/' . $template ) ) { WP_CLI::error( 'Deploy the new theme first: ' . $template ); }
    WP_CLI::log( 'Create draft: ' . $slug );
    if ( ! $apply ) { continue; }
    $id = wp_insert_post( array(
        'post_type' => 'page', 'post_status' => 'draft', 'post_name' => $slug,
        'post_title' => $page['title'], 'post_content' => '',
        'meta_input' => array(
            '_wp_page_template' => $template,
            '_yoast_wpseo_title' => $page['title'] . ' | MeTransfers',
            '_yoast_wpseo_metadesc' => $page['description'],
        ),
    ), true );
    if ( is_wp_error( $id ) ) { WP_CLI::error( $id->get_error_message() ); }
    WP_CLI::log( 'Draft ID: ' . $id );
}
WP_CLI::success( $apply ? 'Drafts prepared; existing pages preserved.' : 'Dry run; pass apply to create drafts.' );
