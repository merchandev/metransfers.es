<!-- Premium Transfers Modal - Mismo estilo que WPTB Modal -->
<div id="pts-booking-modal" class="wptb-modal-overlay" style="display: none !important;">
    <div class="wptb-modal-content wptb-modal-large">
        <button type="button" id="pts-modal-close" class="wptb-modal-close">×</button>
        <h2 class="wptb-modal-title">Reserva tu Traslado</h2>
        
        <div class="wptb-modal-body">
            <!-- Step 1: Search Form -->
            <div id="pts-modal-step-1" class="wptb-modal-step active">
                <div id="pts-step-1" class="booking-form active">
                    <form id="pts-search-form" action="javascript:void(0);" onsubmit="return false;">
                        <!-- Fecha -->
                        <div class="floating-label">
                            <input type="date" id="pts-date" name="transfer_date" required min="<?php echo date('Y-m-d'); ?>">
                            <label for="pts-date">Fecha</label>
                        </div>

                        <!-- Hora -->
                        <div class="floating-label">
                            <input type="time" id="pts-time" name="transfer_time" required>
                            <label for="pts-time">Hora</label>
                        </div>

                        <!-- Origen (siempre Barcelona) -->
                        <div class="floating-label wptb-origin-wrapper" style="position: relative;">
                            <input type="text" id="pts-origin" name="origin" placeholder=" " value="Barcelona, España" readonly autocomplete="off">
                            <label for="pts-origin">Origen</label>
                        </div>

                        <!-- Destination Region (Read-only, pre-filled) -->
                        <div class="floating-label">
                            <input type="text" id="pts-destination-display" name="destination_display" placeholder=" " readonly style="cursor: not-allowed;">
                            <label for="pts-destination-display">Destino (Región)</label>
                        </div>

                        <!-- Specific Address within Region -->
                        <div class="floating-label">
                            <input type="text" id="pts-destination-exact" name="destination_exact" placeholder="Ej: Calle Principal 123, Tossa de Mar" required autocomplete="off">
                            <label for="pts-destination-exact">Dirección Exacta en Destino</label>
                        </div>

                        <!-- Hidden field to store region context for backend -->
                        <input type="hidden" id="pts-region-context" name="region_context" value="">

                        <button type="submit" id="pts-submitBtn" onclick="event.preventDefault(); jQuery(this).closest('form').trigger('submit');" style="background: #004B68 !important; color: #fff !important; border: 2px solid #004B68 !important; border-radius: 24px !important; width: 100%; min-height: 55px; font-weight: 700; text-transform: uppercase;">Buscar Vehículos</button>
                    </form>
                </div>

                <!-- Step 2: Vehicle Selection (para futuro) -->
                <div id="pts-step-2" class="booking-vehicle-selection" style="display:none;">
                    <div class="progress-bar">
                        <div class="step completed">1</div>
                        <p>Búsqueda</p>
                        <div class="step active">2</div>
                        <p>Vehículo</p>
                        <div class="step">3</div>
                        <p>Detalles</p>
                        <div class="step">4</div>
                        <p>Confirmación</p>
                    </div>

                    <div class="trip-type-selector">
                        <button type="button" class="trip-type-btn active" data-type="one_way">Solo Ida</button>
                        <button type="button" class="trip-type-btn" data-type="round_trip">Ida y Vuelta</button>
                    </div>

                    <div id="pts-vehicles-grid" class="vehicles-grid">
                        <!-- Vehicles will be loaded via AJAX -->
                    </div>

                    <button type="button" id="pts-back-step2" class="secondary-btn" style="background: transparent !important; color: #004B68 !important; border: 2px solid #004B68 !important; border-radius: 24px !important; width: 100%; min-height: 55px; font-weight: 700; margin-top: 20px; text-transform: uppercase;">
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                        Cambiar busqueda
                    </button>
                </div>
            </div>

            <!-- Step 2: Vehicle Selection (Inside Modal) -->
            <div id="pts-modal-step-2" class="wptb-modal-step" style="display:none;">
                <div class="wptb-modal-step-header">
                    <button type="button" id="pts-modal-back" class="wptb-back-btn" style="background: transparent !important; color: #004B68 !important; border: 2px solid #004B68 !important; border-radius: 24px !important; min-height: 55px; font-weight: 700; padding: 0 20px; text-transform: uppercase;">
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                        Volver
                    </button>
                    <h3>Selecciona tu Vehículo</h3>
                </div>
                
                <div class="trip-type-selector">
                    <button type="button" class="trip-type-btn-pts active" data-type="one_way">Solo Ida</button>
                    <button type="button" class="trip-type-btn-pts" data-type="round_trip">Ida y Vuelta</button>
                </div>

                <div id="pts-modal-vehicles-grid" class="wptb-modal-vehicles-grid">
                    <!-- Vehicles will be loaded here as small buttons -->
                </div>
            </div>
        </div>
    </div>
</div>
