<?php
namespace MeTransfers\Core;

class Assets {
    public function register() {
        // Run after plugins so the integrated booking design wins the cascade
        // while an old external booking plugin is being removed from a site.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 100 );
    }

    public function enqueue() {
        $site_tracking_path = get_template_directory() . '/assets/js/site-tracking.js';
        if ( file_exists( $site_tracking_path ) ) {
            wp_enqueue_script(
                'mt-site-tracking',
                get_template_directory_uri() . '/assets/js/site-tracking.js',
                array(),
                (string) filemtime( $site_tracking_path ),
                true
            );
        }

        if ( ! self::is_booking_context() ) {
            return;
        }

        $base_dir = get_template_directory() . '/assets/css/';
        $base_url = get_template_directory_uri() . '/assets/css/';

        $phase = self::booking_phase();
        $styles = array(
            'mt-tokens'     => 'tokens.css',
            'mt-components' => 'components.css',
        );
        if ( in_array( $phase, array( 'search', 'vehicle', 'details', 'confirmation', 'hotel' ), true ) ) {
            $styles['mt-booking'] = 'booking.css';
        }
        if ( 'payment' === $phase ) {
            $styles['mt-checkout'] = 'checkout.css';
        }

        $dependency = array();
        foreach ( $styles as $handle => $filename ) {
            $path = $base_dir . $filename;
            if ( ! file_exists( $path ) ) {
                continue;
            }

            wp_enqueue_style( $handle, $base_url . $filename, $dependency, (string) filemtime( $path ) );
            $dependency = array( $handle );
        }

        $tracking_path = get_template_directory() . '/assets/js/booking-tracking.js';
        if ( file_exists( $tracking_path ) ) {
            wp_enqueue_script(
                'mt-booking-tracking',
                get_template_directory_uri() . '/assets/js/booking-tracking.js',
                wp_script_is( 'mt-site-tracking', 'enqueued' ) ? array( 'mt-site-tracking' ) : array(),
                (string) filemtime( $tracking_path ),
                true
            );
        }
    }

    public static function is_booking_context() {
        return 'none' !== self::booking_phase();
    }

    public static function booking_phase() {
        if ( is_admin() ) {
            return 'none';
        }

        if ( is_page( 'reservas-metransfers' ) && isset( $_GET['payment_result'] ) ) {
            return 'confirmation';
        }

        if ( is_front_page() ) {
            return 'search';
        }

        if ( is_page( 'seleccionar-vehiculo' ) ) {
            return 'vehicle';
        }

        if ( is_page( 'reservas-metransfers' ) ) {
            return 'details';
        }

        if ( is_page( 'pago' ) ) {
            return 'payment';
        }

        if ( is_page( 'reservas-hotel' ) ) {
            return 'hotel';
        }

        if ( is_singular( 'ruta' ) ) {
            return 'search';
        }

        if ( is_page_template( array( 'template-madre.php', 'page-seo-dynamic.php' ) ) ) {
            return 'search';
        }

        if ( ! is_singular() ) {
            return 'none';
        }

        $post = get_post();
        if ( ! $post || ! is_string( $post->post_content ) ) {
            return 'none';
        }

        if ( 'page' === $post->post_type
            && ( 0 === strpos( $post->post_name, 'taxis-' ) || 'reservaciones' === $post->post_name ) ) {
            return 'search';
        }

        $phases = array(
            'confirmation' => array(),
            'payment'      => array( 'wptb_checkout', 'wptb_stripe_checkout', 'wptb_redsys_checkout' ),
            'details'      => array( 'wptb_booking_details' ),
            'vehicle'      => array( 'wptb_vehicle_selection' ),
            'hotel'        => array( 'hqp_booking_form' ),
            'search'       => array( 'wptb_booking_form', 'wptb_booking', 'wptb_popular_destinations_carousel', 'wptb_popular_destinations', 'wptb_booking_popup', 'premium_transfers_search' ),
        );

        foreach ( $phases as $phase => $shortcodes ) {
            foreach ( $shortcodes as $shortcode ) {
                if ( has_shortcode( $post->post_content, $shortcode ) ) {
                    return $phase;
                }
            }
        }

        return 'none';
    }
}
