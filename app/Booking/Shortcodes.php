<?php
namespace MeTransfers\Booking;

class Shortcodes {
    public function register() {
        add_shortcode( 'wptb_booking_form', array( $this, 'render_booking_form' ) );
        add_shortcode( 'wptb_booking', array( $this, 'render_booking_form' ) );
        add_shortcode( 'wptb_vehicle_selection', array( $this, 'render_vehicle_selection' ) );
        add_shortcode( 'wptb_booking_details', array( $this, 'render_booking_details' ) );
        add_shortcode( 'wptb_stripe_checkout', array( $this, 'render_checkout_page' ) ); 
        add_shortcode( 'wptb_redsys_checkout', array( $this, 'render_checkout_page' ) ); 
        add_shortcode( 'wptb_checkout', array( $this, 'render_checkout_page' ) ); 
        add_shortcode( 'wptb_popular_destinations_carousel', array( $this, 'render_popular_carousel' ) );
        add_shortcode( 'wptb_popular_destinations', array( $this, 'render_popular_carousel' ) );
        add_shortcode( 'wptb_booking_popup', array( $this, 'render_booking_popup' ) );
    }

    public function render_booking_form( $atts ) {
        ob_start();
        $booking_source = 'Metransfers';
        require MT_WPTB_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }

    public function render_vehicle_selection( $atts ) {
        ob_start();
        require MT_WPTB_DIR . 'templates/booking-vehicles.php';
        return ob_get_clean();
    }

    public function render_booking_details( $atts ) {
        ob_start();
        require MT_WPTB_DIR . 'templates/booking-details.php';
        return ob_get_clean();
    }

    public function render_checkout_page( $atts ) {
        ob_start();
        require MT_WPTB_DIR . 'templates/checkout.php';
        return ob_get_clean();
    }

    public function render_popular_carousel( $atts ) {
        ob_start();
        require MT_WPTB_DIR . 'templates/popular-carousel.php';
        return ob_get_clean();
    }

    public function render_booking_popup( $atts ) {
        ob_start();
        require MT_WPTB_DIR . 'templates/booking-modal.php';
        return ob_get_clean();
    }
}
