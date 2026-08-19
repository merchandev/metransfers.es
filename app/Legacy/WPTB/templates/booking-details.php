<?php
/**
 * Booking details and the sole trusted payment confirmation view.
 */
$wptb_payment_state = 'none';
$wptb_payment_order_id = '';
$wptb_payment_booking = null;
$wptb_i18n = \MeTransfers\Booking\I18n::strings();

if ( isset( $_GET['payment_result'] ) ) {
    $result = sanitize_key( wp_unslash( $_GET['payment_result'] ) );
    $raw_order = isset( $_GET['oid'] ) ? sanitize_text_field( wp_unslash( $_GET['oid'] ) ) : '';
    $wptb_payment_order_id = preg_replace( '/[^0-9A-Za-z]/', '', $raw_order );

    if ( 'ko' === $result ) {
        $wptb_payment_state = 'failed';
    } elseif ( 'ok' === $result && '' !== $wptb_payment_order_id && $raw_order === $wptb_payment_order_id ) {
        $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        if ( ! \MeTransfers\Payments\Redsys\Gateway::verify_confirmation_token( $wptb_payment_order_id, $token ) ) {
            $wptb_payment_state = 'invalid';
        } else {
            global $wpdb;
            $table = $wpdb->prefix . 'wptb_bookings';
            $wptb_payment_booking = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, status, payment_status, price, booking_locale FROM $table WHERE payment_intent_id = %s",
                    $wptb_payment_order_id
                )
            );
            if ( $wptb_payment_booking && ! empty( $wptb_payment_booking->booking_locale ) ) {
                $wptb_i18n = \MeTransfers\Booking\I18n::strings( $wptb_payment_booking->booking_locale );
            }
            $wptb_payment_state = $wptb_payment_booking
                && 'paid' === $wptb_payment_booking->payment_status
                && in_array( $wptb_payment_booking->status, array( 'confirmed', 'completed' ), true )
                    ? 'confirmed'
                    : 'pending';
        }
    } else {
        $wptb_payment_state = 'invalid';
    }
}

