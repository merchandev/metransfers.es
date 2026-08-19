<?php
/**
 * Vehicle selection page template.
 */
?>

<div id="wptb-plugin-container" class="wptb-iso">
    <div id="wptb-vehicle-selection-page" class="wptb-vehicle-page-shell">
        <div id="wptb-step-2" class="booking-vehicle-selection wptb-vehicle-page-step wptb-panel wptb-vehicle-panel">
            <div class="progress-bar wptb-progress">
                <div class="wptb-progress-line" aria-hidden="true"></div>
                <div class="progress-step"><div class="step completed">1</div></div>
                <div class="progress-step"><div class="step active">2</div></div>
                <div class="progress-step"><div class="step">3</div></div>
                <div class="progress-step"><div class="step">4</div></div>
            </div>

            <div id="wptb-vehicle-search-summary" class="wptb-vehicle-search-summary">
                <div class="wptb-vehicle-search-kicker">Selección de vehículo</div>
                <div id="wptb-vehicle-summary-route" class="wptb-vehicle-summary-route">Cargando ruta...</div>
            </div>

            <div class="trip-type-selector wptb-vehicle-trip-toggle">
                <button type="button" class="trip-type-btn active" data-type="one_way">Solo ida</button>
                <button type="button" class="trip-type-btn" data-type="round_trip">Ida y vuelta</button>
            </div>

            <div id="vehicles-grid" class="vehicles-grid wptb-vehicle-grid">
                <div class="loading-spinner">Buscando vehículos...</div>
            </div>

            <button type="button" id="wptb-back-step2" class="secondary-btn wptb-back-search-btn">Cambiar búsqueda</button>
        </div>
    </div>
</div>
