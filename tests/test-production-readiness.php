<?php

function assert_readiness( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

$root = dirname( __DIR__ );
$booking_form = file_get_contents( $root . '/app/Legacy/WPTB/templates/booking-form.php' );
$booking_details = file_get_contents( $root . '/app/Legacy/WPTB/templates/booking-details.php' );
$public_controller = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-public.php' );
$assets = file_get_contents( $root . '/app/Core/Assets.php' );
$tracking = file_get_contents( $root . '/assets/js/booking-tracking.js' );
$readme = file_get_contents( $root . '/README.md' );

assert_readiness( false === strpos( $booking_form, 'payment_result' ), 'The search form must not interpret payment return parameters.' );
assert_readiness( false === strpos( $booking_form, 'payment_intent_id' ), 'The search form must not query bookings by predictable payment references.' );
assert_readiness( false === strpos( $booking_form, '$wpdb' ), 'The search form must not access booking records.' );

assert_readiness( false !== strpos( $booking_details, "'paid' === \$wptb_payment_booking->payment_status" ), 'Confirmation must require a paid database state.' );
assert_readiness( false !== strpos( $booking_details, "array( 'confirmed', 'completed' )" ), 'Confirmation must require a final booking state.' );
assert_readiness( false !== strpos( $booking_details, 'verify_confirmation_token' ), 'Confirmation lookup must require an order-bound HMAC token.' );
assert_readiness( false !== strpos( $booking_details, 'data-payment-state' ), 'The server-confirmed state must be available to the tracking layer.' );

assert_readiness( false === strpos( $public_controller, 'wp_ajax_wptb_create_booking' ), 'The orphan create-booking hook must stay removed.' );
assert_readiness( false === strpos( $public_controller, 'wp_ajax_wptb_get_pricing' ), 'The orphan pricing hook must stay removed.' );
assert_readiness( false === strpos( $public_controller, 'debug_info' ), 'Public AJAX responses must not expose debug payloads.' );
assert_readiness( false === strpos( $public_controller, 'total_vehicles_in_db' ), 'Public AJAX responses must not expose database counts.' );
assert_readiness( false === strpos( $public_controller, 'wptb-debug' ), 'The frontend must not enqueue the legacy debug helper.' );
assert_readiness( ! file_exists( $root . '/app/Legacy/WPTB/assets/js/debug-helper.js' ), 'The legacy browser debug helper must not ship.' );
assert_readiness( ! file_exists( $root . '/app/Legacy/WPTB/templates/stripe-checkout.php' ), 'The unused legacy Stripe checkout template must not ship.' );

assert_readiness( false !== strpos( $assets, "return 'confirmation';" ), 'Assets must distinguish the confirmation phase.' );
assert_readiness( false === strpos( $public_controller, "wp_enqueue_script( 'jspdf'" ), 'jsPDF must not be part of the initial booking payload.' );
assert_readiness( false !== strpos( $public_controller, "'pdf_library_url'" ), 'The receipt library must be available for lazy loading.' );
assert_readiness( false !== strpos( $tracking, "container.dataset.paymentState !== 'confirmed'" ), 'Purchase tracking must require a server-confirmed state.' );

foreach ( array( 'booking_start', 'route_search', 'vehicle_select', 'begin_checkout', 'add_payment_info', 'purchase', 'generate_lead', 'booking_error', 'payment_error' ) as $event ) {
    assert_readiness( false !== strpos( $tracking, "'$event'" ), "Tracking event $event is missing." );
}

foreach ( array(
    'add_filters.php',
    'bump_version.php',
    'bump_version_func.php',
    'check_func.php',
    'check_func_51.php',
    'check_lines.php',
    'mt-seo-importer.php',
    'test-yoast-db.php',
    'test-yoast.php',
    'translate_footer_full.php',
    'translate_footer_full2.php',
    'translate_footer_last.php',
    'translate_fp_all.php',
    'translate_fp_flota.php',
    'translate_fp_p4.php',
    'translate_fp_p5.php',
    'translate_fp_p6.php',
    'translate_fp_s1.php',
    'translate_fp_s2.php',
    'translate_fp_s3.php',
    'translate_fp3.php',
    'translate_gdpr_footer.php',
    'translate_header.php',
    'translate_info.php',
    'translate_nav.php',
    'translate_wa2.php',
    'wrap_esc.php',
) as $obsolete_file ) {
    assert_readiness( ! file_exists( $root . '/' . $obsolete_file ), "Obsolete root helper $obsolete_file must stay removed." );
}

assert_readiness( false !== strpos( $readme, 'MT_GOOGLE_MAPS_SERVER_API_KEY' ), 'README must document server-side Maps configuration.' );
assert_readiness( false !== strpos( $readme, 'Redsys Sandbox' ), 'README must document the staging payment gate.' );

echo "Production-readiness tests passed.\n";
