<?php
// Booking Modal Template - Complete Independent Flow
// Hidden by default, shown on click via JS
?>
<div id="wptb-booking-modal" class="wptb-modal-overlay" style="display: none !important;">
    <div class="wptb-modal-content wptb-modal-large">
        <button type="button" id="wptb-modal-close" class="wptb-modal-close">&times;</button>
        <h2 class="wptb-modal-title">Reserva tu Traslado</h2>
        <div class="wptb-modal-body">
            <!-- Step 1: Search Form -->
            <div id="wptb-modal-step-1" class="wptb-modal-step active">
                <?php 
                // Include dynamic form for split destination logic
                $form_suffix = '-modal';
                include WPTB_PLUGIN_DIR . 'templates/booking-form-dynamic.php'; 
                ?>
            </div>

            <!-- Step 2: Vehicle Selection (Inside Modal) -->
            <div id="wptb-modal-step-2" class="wptb-modal-step" style="display:none;">
                <div class="wptb-modal-step-header">
                    <button type="button" id="wptb-modal-back" class="wptb-back-btn">
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                        Volver
                    </button>
                    <h3>Selecciona tu Vehículo</h3>
                </div>
                
                <div class="trip-type-selector">
                    <button type="button" class="trip-type-btn-modal active" data-type="one_way">Solo Ida</button>
                    <button type="button" class="trip-type-btn-modal" data-type="round_trip">Ida y Vuelta</button>
                </div>

                <div id="wptb-modal-vehicles-grid" class="wptb-modal-vehicles-grid">
                    <!-- Vehicles will be loaded here as small buttons -->
                </div>
            </div>
        </div>
    </div>
</div>
