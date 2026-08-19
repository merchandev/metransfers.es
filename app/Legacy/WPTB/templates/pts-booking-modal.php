<?php $wptb_i18n = \MeTransfers\Booking\I18n::strings(); ?>
<!-- Premium Transfers Modal - Mismo estilo que WPTB Modal -->
<div id="pts-booking-modal" class="wptb-modal-overlay is-hidden">
    <div class="wptb-modal-content wptb-modal-large">
        <button type="button" id="pts-modal-close" class="wptb-modal-close">×</button>
        <h2 class="wptb-modal-title"><?php echo esc_html( $wptb_i18n['book_transfer'] ); ?></h2>
        
        <div class="wptb-modal-body">
            <!-- Step 1: Search Form -->
            <div id="pts-modal-step-1" class="wptb-modal-step active">
                <div id="pts-step-1" class="booking-form active">
                    <form id="pts-search-form" action="javascript:void(0);" onsubmit="return false;">
                        <!-- Fecha -->
                        <div class="floating-label">
                            <input type="date" id="pts-date" name="transfer_date" required min="<?php echo date('Y-m-d'); ?>">
                            <label for="pts-date"><?php echo esc_html( $wptb_i18n['date'] ); ?></label>
                        </div>

                        <!-- Hora -->
                        <div class="floating-label">
                            <input type="time" id="pts-time" name="transfer_time" required>
                            <label for="pts-time"><?php echo esc_html( $wptb_i18n['time'] ); ?></label>
                        </div>

                        <!-- Origen (siempre Barcelona) -->
                        <div class="floating-label wptb-origin-wrapper">
                            <input type="text" id="pts-origin" name="origin" placeholder=" " value="Barcelona, España" readonly autocomplete="off">
                            <label for="pts-origin"><?php echo esc_html( $wptb_i18n['origin'] ); ?></label>
                        </div>

                        <!-- Destination Region (Read-only, pre-filled) -->
                        <div class="floating-label">
                            <input type="text" id="pts-destination-display" class="is-readonly" name="destination_display" placeholder=" " readonly>
                            <label for="pts-destination-display"><?php echo esc_html( $wptb_i18n['destination_region'] ); ?></label>
                        </div>

                        <!-- Specific Address within Region -->
                        <div class="floating-label">
                            <input type="text" id="pts-destination-exact" name="destination_exact" placeholder="<?php echo esc_attr( $wptb_i18n['exact_address_placeholder'] ); ?>" required autocomplete="off">
                            <label for="pts-destination-exact"><?php echo esc_html( $wptb_i18n['exact_address'] ); ?></label>
                        </div>

                        <!-- Hidden field to store region context for backend -->
                        <input type="hidden" id="pts-region-context" name="region_context" value="">

                        <button type="submit" id="pts-submitBtn" class="mt-button mt-button--primary"><?php echo esc_html( $wptb_i18n['search_vehicles'] ); ?></button>
                    </form>
                </div>

                <!-- Step 2: Vehicle Selection (para futuro) -->
                <div id="pts-step-2" class="booking-vehicle-selection is-hidden">
                    <div class="progress-bar">
                        <div class="step completed">1</div>
                        <p><?php echo esc_html( $wptb_i18n['search'] ); ?></p>
                        <div class="step active">2</div>
                        <p><?php echo esc_html( $wptb_i18n['vehicle'] ); ?></p>
                        <div class="step">3</div>
                        <p><?php echo esc_html( $wptb_i18n['details'] ); ?></p>
                        <div class="step">4</div>
                        <p><?php echo esc_html( $wptb_i18n['confirmation'] ); ?></p>
                    </div>

                    <div class="trip-type-selector">
                        <button type="button" class="trip-type-btn active" data-type="one_way"><?php echo esc_html( $wptb_i18n['one_way'] ); ?></button>
                        <button type="button" class="trip-type-btn" data-type="round_trip"><?php echo esc_html( $wptb_i18n['round_trip'] ); ?></button>
                    </div>

                    <div id="pts-vehicles-grid" class="vehicles-grid">
                        <!-- Vehicles will be loaded via AJAX -->
                    </div>

                    <button type="button" id="pts-back-step2" class="secondary-btn mt-button mt-button--secondary">
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                        <?php echo esc_html( $wptb_i18n['change_search'] ); ?>
                    </button>
                </div>
            </div>

            <!-- Step 2: Vehicle Selection (Inside Modal) -->
            <div id="pts-modal-step-2" class="wptb-modal-step is-hidden">
                <div class="wptb-modal-step-header">
                    <button type="button" id="pts-modal-back" class="wptb-back-btn mt-button mt-button--secondary">
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                        <?php echo esc_html( $wptb_i18n['back'] ); ?>
                    </button>
                    <h3><?php echo esc_html( $wptb_i18n['select_vehicle'] ); ?></h3>
                </div>
                
                <div class="trip-type-selector">
                    <button type="button" class="trip-type-btn-pts active" data-type="one_way"><?php echo esc_html( $wptb_i18n['one_way'] ); ?></button>
                    <button type="button" class="trip-type-btn-pts" data-type="round_trip"><?php echo esc_html( $wptb_i18n['round_trip'] ); ?></button>
                </div>

                <div id="pts-modal-vehicles-grid" class="wptb-modal-vehicles-grid">
                    <!-- Vehicles will be loaded here as small buttons -->
                </div>
            </div>
        </div>
    </div>
</div>
