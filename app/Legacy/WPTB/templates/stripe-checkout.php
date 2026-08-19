<?php
/**
 * Template for Stripe Payment Checkout
 * Loaded via [wptb_stripe_checkout] shortcode
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
                        
                        <div id="payment-element" style="margin-bottom: 20px; text-align: center; color: #ccc;">
                            <p>Serás redirigido a la pasarela de pago segura del banco.</p>
                            <img src="https://sis.redsys.es/sis/imgs/sis_visa_mastercard.gif" alt="Pago Seguro" style="max-height: 50px;">
                        </div>
                        
                        <div class="form-actions" style="background: transparent !important; margin-top: 30px; display: flex; gap: 20px; flex-direction: row !important;">
                            <button type="button" class="btn-secondary" onclick="window.history.back()" style="background: transparent !important; color: #004B68 !important; border: 2px solid #004B68 !important; border-radius: 24px !important; flex: 1; min-height: 55px; font-weight: 700; text-transform: uppercase;">
                                <span>VOLVER</span>
                            </button>
                            <button type="submit" id="submit-payment" class="btn-primary" style="background: #004B68 !important; color: #fff !important; border: 2px solid #004B68 !important; border-radius: 24px !important; flex: 1; min-height: 55px; font-weight: 700; text-transform: uppercase;">
                                <span id="button-text">PAGAR <span id="button-amount">0.00</span></span>
                                <div class="spinner" id="payment-spinner" style="display:none;"></div>
                            </button>
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
                        <span class="d-label">Total Probado:</span>
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

.success-check-icon {
    width: 80px;
    height: 80px;
    background: #28a745;
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
}

.success-check-icon .dashicons {
    font-size: 50px;
    width: 50px;
    height: 50px;
    color: #fff;
}

.success-header h2 {
    color: #fff;
    font-size: 28px;
    margin: 0 0 10px;
    font-weight: 700;
}

.success-subtitle {
    color: #aaa;
    margin-bottom: 25px;
    font-size: 16px;
}

.success-details-grid {
    background: #2f2f2f;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    text-align: left;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 15px;
}

.d-label {
    color: #888;
}

.d-value {
    color: #eee;
    font-weight: 500;
    text-align: right;
    max-width: 60%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.d-value.highlight {
    color: #006597;
    font-weight: 700;
    font-size: 18px;
}

.separator {
    border: 0;
    border-top: 1px solid #444;
    margin: 15px 0;
}

.small-code {
    font-size: 11px;
    font-family: monospace;
    opacity: 0.7;
}

.total-row {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #444;
    align-items: center;
}

.total-price {
    color: #28a745;
    font-size: 24px;
    font-weight: 700;
}

.success-actions {
    display: flex;
    gap: 15px;
    flex-direction: column;
}

.btn-pdf {
    background: #333;
    color: #fff;
    border: 1px solid #444;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s;
}

.btn-pdf:hover {
    background: #444;
    border-color: #555;
    transform: translateY(-2px);
}

.btn-home {
    background: #006597;
    color: #fff;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: block;
    transition: all 0.3s;
}

.btn-home:hover {
    background: #e67e00;
    color: #fff;
    transform: translateY(-2px);
}

/* Spinner styling */
#payment-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    margin-left: 10px;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
