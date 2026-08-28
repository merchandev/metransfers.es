<?php
// Script to run content repopulation

// Bypass AJAX checks and call the logic directly
if ( function_exists( 'me_transfers_sync_service_pages' ) ) {
    delete_option( 'me_transfers_services_sync_version' );
    me_transfers_sync_service_pages();
}

// Regenerate ALL destinations just in case
if ( function_exists( 'me_transfers_sync_destination_pages' ) ) {
    delete_option( 'me_transfers_destinations_sync_version' );
    me_transfers_sync_destination_pages();
}

if ( function_exists( 'me_transfers_sync_legal_pages' ) ) {
    delete_option( 'me_transfers_legal_pages_sync_version' );
    me_transfers_sync_legal_pages();
}

// Then call the repopulate script manually
require_once get_template_directory() . '/includes/admin-content-repopulate.php';

$hub = get_page_by_path( 'destinos', 'OBJECT', 'page' );
$hub_id = $hub ? $hub->ID : 0;
if ( ! $hub ) {
    $hub_id = wp_insert_post( array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => 'MeTransfers Barcelona - Destinos de traslados privados desde Barcelona',
        'post_name'    => 'destinos',
    ));
}

// Create all destinations
if ( function_exists( 'me_transfers_get_destination_catalog' ) ) {
    $catalog = me_transfers_get_destination_catalog();
    foreach ( $catalog as $slug => $dest ) {
        $page = get_page_by_path( 'destinos/' . $slug, 'OBJECT', 'page' );
        if ( ! $page ) { $page = get_page_by_path( $slug, 'OBJECT', 'page' ); }
        if ( ! $page ) {
            wp_insert_post( array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $dest['title'],
                'post_name'    => $slug,
                'post_parent'  => $hub_id,
            ));
        }
    }
}

// Create services
if ( function_exists( 'me_transfers_get_service_catalog' ) ) {
    foreach ( me_transfers_get_service_catalog() as $slug => $service ) {
        $page = get_page_by_path( $slug, 'OBJECT', 'page' );
        if ( ! $page ) {
            wp_insert_post( array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $service['title'],
                'post_name'    => $slug,
            ));
        }
    }
}
