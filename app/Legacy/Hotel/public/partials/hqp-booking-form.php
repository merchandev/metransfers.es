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
        <p class="hqp-subtitle">Tarifa exclusiva para huéspedes de <?php echo esc_html( $hotel_name ); ?></p>
    </div>

    <div class="hqp-form-surface">
        <div class="hqp-progress" aria-label="Progreso de la reserva">
            <span class="hqp-progress-item is-active" data-step-indicator="1"><b>1</b> Trayecto</span>
            <span class="hqp-progress-separator" aria-hidden="true"></span>
            <span class="hqp-progress-item" data-step-indicator="2"><b>2</b> Vehículo</span>
            <span class="hqp-progress-separator" aria-hidden="true"></span>
            <span class="hqp-progress-item" data-step-indicator="3"><b>3</b> Datos</span>
        </div>

        <form id="hqp-booking-form" class="hqp-form-dark" novalidate>
            <input type="hidden" id="hqp-hotel-id" name="hotel_id" value="<?php echo esc_attr( $hotel_id ); ?>">
            <input type="hidden" id="hqp-hotel-name" value="<?php echo esc_attr( $hotel_name ); ?>">
            <input type="hidden" id="hqp-hotel-address" value="<?php echo esc_attr( $hotel_address ); ?>">

            <div class="hqp-step" id="hqp-step-1">
                <div class="hqp-step-heading">
                    <h3>¿A dónde vas?</h3>
                    <p>Selecciona el sentido del trayecto, destino, fecha y número de pasajeros.</p>
                </div>

                <div class="hqp-form-group">
                    <label>Dirección del trayecto</label>
                    <div class="hqp-radio-options-dark" role="radiogroup" aria-label="Dirección del trayecto">
                        <label class="hqp-radio-dark active">
                            <input type="radio" name="route_direction" value="from_hotel" checked>
                            <span>Desde el hotel</span>
                        </label>
                        <label class="hqp-radio-dark">
                            <input type="radio" name="route_direction" value="to_hotel">
                            <span>Hacia el hotel</span>
                        </label>
                    </div>
                </div>

                <div class="hqp-row">
                    <div class="hqp-col">
                        <label for="hqp-origin" id="label-origin">Origen · Hotel</label>
                        <div class="hqp-input-wrapper-dark">
                            <input type="text" id="hqp-origin" class="hqp-input-dark" value="<?php echo esc_attr( $hotel_address ); ?>" readonly>
                        </div>
                    </div>

                    <div class="hqp-col">
                        <label for="hqp-destination" id="label-destination">Destino</label>
                        <div class="hqp-input-wrapper-dark">
                            <select id="hqp-destination" class="hqp-input-dark" required>
                                <option value="" disabled selected>Selecciona una ubicación</option>
                                <?php foreach ( (array) $route_locations as $location_id => $location ) : ?>
                                    <option value="<?php echo esc_attr( $location_id ); ?>"><?php echo esc_html( $location['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="hqp-row hqp-row-three">
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
                    <div class="hqp-col">
                        <label for="hqp-passengers">Pasajeros</label>
                        <div class="hqp-input-wrapper-dark">
                            <select id="hqp-passengers" class="hqp-input-dark" required>
                                <?php for ( $pax = 1; $pax <= 7; $pax++ ) : ?>
                                    <option value="<?php echo esc_attr( $pax ); ?>"><?php echo esc_html( $pax . ( 1 === $pax ? ' persona' : ' personas' ) ); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="hqp-actions">
                    <button type="submit" id="hqp-btn-calculate" class="hqp-btn-orange">
                        Ver vehículos y tarifas
                    </button>
                    <p class="hqp-action-help">Solo mostraremos vehículos activos con tarifa configurada para este hotel.</p>
                </div>
            </div>

            <div class="hqp-step" id="hqp-step-2" style="display:none;">
                <div class="hqp-back-link">
                    <a href="#" id="hqp-back-to-step-1">← Cambiar trayecto</a>
                </div>

                <div class="hqp-step-heading">
                    <h3>Elige tu vehículo</h3>
                    <p>Selecciona una opción disponible para el número de pasajeros indicado.</p>
                </div>

                <div id="hqp-vehicles-grid" class="hqp-vehicles-grid" aria-live="polite">
                    <div class="hqp-loading"><span class="hqp-spinner" aria-hidden="true"></span> Consultando vehículos y tarifas…</div>
                </div>
            </div>

            <div class="hqp-step" id="hqp-step-3" style="display:none;">
                <div class="hqp-back-link">
                    <a href="#" id="hqp-back-to-step-2">← Elegir otro vehículo</a>
                </div>

                <div class="hqp-step-heading">
                    <h3>Completa tus datos</h3>
                    <p>Revisa el viaje y añade los datos necesarios para confirmar la reserva.</p>
                </div>

                <div class="hqp-summary-card-dark">
                    <h4>Resumen del viaje</h4>
                    <p><strong>Ruta</strong> <span id="summary-route">—</span></p>
                    <p><strong>Fecha</strong> <span id="summary-date">—</span></p>
                    <p><strong>Vehículo</strong> <span id="summary-vehicle">—</span></p>
                    <p class="hqp-total-price-dark"><strong>Total</strong> <span id="summary-price">—</span></p>
                </div>

                <div class="hqp-row">
                    <div class="hqp-col">
                        <label for="hqp-name">Nombre completo</label>
                        <input type="text" name="customer_name" id="hqp-name" class="hqp-input-dark" required autocomplete="name" placeholder="Nombre y apellido">
                    </div>
                    <div class="hqp-col">
                        <label for="hqp-email">Email</label>
                        <input type="email" name="customer_email" id="hqp-email" class="hqp-input-dark" required autocomplete="email" placeholder="nombre@correo.com">
                    </div>
                </div>

                <div class="hqp-row">
                    <div class="hqp-col">
                        <label for="hqp-phone">Teléfono</label>
                        <input type="tel" name="customer_phone" id="hqp-phone" class="hqp-input-dark" required autocomplete="tel" placeholder="+34 600 000 000">
                    </div>
                    <div class="hqp-col">
                        <label for="hqp-flight">Número de vuelo <span class="hqp-optional">Opcional</span></label>
                        <input type="text" name="flight_number" id="hqp-flight" class="hqp-input-dark" placeholder="Ej. VY1234">
                    </div>
                </div>

                <div class="hqp-row hqp-row-single">
                    <div class="hqp-col">
                        <label for="hqp-notes">Notas adicionales <span class="hqp-optional">Opcional</span></label>
                        <textarea name="notes" id="hqp-notes" class="hqp-input-dark" rows="3" placeholder="Equipaje especial, silla infantil u otra información útil"></textarea>
                    </div>
                </div>

                <div class="hqp-actions">
                    <button type="button" id="hqp-btn-book" class="hqp-btn-orange">
                        Confirmar reserva y pagar
                    </button>
                    <p class="hqp-action-help">El precio mostrado se valida nuevamente en el servidor antes de iniciar el pago.</p>
                </div>
            </div>
        </form>
    </div>
</div>
