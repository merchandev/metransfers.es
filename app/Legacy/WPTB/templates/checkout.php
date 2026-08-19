<?php
/**
 * Template for Checkout
 * Loaded via [wptb_checkout] shortcode
 */
?>
<div id="wptb-plugin-container" class="wptb-iso">
    
    <!-- PROGRESS BAR -->
    <div class="progress-bar-container">
        <div class="progress-bar">
            <div class="progress-step">
                <div class="step completed">1</div>
            </div>
            <div class="progress-step">
                <div class="step completed">2</div>
            </div>
            <div class="progress-step">
                <div class="step completed">3</div>
            </div>
            <div class="progress-step">
                <div class="step active">4</div>
            </div>
        </div>
    </div>

    <!-- Force Styles -->
    <style>
        #wptb-plugin-container .wptb-terms-text {
            color: #333 !important;
        }

        #wptb-plugin-container .wptb-terms-link {
            color: #004B68 !important;
        }

        #wptb-plugin-container .wptb-required-star {
            color: #FFD700 !important;
        }
    </style>

    <div class="booking-details" style="display: block; width: 100% !important; max-width: 1240px !important; margin: 46px auto 0 !important;">
        
        <!-- PAYMENT STEP -->
        <div id="wptb-payment-step" class="booking-step">
            <h2 style="color: #004B68 !important; margin-bottom: 20px; font-weight: 800; text-transform: uppercase;">💳 Pago Seguro</h2>
            
            <div class="booking-layout-wrapper">
                <!-- SUMMARY SIDEBAR -->
                <div class="summary-sidebar">
                    <div class="contact-summary sticky-summary" style="background-color: #003f59 !important; border: 1px solid #004B68 !important; border-radius: 24px !important; padding: 25px !important;">
                        <h3 style="margin-top:0; color:#ffffff !important; text-transform: uppercase;">Resumen de tu Reserva</h3>
                        <p style="color: #fff !important;"><strong>Vehículo:</strong> <span id="payment-vehicle" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff !important;"><strong>Tipo:</strong> <span id="payment-trip-type" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff !important;"><strong>Origen:</strong> <span id="payment-origin" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff !important;"><strong>Destino:</strong> <span id="payment-destination" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff !important;"><strong>Pasajeros:</strong> <span id="payment-passengers" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff !important;"><strong>Fecha:</strong> <span id="payment-date" style="color: #FFD700;">-</span></p>
                        <p id="payment-original-row" style="display:none; color:#999 !important; text-decoration:line-through; font-size: 0.9em;"><strong>Precio Original:</strong> <span id="payment-original-price">-</span></p>
                        <p id="payment-discount-row" style="display:none; color:#27ae60 !important;"><strong>Descuento:</strong> <span id="payment-discount-val">-</span></p>
                        <hr style="margin: 15px 0; border: none; border-top: 1px solid #004B68;">
                        <h2 style="margin-bottom:0; margin-top:10px; color:#ffffff !important;">Total: <span id="payment-price" style="color:#FFD700;">EUR 0.00</span></h2>
                    </div>
                </div>
                
                <!-- PAYMENT FORM -->
                <div class="form-content">
                    <div id="payment-message" class="payment-message" style="display:none;"></div>
                    
                    <form id="payment-form" action="javascript:void(0);" onsubmit="return false;">
                        <!-- MAP CONTAINER -->
                        <div id="map-canvas" style="width: 100%; height: 300px; background: #f0f0f0; margin-bottom: 25px; border-radius: 8px; border: 1px solid #ddd;"></div>
                        
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="<?php echo WPTB_PLUGIN_URL . 'assets/images/49alternativo.png'; ?>" alt="Métodos de Pago" style="max-height: 50px;">
                        </div>

                        <div id="payment-element" style="display:none;"></div>

                        <!-- TÉRMINOS Y CONDICIONES -->
                        <div class="wptb-terms-wrapper" id="wptb-terms-wrapper">
                            <label class="wptb-terms-label" for="wptb-accept-terms">
                                <input type="checkbox" id="wptb-accept-terms" name="accept_terms">
                                <span class="wptb-terms-checkmark"></span>
                                <span class="wptb-terms-text">
                                    He leído los <a href="/terminos-y-condiciones/" target="_blank" class="wptb-terms-link">Términos y Condiciones</a> de la web y estoy de acuerdo en continuar <span class="wptb-required-star">*</span>
                                </span>
                            </label>
                            <p id="wptb-terms-error" class="wptb-terms-error" style="display:none;">⚠ Debes aceptar los Términos y Condiciones para continuar.</p>
                        </div>

                        <div class="form-actions" style="background: transparent !important; margin-top: 30px; display: flex; gap: 20px; flex-direction: row !important;">
                            <button type="button" class="btn-secondary" onclick="window.history.back()" style="background: transparent !important; color: #004B68 !important; border: 2px solid #004B68 !important; border-radius: 24px !important; flex: 1; min-height: 55px; font-weight: 700; text-transform: uppercase;">
                                <span>VOLVER</span>
                            </button>
                            <button type="submit" id="submit-payment" class="btn-primary" style="background: #004B68 !important; color: #fff !important; border: 2px solid #004B68 !important; border-radius: 24px !important; flex: 1; min-height: 55px; font-weight: 700; text-transform: uppercase;">
                                <span id="button-text">PAGAR</span>
                                <div class="spinner" id="payment-spinner" style="display:none;"></div>
                            </button>
                        </div>

                        <div id="payment-info-header" style="margin-top: 25px; text-align: center;">
                            <h3 style="color: #FFD700; font-weight: bold; margin: 0; font-size: 13px;">Serás redirigido a la pasarela de pago segura del banco.</h3>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SUCCESS STEP -->
        <div id="wptb-payment-success" class="booking-success-dark" style="display: none;">
            <div class="success-card">
                <div class="success-header">
                    <div class="success-check-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <h2>¡Reserva Confirmada!</h2>
                    <p class="success-subtitle">Hemos recibido tu pago correctamente.</p>
                </div>

                <div class="success-details-grid">
                    <div class="detail-row">
                        <span class="d-label">Referencia:</span>
                        <span class="d-value highlight" id="success-booking-id">#---</span>
                    </div>
                    <div class="detail-row">
                        <span class="d-label">ID Pago:</span>
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
                        <span class="d-label">Total Pagado:</span>
                        <span class="d-value total-price" id="success-price">---</span>
                    </div>
                </div>

                <div class="success-actions">
                    <button id="btn-download-pdf" class="btn-pdf">
                        <span class="dashicons dashicons-pdf"></span> Descargar Recibo
                    </button>
                    <a href="/" class="btn-home">Volver al Inicio</a>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* New Dark Success Theme */
