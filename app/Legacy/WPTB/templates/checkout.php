<?php
/**
 * Authoritative Redsys checkout. Payment success is intentionally rendered
 * only by booking-details.php after HMAC and database-state verification.
 */
$wptb_i18n = \MeTransfers\Booking\I18n::strings();
?>
<div id="wptb-plugin-container" class="wptb-iso mt-checkout">
    <ol class="mt-progress" aria-label="<?php echo esc_attr( $wptb_i18n['secure_payment'] ); ?>">
        <li class="is-complete">1</li>
        <li class="is-complete">2</li>
        <li class="is-complete">3</li>
        <li class="is-active" aria-current="step">4</li>
    </ol>

    <section id="wptb-payment-step" class="mt-checkout__step">
        <h1 class="mt-checkout__title"><?php echo esc_html( $wptb_i18n['secure_payment'] ); ?></h1>

        <div class="mt-checkout__layout">
            <aside class="mt-checkout__summary" aria-label="<?php echo esc_attr( $wptb_i18n['booking_summary'] ); ?>">
                <h2><?php echo esc_html( $wptb_i18n['booking_summary'] ); ?></h2>
                <dl>
                    <div><dt><?php echo esc_html( $wptb_i18n['vehicle'] ); ?></dt><dd id="payment-vehicle">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['type'] ); ?></dt><dd id="payment-trip-type">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['origin'] ); ?></dt><dd id="payment-origin">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['destination'] ); ?></dt><dd id="payment-destination">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['passengers'] ); ?></dt><dd id="payment-passengers">-</dd></div>
                    <div><dt><?php echo esc_html( $wptb_i18n['date'] ); ?></dt><dd id="payment-date">-</dd></div>
                    <div id="payment-original-row" class="mt-checkout__discount-row is-hidden"><dt><?php echo esc_html( $wptb_i18n['original_price'] ); ?></dt><dd id="payment-original-price">-</dd></div>
                    <div id="payment-discount-row" class="mt-checkout__discount-row is-hidden"><dt><?php echo esc_html( $wptb_i18n['discount'] ); ?></dt><dd id="payment-discount-val">-</dd></div>
                    <div class="mt-checkout__total"><dt><?php echo esc_html( $wptb_i18n['total'] ); ?></dt><dd id="payment-price">EUR 0.00</dd></div>
                </dl>
            </aside>

            <div class="mt-checkout__form-card">
                <div id="payment-message" class="mt-alert is-hidden" role="alert"></div>
                <form id="payment-form" action="" method="post">
                    <div id="map-canvas" class="mt-checkout__map" aria-label="<?php echo esc_attr( $wptb_i18n['trip_summary'] ); ?>"></div>
                    <img class="mt-checkout__payment-methods" src="<?php echo esc_url( MT_WPTB_URL . 'assets/images/49alternativo.png' ); ?>" alt="<?php echo esc_attr( $wptb_i18n['payment_methods_alt'] ); ?>">
                    <div id="payment-element" class="is-hidden"></div>

                    <div class="mt-terms" id="wptb-terms-wrapper">
                        <label for="wptb-accept-terms">
                            <input type="checkbox" id="wptb-accept-terms" name="accept_terms" value="1" required>
                            <span><?php echo esc_html( $wptb_i18n['terms_prefix'] ); ?> <a href="<?php echo esc_url( \MeTransfers\Booking\I18n::url( '/terminos-y-condiciones/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $wptb_i18n['terms_link'] ); ?></a> <?php echo esc_html( $wptb_i18n['terms_suffix'] ); ?> *</span>
                        </label>
                        <p id="wptb-terms-error" class="mt-alert mt-alert--error is-hidden" role="alert"><?php echo esc_html( $wptb_i18n['terms_required'] ); ?></p>
                    </div>

                    <div class="mt-actions">
                        <button type="button" class="mt-button mt-button--secondary" id="wptb-payment-back"><?php echo esc_html( $wptb_i18n['back'] ); ?></button>
                        <button type="submit" id="submit-payment" class="mt-button mt-button--primary">
                            <span id="button-text"><?php echo esc_html( $wptb_i18n['pay'] ); ?></span>
                            <span class="mt-spinner is-hidden" id="payment-spinner" aria-hidden="true"></span>
                        </button>
                    </div>
                    <p id="payment-info-header" class="mt-checkout__redirect-note"><?php echo esc_html( $wptb_i18n['payment_redirect'] ); ?></p>
                </form>
            </div>
        </div>
    </section>
</div>
