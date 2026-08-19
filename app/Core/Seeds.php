<?php
namespace MeTransfers\Core;

final class Seeds {
    public function register() {
        add_action( 'after_switch_theme', array( __CLASS__, 'run' ), 20 );
    }

    public static function run() {
        self::ensureVehicleTypes();
        self::ensureTransferProduct();
        self::ensurePage( 'Finalizar Reserva', 'reservas-metransfers', '[wptb_booking_details]' );
        self::ensurePage( 'Seleccionar Vehiculo', 'seleccionar-vehiculo', '[wptb_vehicle_selection]' );
        self::ensurePage( 'Finalizar Pago', 'pago', '[wptb_checkout]' );
    }

    private static function ensureVehicleTypes() {
        global $wpdb;
        $table = $wpdb->prefix . 'wptb_vehicle_types';
        if ( 0 !== (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) ) {
            return;
        }

        $types = array(
            array( 'name' => 'Sedán', 'slug' => 'sedan', 'description' => 'Vehículo sedán estándar para 3-4 pasajeros', 'display_order' => 1 ),
            array( 'name' => 'SUV', 'slug' => 'suv', 'description' => 'Vehículo grande tipo SUV para 5-6 pasajeros', 'display_order' => 2 ),
            array( 'name' => 'Van', 'slug' => 'van', 'description' => 'Furgoneta para grupos de 7-8 pasajeros', 'display_order' => 3 ),
            array( 'name' => 'Minibús', 'slug' => 'minibus', 'description' => 'Vehículo para grupos grandes de 9-15 pasajeros', 'display_order' => 4 ),
            array( 'name' => 'Lujo', 'slug' => 'luxury', 'description' => 'Vehículo de lujo premium', 'display_order' => 5 ),
        );
        foreach ( $types as $type ) {
            $wpdb->insert( $table, $type );
        }
    }

    private static function ensureTransferProduct() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        $product_id = absint( get_option( 'wptb_transfer_product_id' ) );
        if ( $product_id && get_post( $product_id ) ) {
            return;
        }

        $product_id = wp_insert_post(
            array(
                'post_title'   => 'Transfer Service',
                'post_content' => 'Booking transfer payment.',
                'post_status'  => 'publish',
                'post_type'    => 'product',
            )
        );
        if ( ! $product_id || is_wp_error( $product_id ) ) {
            return;
        }

        update_post_meta( $product_id, '_visibility', 'hidden' );
        update_post_meta( $product_id, '_stock_status', 'instock' );
        update_post_meta( $product_id, '_price', '1' );
        update_post_meta( $product_id, '_regular_price', '1' );
        update_post_meta( $product_id, '_virtual', 'yes' );
        update_option( 'wptb_transfer_product_id', $product_id, false );
    }

    private static function ensurePage( $title, $slug, $content ) {
        if ( get_page_by_path( $slug ) ) {
            return;
        }

        wp_insert_post(
            array(
                'post_title'     => $title,
                'post_name'      => $slug,
                'post_content'   => $content,
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            )
        );
    }
}