.booking-success-dark {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 20px;
    background: #1a1a1a; /* Dark Background */
    border-radius: 12px;
    color: #fff;
    min-height: 400px;
}

.success-card {
    background: #252525;
    width: 100%;
    max-width: 500px;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    text-align: center;
    border: 1px solid #333;
}

.success-header {
    margin-bottom: 25px;
}

.success-check-icon {
    width: 60px;
    height: 60px;
    background: #27ae60;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
}

.success-check-icon .dashicons {
    color: #fff;
    font-size: 36px;
    width: 36px;
    height: 36px;
}

.success-header h2 {
    color: #fff;
    font-size: 24px;
    margin: 0 0 5px 0;
}

.success-subtitle {
    color: #aaa;
    font-size: 14px;
    margin: 0;
}

.success-details-grid {
    background: #1e1e1e;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    text-align: left;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.d-label {
    color: #888;
}

.d-value {
    color: #fff;
    font-weight: 500;
}

.d-value.highlight {
    color: #FFD700;
    font-weight: bold;
}

.d-value.small-code {
    font-family: monospace;
    color: #bbb;
}

.separator {
    border: 0;
    border-top: 1px solid #333;
    margin: 15px 0;
}

.total-row {
    font-size: 16px;
    margin-top: 5px;
}

.total-row .d-value {
    color: #27ae60;
    font-weight: bold;
    font-size: 18px;
}

.success-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.btn-pdf, .btn-home {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-pdf {
    background: #0056b3;
    color: #fff;
    border: none;
}

.btn-pdf:hover {
    background: #004494;
}

.btn-home {
    background: transparent;
    color: #ccc;
    border: 1px solid #555;
}

.btn-home:hover {
    background: #333;
    color: #fff;
    text-decoration: none;
}

@media (max-width: 480px) {
    .booking-success-dark {
        padding: 20px 10px;
    }
    .success-card {
        padding: 20px;
    }
}
</style>
