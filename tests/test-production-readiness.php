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
$site_tracking = file_get_contents( $root . '/assets/js/site-tracking.js' );
$checkout = file_get_contents( $root . '/app/Legacy/WPTB/templates/checkout.php' );
$i18n_runtime = file_get_contents( $root . '/includes/i18n.php' );
$functions = file_get_contents( $root . '/functions.php' );
$notification_service = file_get_contents( $root . '/app/Notifications/NotificationService.php' );
$outbox = file_get_contents( $root . '/app/Analytics/PurchaseOutbox.php' );
$generic_outbox = file_get_contents( $root . '/app/Core/Outbox.php' );
$booking_events = file_get_contents( $root . '/app/Booking/BookingEvents.php' );
$release_gate = file_get_contents( $root . '/app/Core/ReleaseGate.php' );
$readme = file_get_contents( $root . '/README.md' );
$loader = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-loader.php' );

assert_readiness( false === strpos( $booking_form, 'payment_result' ), 'The search form must not interpret payment return parameters.' );
assert_readiness( false === strpos( $booking_form, 'payment_intent_id' ), 'The search form must not query bookings by predictable payment references.' );
assert_readiness( false === strpos( $booking_form, '$wpdb' ), 'The search form must not access booking records.' );

assert_readiness( false !== strpos( $booking_details, "'paid' === \$wptb_payment_booking->payment_status" ), 'Confirmation must require a paid database state.' );
assert_readiness( false !== strpos( $booking_details, "array( 'confirmed', 'completed' )" ), 'Confirmation must require a final booking state.' );
assert_readiness( false !== strpos( $booking_details, 'validate_confirmation_request' ), 'Both OK and KO returns must validate their order-bound HMAC token.' );
assert_readiness( false !== strpos( $booking_details, 'data-payment-state' ), 'The server-confirmed state must be available to the tracking layer.' );

assert_readiness(
    false === strpos( $public_controller, "'wp_ajax_wptb_create_booking'," )
        && false === strpos( $public_controller, "'wp_ajax_nopriv_wptb_create_booking'," ),
    'The orphan create-booking hook must stay removed.'
);
assert_readiness( false === strpos( $public_controller, 'wp_ajax_wptb_get_pricing' ), 'The orphan pricing hook must stay removed.' );
assert_readiness( false === strpos( $loader, 'wptb_calculate_price' ) && false === strpos( $public_controller, 'function ajax_calculate_price' ), 'The legacy browser-supplied pricing endpoint must stay removed.' );
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
    assert_readiness( false !== strpos( $tracking . $site_tracking, "'$event'" ), "Tracking event $event is missing." );
}

assert_readiness( false === strpos( $checkout, 'wptb-payment-success' ), 'Checkout must not contain a premature success view.' );
assert_readiness( false === strpos( $checkout, 'style=' ) && false === strpos( $checkout, '<style' ), 'Checkout must use the shared design system without inline CSS.' );
assert_readiness( false === strpos( $booking_details, 'style=' ) && false === strpos( $booking_details, '<style' ), 'Booking details must use the shared design system without inline CSS.' );
assert_readiness( false !== strpos( $public_controller, 'QuoteService::create' ), 'Payment must use the authoritative server quote.' );
assert_readiness( false !== strpos( $public_controller, 'terms_accepted_at' ), 'Terms acceptance must be persisted server-side.' );
assert_readiness( false !== strpos( $public_controller, 'booking_locale' ), 'The booking locale must be persisted.' );
assert_readiness( false !== strpos( $i18n_runtime, 'Public rendering is cache-only' ), 'Public translations must be cache-only.' );
assert_readiness( 1 === substr_count( $public_controller, 'function send_whatsapp_alert(' ), 'Only the deprecated WhatsApp facade may remain in the legacy controller.' );
assert_readiness( false === strpos( $public_controller, 'reservas@barcelonatours.email' ), 'Notification senders must not be hardcoded.' );
assert_readiness( false !== strpos( $notification_service, "Settings::get( 'smtp_from'" ), 'Notification sender must come from platform settings.' );
assert_readiness( false !== strpos( $functions, 'mt_is_transactional_page' ) && false !== strpos( $functions, "'noindex' => true" ), 'Transactional pages must be noindex.' );
assert_readiness( false !== strpos( $assets, "'mt-site-tracking'" ), 'Phone and WhatsApp tracking must be enqueued globally.' );
assert_readiness( false === strpos( $public_controller, "wp_enqueue_style( 'wptb-main-style'" ), 'Legacy funnel CSS must not be enqueued.' );
assert_readiness( false !== strpos( $outbox, "'analytics.purchase'" ) && false !== strpos( $outbox, 'Outbox::enqueue' ), 'Financial purchase tracking must use the generic durable outbox.' );
assert_readiness( false !== strpos( $generic_outbox, 'INSERT IGNORE' ) && false !== strpos( $generic_outbox, "'failed'" ) && false !== strpos( $generic_outbox, 'backoffSeconds' ), 'The generic outbox must provide idempotency, retry backoff and dead-letter state.' );
assert_readiness( false !== strpos( $booking_events, "'booking.paid:'" ) && false !== strpos( $booking_events, "'whatsapp.admin'" ), 'Paid booking work must expand into idempotent per-channel events.' );
assert_readiness( false !== strpos( $release_gate, 'redsys_sandbox_verified_at' ) && false !== strpos( $release_gate, 'maps_credentials_rotated_at' ), 'Live payments must be gated by external security attestations.' );

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
