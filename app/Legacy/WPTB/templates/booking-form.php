<?php
/**
 * Main booking form template.
 *
 * @var string $form_suffix Optional suffix used by modal instances.
 */

$suffix = isset( $form_suffix ) ? $form_suffix : '';
?>

<div id="wptb-plugin-container" class="wptb-iso">
    <div id="wptb-step-1<?php echo esc_attr( $suffix ); ?>" class="booking-form active wptb-panel wptb-search-panel">
        <form id="wptb-search-form<?php echo esc_attr( $suffix ); ?>" class="wptb-main-search-form" autocomplete="off">
            <input type="hidden" id="wptb-booking-source<?php echo esc_attr( $suffix ); ?>" name="booking_source" value="<?php echo esc_attr( isset($booking_source) ? $booking_source : 'Metransfers' ); ?>">
            <div class="floating-label wptb-main-search-field wptb-search-field-origin">
                <input type="text" id="wptb-origin<?php echo esc_attr( $suffix ); ?>" name="origin" placeholder=" " required autocomplete="off">
                <label for="wptb-origin<?php echo esc_attr( $suffix ); ?>">Origen</label>
            </div>

            <div class="floating-label wptb-main-search-field wptb-search-field-destination">
                <input type="text" id="wptb-destination<?php echo esc_attr( $suffix ); ?>" name="destination" placeholder=" " required autocomplete="off">
                <label for="wptb-destination<?php echo esc_attr( $suffix ); ?>">Destino</label>
            </div>

            <div class="floating-label wptb-main-search-field wptb-search-field-date">
                <input type="date" id="wptb-date<?php echo esc_attr( $suffix ); ?>" name="transfer_date" required min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                <label for="wptb-date<?php echo esc_attr( $suffix ); ?>">Fecha</label>
            </div>

            <div class="floating-label wptb-main-search-field wptb-search-field-time">
                <input type="time" id="wptb-time<?php echo esc_attr( $suffix ); ?>" name="transfer_time" required>
                <label for="wptb-time<?php echo esc_attr( $suffix ); ?>">Hora</label>
            </div>

            <button type="submit" id="submitBtn<?php echo esc_attr( $suffix ); ?>" class="wptb-main-search-submit" style="background: #004B68 !important; color: #fff !important; border: 2px solid #004B68 !important; border-radius: 24px !important; width: 100%; min-height: 55px; font-weight: 700; text-transform: uppercase;">
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

        <button type="button" id="wptb-back-step2" class="secondary-btn wptb-back-search-btn" style="background: transparent !important; color: #004B68 !important; border: 2px solid #004B68 !important; border-radius: 24px !important; width: 100%; min-height: 55px; font-weight: 700; margin-top: 20px; text-transform: uppercase;">Cambiar búsqueda</button>
    </div>

    <div id="wptb-payment-success" class="booking-success-dark" style="display:none;" data-nosnippet aria-hidden="true">
        <div class="success-card">
            <div class="success-header">
                <div class="success-check-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <h2>Reserva confirmada</h2>
                <p class="success-subtitle">Hemos recibido tu pago correctamente.</p>
            </div>

            <?php
            // Inject booking data if we are returning from payment
            if ( isset($_GET['payment_result']) && $_GET['payment_result'] === 'ok' && isset($_GET['oid']) ) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'wptb_bookings';
                $order_id = sanitize_text_field( $_GET['oid'] );
                $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE payment_intent_id = %s", $order_id ) );
                
                if ( $booking ) {
                    $vehicle_table = $wpdb->prefix . 'wptb_vehicles';
                    $vehicle_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $vehicle_table WHERE id = %d", $booking->vehicle_id ) );
                    if ( !$vehicle_name ) $vehicle_name = 'Vehículo Asignado';
                    ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('success-booking-id').textContent = '#<?php echo esc_js($booking->id); ?>';
                            document.getElementById('success-payment-id').textContent = '<?php echo esc_js($booking->payment_intent_id); ?>';
                            document.getElementById('success-vehicle').textContent = '<?php echo esc_js($vehicle_name); ?>';
                            document.getElementById('success-date').textContent = '<?php echo esc_js($booking->booking_date . ' ' . $booking->booking_time); ?>';
                            document.getElementById('success-origin').textContent = '<?php echo esc_js($booking->origin); ?>';
                            document.getElementById('success-destination').textContent = '<?php echo esc_js($booking->destination); ?>';
                            document.getElementById('success-price').textContent = '€<?php echo esc_js($booking->price); ?>';
                            document.getElementById('wptb-payment-success').style.display = 'block';
                            
                            // Hide other steps
                            var steps = document.querySelectorAll('.booking-step');
                            for(var i=0; i<steps.length; i++) { steps[i].style.display = 'none'; }
                        });
                    </script>
                    <?php
                }
            }
            ?>
            <div class="success-details-grid">
                <div class="detail-row">
                    <span class="d-label">Referencia:</span>
                    <span class="d-value highlight" id="success-booking-id">#---</span>
                </div>
                <div class="detail-row">
                    <span class="d-label">ID pago:</span>
                    <span class="d-value small-code" id="success-payment-id">---</span>
                </div>
                <hr class="separator">
                <div class="detail-row">
                    <span class="d-label">Vehículo:</span>
                    <span class="d-value" id="success-vehicle">---</span>
                </div>
                <div class="detail-row">
                    <span class="d-label">Fecha:</span>
                    <span class="d-value" id="success-date">---</span>
                </div>
                <div class="detail-row">
                    <span class="d-label">Origen:</span>
                    <span class="d-value" id="success-origin">---</span>
                </div>
                <div class="detail-row">
                    <span class="d-label">Destino:</span>
                    <span class="d-value" id="success-destination">---</span>
                </div>
                <div class="detail-row total-row">
                    <span class="d-label">Total pagado:</span>
                    <span class="d-value total-price" id="success-price">---</span>
                </div>
            </div>

            <div class="success-actions">
                <button id="btn-download-pdf" class="btn-pdf">
                    <span class="dashicons dashicons-pdf"></span> Descargar recibo
                </button>
                <a href="/" class="btn-home">Volver al inicio</a>
            </div>
        </div>
    </div>
</div>
