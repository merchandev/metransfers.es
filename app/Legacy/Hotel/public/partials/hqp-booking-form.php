<?php
/**
 * Specialized hotel booking form.
 *
 * Used by the [hqp_booking_form] shortcode.
 */

$hotel_name    = isset( $hotel_name ) ? $hotel_name : 'Hotel Partner';
$hotel_address = isset( $hotel_address ) ? $hotel_address : '';
$hotel_id      = isset( $hotel_id ) ? $hotel_id : 0;
?>

<div id="hqp-booking-wrapper" class="hqp-wrapper-dark">
    <div class="hqp-header-dark">
        <span class="hqp-eyebrow">Servicio privado de hotel</span>
        <h2>Reserva tu traslado</h2>
        <p class="hqp-subtitle">Servicio exclusivo desde <?php echo esc_html( $hotel_name ); ?></p>
    </div>

    <form id="hqp-booking-form" class="hqp-form-dark">
        <input type="hidden" id="hqp-hotel-id" name="hotel_id" value="<?php echo esc_attr( $hotel_id ); ?>">
        <input type="hidden" id="hqp-hotel-name" value="<?php echo esc_attr( $hotel_name ); ?>">
        <input type="hidden" id="hqp-hotel-address" value="<?php echo esc_attr( $hotel_address ); ?>">

        <div class="hqp-step" id="hqp-step-1">
            <div class="hqp-form-group">
                <label>Dirección del trayecto</label>
                <div class="hqp-radio-options-dark">
                    <label class="hqp-radio-dark active">
                        <input type="radio" name="route_direction" value="from_hotel" checked>
                        Desde el hotel
                    </label>
                    <label class="hqp-radio-dark">
                        <input type="radio" name="route_direction" value="to_hotel">
                        Hacia el hotel
                    </label>
                </div>
            </div>

            <div class="hqp-row">
                <div class="hqp-col">
                    <label for="hqp-origin" id="label-origin">Origen</label>
                    <div class="hqp-input-wrapper-dark">
                        <input type="text" id="hqp-origin" class="hqp-input-dark" value="<?php echo esc_attr( $hotel_address ); ?>" readonly>
                    </div>
                </div>

                <div class="hqp-col">
                    <label for="hqp-destination" id="label-destination">Destino</label>
                    <div class="hqp-input-wrapper-dark">
                        <select id="hqp-destination" class="hqp-input-dark">
                            <option value="" disabled selected>Selecciona destino...</option>
                            <option value="Aerop. Josep Tarradellas Barcelona-El Prat (BCN)">Aerop. Josep Tarradellas Barcelona-El Prat (BCN)</option>
                            <option value="Estación de Sants (Barcelona)">Estación de Sants (Barcelona)</option>
                            <option value="Puerto de Barcelona (Terminal Cruceros)">Puerto de Barcelona (Terminal Cruceros)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="hqp-form-group" style="margin-bottom: 20px;">
                <label>Tipo de Vehículo</label>
                <div class="hqp-radio-options-dark">
                    <label class="hqp-radio-dark active">
                        <input type="radio" name="vehicle_type" value="sedan" checked>
                        Sedán (1-3 pax)
                    </label>
                    <label class="hqp-radio-dark">
                        <input type="radio" name="vehicle_type" value="van">
                        Minivan (1-7 pax)
                    </label>
                </div>
            </div>

            <div class="hqp-row">
                <div class="hqp-col">
                    <label for="hqp-date">Fecha</label>
                    <div class="hqp-input-wrapper-dark">
                        <input type="date" id="hqp-date" class="hqp-input-dark" required min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                    </div>
                </div>
                <div class="hqp-col">
                    <label for="hqp-time">Hora</label>
                    <div class="hqp-input-wrapper-dark">
                        <input type="time" id="hqp-time" class="hqp-input-dark" required>
                    </div>
                </div>
            </div>

            <div class="hqp-row">
                <div class="hqp-col">
                    <label for="hqp-passengers">Pasajeros</label>
                    <div class="hqp-input-wrapper-dark">
                        <select id="hqp-passengers" class="hqp-input-dark">
                            <option value="1">1 persona</option>
                            <option value="2">2 personas</option>
                            <option value="3">3 personas</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="hqp-actions">
                <button type="submit" id="hqp-btn-calculate" class="hqp-btn-orange">
                    Ver precios
                </button>
            </div>
        </div>

        <div class="hqp-step" id="hqp-step-2" style="display:none;">
            <div class="hqp-back-link">
                <a href="#" id="hqp-back-to-step-1">← Volver a modificar</a>
            </div>

            <h3 class="hqp-section-title-dark">Vehículo seleccionado</h3>
            <div id="hqp-vehicles-grid" class="hqp-vehicles-grid">
                <div class="hqp-loading">Calculando tarifa...</div>
            </div>
        </div>

        <div class="hqp-step" id="hqp-step-3" style="display:none;">
            <div class="hqp-back-link">
                <a href="#" id="hqp-back-to-step-2">← Volver</a>
            </div>

            <h3 class="hqp-section-title-dark">Datos de contacto</h3>

            <div class="hqp-summary-card-dark">
                <h4>Resumen del viaje</h4>
                <p><strong>Ruta:</strong> <span id="summary-route">...</span></p>
                <p><strong>Fecha:</strong> <span id="summary-date">...</span></p>
                <p><strong>Vehículo:</strong> <span id="summary-vehicle">...</span></p>
                <p class="hqp-total-price-dark">Total: <span id="summary-price">...</span></p>
            </div>

            <div class="hqp-row">
                <div class="hqp-col">
                    <label for="hqp-name">Nombre completo</label>
                    <input type="text" name="customer_name" id="hqp-name" class="hqp-input-dark" required>
                </div>
                <div class="hqp-col">
                    <label for="hqp-email">Email</label>
                    <input type="email" name="customer_email" id="hqp-email" class="hqp-input-dark" required>
                </div>
            </div>

            <div class="hqp-row">
                <div class="hqp-col">
                    <label for="hqp-phone">Teléfono</label>
                    <input type="tel" name="customer_phone" id="hqp-phone" class="hqp-input-dark" required placeholder="+34 ...">
                </div>
                <div class="hqp-col">
                    <label for="hqp-flight">Número de vuelo</label>
                    <input type="text" name="flight_number" id="hqp-flight" class="hqp-input-dark" placeholder="Ej: VY1234">
                </div>
            </div>

            <div class="hqp-row">
                <div class="hqp-col">
                    <label for="hqp-notes">Notas adicionales</label>
                    <textarea name="notes" id="hqp-notes" class="hqp-input-dark" rows="2"></textarea>
                </div>
            </div>

            <div class="hqp-actions">
                <button type="button" id="hqp-btn-book" class="hqp-btn-orange">
                    Confirmar reserva
                </button>
            </div>
        </div>
    </form>
</div>
