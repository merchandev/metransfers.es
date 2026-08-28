<?php
/**
 * Main booking form template.
 *
 * @var string $form_suffix Optional suffix used by modal instances.
 */

$suffix = isset( $form_suffix ) ? $form_suffix : '';
$wptb_i18n = \MeTransfers\Booking\I18n::strings();
?>

<div id="wptb-plugin-container" class="wptb-iso mt-booking mt-booking--search">
    <div id="wptb-step-1<?php echo esc_attr( $suffix ); ?>" class="booking-form active wptb-panel wptb-search-panel">
        <form id="wptb-search-form<?php echo esc_attr( $suffix ); ?>" class="wptb-main-search-form" autocomplete="off">
            <input type="hidden" id="wptb-booking-source<?php echo esc_attr( $suffix ); ?>" name="booking_source" value="<?php echo esc_attr( isset($booking_source) ? $booking_source : 'Metransfers' ); ?>">
            <div class="floating-label wptb-main-search-field wptb-search-field-origin">
                <input type="text" id="wptb-origin<?php echo esc_attr( $suffix ); ?>" name="origin" placeholder=" " required autocomplete="off">
                <label for="wptb-origin<?php echo esc_attr( $suffix ); ?>"><?php echo esc_html( $wptb_i18n['origin'] ); ?></label>
            </div>

            <div class="floating-label wptb-main-search-field wptb-search-field-destination">
                <input type="text" id="wptb-destination<?php echo esc_attr( $suffix ); ?>" name="destination" placeholder=" " required autocomplete="off">
                <label for="wptb-destination<?php echo esc_attr( $suffix ); ?>"><?php echo esc_html( $wptb_i18n['destination'] ); ?></label>
            </div>

            <div class="floating-label wptb-main-search-field wptb-search-field-date">
                <input type="date" id="wptb-date<?php echo esc_attr( $suffix ); ?>" name="transfer_date" required min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                <label for="wptb-date<?php echo esc_attr( $suffix ); ?>"><?php echo esc_html( $wptb_i18n['date'] ); ?></label>
            </div>

            <div class="floating-label wptb-main-search-field wptb-search-field-time">
                <input type="time" id="wptb-time<?php echo esc_attr( $suffix ); ?>" name="transfer_time" required>
                <label for="wptb-time<?php echo esc_attr( $suffix ); ?>"><?php echo esc_html( $wptb_i18n['time'] ); ?></label>
            </div>

            <button type="submit" id="submitBtn<?php echo esc_attr( $suffix ); ?>" class="wptb-main-search-submit mt-button mt-button--primary">
                <?php echo esc_html( $wptb_i18n['search_vehicles'] ); ?>
            </button>
        </form>
    </div>

    <div id="wptb-step-2" class="booking-vehicle-selection wptb-panel wptb-vehicle-panel is-hidden">
        <div class="progress-bar wptb-progress">
            <div class="wptb-progress-line" aria-hidden="true"></div>
            <div class="progress-step"><div class="step completed">1</div></div>
            <div class="progress-step"><div class="step active">2</div></div>
            <div class="progress-step"><div class="step">3</div></div>
            <div class="progress-step"><div class="step">4</div></div>
        </div>

        <div class="trip-type-selector">
            <button type="button" class="trip-type-btn active" data-type="one_way"><?php echo esc_html( $wptb_i18n['one_way'] ); ?></button>
            <button type="button" class="trip-type-btn" data-type="round_trip"><?php echo esc_html( $wptb_i18n['round_trip'] ); ?></button>
        </div>

        <div id="vehicles-grid" class="vehicles-grid wptb-vehicle-grid">
            <!-- Vehicles will be loaded via AJAX -->
        </div>

        <button type="button" id="wptb-back-step2" class="btn btn-outline wptb-back-search-btn" style="margin-top: 24px; display: flex; margin-inline: auto;"><?php echo esc_html( $wptb_i18n['change_search'] ); ?></button>
    </div>

</div>