$wptb_state_class = static function ( $state ) use ( $wptb_payment_state ) {
    return $state === $wptb_payment_state ? '' : ' is-hidden';
};
?>
<div id="wptb-plugin-container" class="wptb-iso mt-booking"
    data-payment-state="<?php echo esc_attr( $wptb_payment_state ); ?>"
    <?php if ( 'confirmed' === $wptb_payment_state && $wptb_payment_booking ) : ?>
        data-booking-id="<?php echo esc_attr( $wptb_payment_booking->id ); ?>"
        data-payment-value="<?php echo esc_attr( $wptb_payment_booking->price ); ?>"
        data-payment-currency="EUR"
    <?php endif; ?>>

    <ol class="mt-progress" aria-label="<?php echo esc_attr( $wptb_i18n['booking_details'] ); ?>">
        <li class="is-complete">1</li><li class="is-complete">2</li><li class="is-active" aria-current="step">3</li><li>4</li>
    </ol>

    <section id="wptb-step-3" class="mt-booking__step<?php echo esc_attr( $wptb_state_class( 'none' ) ); ?>">
        <h1 class="mt-booking__title"><?php echo esc_html( $wptb_i18n['booking_details'] ); ?></h1>
        <div class="mt-booking__layout">
            <aside class="mt-booking__summary">
                <div id="route-map" class="mt-booking__map" aria-label="<?php echo esc_attr( $wptb_i18n['trip_summary'] ); ?>"></div>
                <h2><?php echo esc_html( $wptb_i18n['trip_summary'] ); ?></h2>
                <dl>
                    <div><dt><?php echo esc_html( $wptb_i18n['vehicle'] ); ?></dt><dd id="summary-vehicle">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['type'] ); ?></dt><dd id="summary-trip-type">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['origin'] ); ?></dt><dd id="summary-origin">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['destination'] ); ?></dt><dd id="summary-destination">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['distance'] ); ?></dt><dd><span id="summary-distance">-</span> · <span id="summary-duration">-</span></dd></div>
                    <div class="mt-booking__total"><dt><?php echo esc_html( $wptb_i18n['total'] ); ?></dt><dd id="summary-price">EUR 0.00</dd></div>
                </dl>
            </aside>

            <form id="wptb-details-form" class="mt-form mt-booking__form" action="" method="post">
                <div class="mt-form__grid">
                    <div class="mt-form__field"><label for="wptb-passengers"><?php echo esc_html( $wptb_i18n['passengers'] ); ?></label><input class="mt-input" type="number" id="wptb-passengers" min="1" max="50" required></div>

                    <fieldset id="wptb-return-details" class="mt-booking__return is-hidden">
                        <legend><?php echo esc_html( $wptb_i18n['return_details'] ); ?></legend>
                        <div class="mt-form__grid">
                            <div class="mt-form__field"><label for="wptb-return-date"><?php echo esc_html( $wptb_i18n['return_date'] ); ?></label><input class="mt-input" type="date" id="wptb-return-date"></div>
                            <div class="mt-form__field"><label for="wptb-return-time"><?php echo esc_html( $wptb_i18n['return_time'] ); ?></label><input class="mt-input" type="time" id="wptb-return-time"></div>
                            <div class="mt-form__field mt-form__field--wide"><label for="wptb-return-origin"><?php echo esc_html( $wptb_i18n['return_pickup'] ); ?></label><input class="mt-input" type="text" id="wptb-return-origin"></div>
                            <div class="mt-form__field mt-form__field--wide"><label for="wptb-return-destination"><?php echo esc_html( $wptb_i18n['return_destination'] ); ?></label><input class="mt-input" type="text" id="wptb-return-destination"></div>
                        </div>
                    </fieldset>

                    <div class="mt-form__field"><label for="wptb-fullname"><?php echo esc_html( $wptb_i18n['full_name'] ); ?></label><input class="mt-input" type="text" id="wptb-fullname" autocomplete="name" required></div>
                    <div class="mt-form__field"><label for="wptb-phone"><?php echo esc_html( $wptb_i18n['phone'] ); ?></label><input class="mt-input" type="tel" id="wptb-phone" autocomplete="tel" required></div>
                    <div class="mt-form__field mt-form__field--wide"><label for="wptb-email"><?php echo esc_html( $wptb_i18n['email'] ); ?></label><input class="mt-input" type="email" id="wptb-email" autocomplete="email" required></div>
                    <div class="mt-form__field"><label for="wptb-suitcases"><?php echo esc_html( $wptb_i18n['large_suitcases'] ); ?></label><input class="mt-input" type="number" id="wptb-suitcases" min="0" value="0"></div>
                    <div class="mt-form__field"><label for="wptb-carryOns"><?php echo esc_html( $wptb_i18n['carry_on'] ); ?></label><input class="mt-input" type="number" id="wptb-carryOns" min="0" value="0"></div>
                    <div class="mt-form__field mt-form__field--wide"><label for="wptb-flight"><?php echo esc_html( $wptb_i18n['flight_optional'] ); ?></label><input class="mt-input" type="text" id="wptb-flight"></div>
                    <div class="mt-form__field mt-form__field--wide"><label for="wptb-notes"><?php echo esc_html( $wptb_i18n['additional_notes'] ); ?></label><textarea class="mt-input" id="wptb-notes" rows="4"></textarea></div>
                </div>
                <div class="mt-actions">
                    <button type="button" class="mt-button mt-button--secondary" id="wptb-back-step3"><?php echo esc_html( $wptb_i18n['back'] ); ?></button>
                    <button type="submit" class="mt-button mt-button--primary" id="wptb-confirm-btn"><?php echo esc_html( $wptb_i18n['confirm_booking'] ); ?></button>
                </div>
            </form>
        </div>
    </section>

    <section id="wptb-step-4" class="mt-status mt-status--success<?php echo esc_attr( $wptb_state_class( 'confirmed' ) ); ?>">
        <span class="mt-status__icon" aria-hidden="true">✓</span>
        <h1><?php echo esc_html( $wptb_i18n['booking_confirmed'] ); ?></h1>
        <p><?php echo esc_html( $wptb_i18n['confirmation_email'] ); ?></p>
        <p><strong><?php echo esc_html( $wptb_i18n['reference'] ); ?>:</strong> <span id="success-order-id">#<?php echo esc_html( $wptb_payment_order_id ?: '...' ); ?></span></p>
        <div class="mt-actions">
            <button type="button" id="btn-download-pdf" class="mt-button mt-button--secondary"><?php echo esc_html( $wptb_i18n['download_receipt'] ); ?></button>
            <a href="<?php echo esc_url( \MeTransfers\Booking\I18n::url( '/' ) ); ?>" class="mt-button mt-button--primary"><?php echo esc_html( $wptb_i18n['back_home'] ); ?></a>
        </div>
    </section>

    <section id="wptb-payment-pending" class="mt-status<?php echo esc_attr( $wptb_state_class( 'pending' ) ); ?>">
        <h1><?php echo esc_html( $wptb_i18n['payment_pending_title'] ); ?></h1><p><?php echo esc_html( $wptb_i18n['payment_pending_text'] ); ?></p>
        <?php if ( $wptb_payment_order_id ) : ?><p><strong><?php echo esc_html( $wptb_i18n['reference'] ); ?>:</strong> #<?php echo esc_html( $wptb_payment_order_id ); ?></p><?php endif; ?>
    </section>

    <section id="wptb-payment-failed" class="mt-status mt-status--error<?php echo esc_attr( $wptb_state_class( 'failed' ) ); ?>">
        <h1><?php echo esc_html( $wptb_i18n['payment_failed_title'] ); ?></h1><p><?php echo esc_html( $wptb_i18n['payment_failed_text'] ); ?></p>
        <a href="<?php echo esc_url( \MeTransfers\Booking\I18n::url( '/reservas-metransfers/' ) ); ?>" class="mt-button mt-button--primary"><?php echo esc_html( $wptb_i18n['try_again'] ); ?></a>
    </section>

    <section id="wptb-payment-invalid" class="mt-status mt-status--error<?php echo esc_attr( $wptb_state_class( 'invalid' ) ); ?>">
        <h1><?php echo esc_html( $wptb_i18n['invalid_confirmation_title'] ); ?></h1><p><?php echo esc_html( $wptb_i18n['invalid_confirmation_text'] ); ?></p>
        <a href="<?php echo esc_url( \MeTransfers\Booking\I18n::url( '/' ) ); ?>" class="mt-button mt-button--primary"><?php echo esc_html( $wptb_i18n['back_home'] ); ?></a>
    </section>
</div>
