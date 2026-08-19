<?php
namespace MeTransfers\Core;

class Assets {
    public function register() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
    }

    public function enqueue() {
        if ( ! self::is_booking_context() ) {
            return;
        }

        $base_dir = get_template_directory() . '/assets/css/';
        $base_url = get_template_directory_uri() . '/assets/css/';

        $styles = array(
            'mt-tokens'     => 'tokens.css',
            'mt-components' => 'components.css',
            'mt-booking'    => 'booking.css',
            'mt-checkout'   => 'checkout.css',
        );

        $dependency = array();
        foreach ( $styles as $handle => $filename ) {
            $path = $base_dir . $filename;
            if ( ! file_exists( $path ) ) {
                continue;
            }

            wp_enqueue_style( $handle, $base_url . $filename, $dependency, (string) filemtime( $path ) );
            $dependency = array( $handle );
        }
    }

    public static function is_booking_context() {
        if ( is_admin() ) {
            return false;
        }

        if ( is_front_page() || is_page( array( 'seleccionar-vehiculo', 'reservas-metransfers', 'pago', 'reservas-hotel' ) ) ) {
            return true;
        }

        if ( ! is_singular() ) {
            return false;
        }

        $post = get_post();
        if ( ! $post || ! is_string( $post->post_content ) ) {
            return false;
        }

        foreach ( array( 'wptb_booking_form', 'wptb_booking', 'wptb_vehicle_selection', 'wptb_booking_details', 'wptb_checkout', 'wptb_stripe_checkout', 'wptb_redsys_checkout', 'wptb_popular_destinations_carousel', 'wptb_popular_destinations', 'wptb_booking_popup', 'premium_transfers_search', 'hqp_booking_form' ) as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                return true;
            }
        }

        return false;
    }
}
