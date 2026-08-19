<?php
/**
 * Dynamic booking form template.
 *
 * Used by modal/search flows with destination region context.
 *
 * @var string $form_suffix Optional suffix used by modal instances.
 */

$suffix = isset( $form_suffix ) ? $form_suffix : '';
?>

<div id="wptb-step-1<?php echo esc_attr( $suffix ); ?>" class="booking-form active wptb-panel wptb-search-panel wptb-dynamic-search-panel">
        <form id="wptb-search-form<?php echo esc_attr( $suffix ); ?>" class="wptb-main-search-form wptb-dynamic-search-form" autocomplete="off" action="javascript:void(0);" onsubmit="return false;">
        <div class="floating-label wptb-main-search-field wptb-search-field-date">
            <input type="date" id="wptb-date<?php echo esc_attr( $suffix ); ?>" name="transfer_date" required min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
            <label for="wptb-date<?php echo esc_attr( $suffix ); ?>">Fecha</label>
        </div>

        <div class="floating-label wptb-main-search-field wptb-search-field-time">
            <input type="time" id="wptb-time<?php echo esc_attr( $suffix ); ?>" name="transfer_time" required>
            <label for="wptb-time<?php echo esc_attr( $suffix ); ?>">Hora</label>
        </div>

        <div class="floating-label wptb-main-search-field wptb-search-field-origin">
            <input type="text" id="wptb-origin<?php echo esc_attr( $suffix ); ?>" name="origin" placeholder=" " required autocomplete="off">
            <label for="wptb-origin<?php echo esc_attr( $suffix ); ?>">Origen</label>
        </div>

        <div class="floating-label wptb-main-search-field wptb-search-field-region">
            <input type="text" id="wptb-destination-display<?php echo esc_attr( $suffix ); ?>" name="destination_display" placeholder=" " readonly>
            <label for="wptb-destination-display<?php echo esc_attr( $suffix ); ?>">Destino región</label>
        </div>

        <div class="floating-label wptb-main-search-field wptb-search-field-destination">
            <input type="text" id="wptb-destination<?php echo esc_attr( $suffix ); ?>" name="destination_exact" placeholder=" " required autocomplete="off">
            <label for="wptb-destination<?php echo esc_attr( $suffix ); ?>">Dirección exacta</label>
        </div>

        <input type="hidden" id="wptb-region-context<?php echo esc_attr( $suffix ); ?>" name="region_context" value="">

        <button type="submit" id="submitBtn<?php echo esc_attr( $suffix ); ?>" onclick="event.preventDefault(); jQuery(this).closest('form').trigger('submit');" class="wptb-main-search-submit" style="background: #004B68 !important; color: #fff !important; border: 2px solid #004B68 !important; border-radius: 24px !important; width: 100%; min-height: 55px; font-weight: 700; text-transform: uppercase;">
            Buscar vehículos
        </button>
    </form>
</div>

<div id="wptb-step-2" class="booking-vehicle-selection wptb-panel wptb-vehicle-panel" style="display:none;">
    <div class="progress-bar wptb-progress">
        <div class="wptb-progress-line" aria-hidden="true"></div>
        <div class="progress-step"><div class="step completed">1</div></div>
        <div class="progress-step"><div class="step active">2</div></div>
        <div class="progress-step"><div class="step">3</div></div>
        <div class="progress-step"><div class="step">4</div></div>
    </div>

    <div class="trip-type-selector">
        <button type="button" class="trip-type-btn active" data-type="one_way">Solo ida</button>
        <button type="button" class="trip-type-btn" data-type="round_trip">Ida y vuelta</button>
    </div>

    <div id="vehicles-grid" class="vehicles-grid wptb-vehicle-grid">
        <!-- Vehicles will be loaded via AJAX -->
    </div>

    <button type="button" id="wptb-back-step2<?php echo esc_attr( $suffix ); ?>" class="secondary-btn wptb-back-search-btn" style="background: transparent !important; color: #004B68 !important; border: 2px solid #004B68 !important; border-radius: 24px !important; width: 100%; min-height: 55px; font-weight: 700; margin-top: 20px; text-transform: uppercase;">Cambiar búsqueda</button>
</div>
