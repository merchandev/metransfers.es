<?php
/**
 * Template for Booking Details & Confirmation Page
 * Steps 3 & 4
 */
?>
<div id="wptb-plugin-container" class="wptb-iso" style="margin-top: 28px;">
    
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
                <div class="step active">3</div>
            </div>
            <div class="progress-step">
                <div class="step">4</div>
            </div>
        </div>
    </div>

    <!-- Force Styles -->
    <style>
        /* Force Orange Calendar/Clock Indicators for Return Fields */
        #wptb-return-date::-webkit-calendar-picker-indicator,
        #wptb-return-time::-webkit-calendar-picker-indicator {
            filter: invert(19%) sepia(88%) saturate(1472%) hue-rotate(180deg) brightness(97%) contrast(106%) !important;
            opacity: 1 !important;
            cursor: pointer;
            display: block !important;
        }

        /* Fix Chrome Autofill on Dark Background */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #003A52 inset !important;
            -webkit-text-fill-color: #fff !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Ensure inputs and textareas have consistent styling */
        #wptb-plugin-container input,
        #wptb-plugin-container textarea {
            background-color: #003A52 !important;
            color: #fff !important;
            border: 1px solid rgba(173, 214, 233, 0.16) !important;
        }

        /* Label styles */
        #wptb-plugin-container .floating-label label {
            color: #ccc !important;
        }

        /* VOLVER BUTTON FIX */
        #wptb-plugin-container .btn-secondary {
            background: transparent !important;
            color: #004B68 !important;
            border: 2px solid #004B68 !important;
        }

        /* ===== BOOKING DETAILS - WRAPPER ===== */
        .booking-details {
            padding: 40px !important;
        }

        /* ===== FORM ACTIONS: Botones ===== */
        .form-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 16px !important;
            margin-top: 30px !important;
        }
        .form-actions button {
            flex: 1 1 auto !important;
            min-width: 140px !important;
            white-space: nowrap !important;
            overflow: visible !important;
            text-overflow: clip !important;
            font-size: 14px !important;
            padding: 14px 20px !important;
            box-sizing: border-box !important;
        }

        /* ===== BENTO GRID - Full width fields on mobile ===== */
        .wptb-bento-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 16px !important;
        }

        /* ===== MAP ===== */
        #route-map {
            width: 100% !important;
            height: 220px !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            margin-bottom: 20px !important;
            background: #002a3d !important;
        }

        /* ===== MOBILE (<= 768px) ===== */
        @media (max-width: 768px) {
            .booking-details {
                padding: 20px 16px !important;
                margin: 10px auto !important;
            }

            /* Summary sidebar stacks above form */
            .booking-layout-wrapper {
                flex-direction: column !important;
                gap: 20px !important;
            }
            .summary-sidebar {
                width: 100% !important;
                position: relative !important;
                top: 0 !important;
                flex: none !important;
            }

            /* Bento grid → single column on mobile */
            .wptb-bento-grid {
                grid-template-columns: 1fr !important;
            }
            .wptb-bento-grid .floating-label {
                width: 100% !important;
            }

            /* Inputs full width */
            #wptb-plugin-container input,
            #wptb-plugin-container textarea,
            #wptb-plugin-container select {
                width: 100% !important;
                box-sizing: border-box !important;
            }

            /* Buttons stack vertically, full width */
            .form-actions {
                flex-direction: column !important;
                gap: 12px !important;
            }
            .form-actions button {
                width: 100% !important;
                flex: none !important;
                font-size: 15px !important;
                min-height: 54px !important;
                white-space: normal !important;
            }

            /* Map smaller on mobile */
            #route-map {
                height: 180px !important;
            }

            /* Progress bar tighter */
            .progress-bar {
                gap: 6px !important;
            }

            /* Plugin container full width on mobile */
            #wptb-plugin-container {
                padding: 0 !important;
                margin: 0 !important;
            }
        }

        /* ===== SMALL MOBILE (<= 480px) ===== */
        @media (max-width: 480px) {
            .booking-details {
                padding: 16px 12px !important;
                border-radius: 16px !important;
            }
            .contact-summary {
                padding: 16px !important;
            }
            #route-map {
                height: 160px !important;
            }
            .form-actions button {
                font-size: 14px !important;
                padding: 14px 12px !important;
            }
        }
    </style>

    <div class="booking-details" style="display: block; border-radius: 24px !important; padding: 40px !important; border: 1px solid #004B68 !important; width: 100% !important; max-width: 1240px !important; margin: 46px auto 0 !important;">
        
        <!-- STEP 2: VEHICLE SELECTION -->
        <div id="wptb-vehicle-selection-page" class="wptb-vehicle-page-shell" style="display: none;">
            <div id="wptb-step-2" class="booking-vehicle-selection wptb-vehicle-page-step wptb-panel wptb-vehicle-panel" style="background: transparent !important; padding: 0 !important; border: none !important;">
                
                <h2 style="color: #004B68 !important; margin-bottom: 20px; font-weight: 800;">Selecciona tu vehículo</h2>

                <div class="trip-type-selector wptb-vehicle-trip-toggle">
                    <button type="button" class="trip-type-btn active" data-type="one_way">Solo ida</button>
                    <button type="button" class="trip-type-btn" data-type="round_trip">Ida y vuelta</button>
                </div>

                <div id="vehicles-grid" class="vehicles-grid wptb-vehicle-grid">
                    <div class="loading-spinner">Buscando vehículos...</div>
                </div>

            </div>
        </div>

        <!-- STEP 3: DETAILS -->
        <div id="wptb-step-3" class="booking-step" style="background: transparent !important; padding: 0 !important; border: none !important;">
            <h2 style="color: #004B68 !important; margin-bottom: 20px; font-weight: 800;">Detalles de la Reserva</h2>
            
            <div class="booking-layout-wrapper" style="background: transparent !important;">
                <!-- STICKY SUMMARY -->
                <div class="summary-sidebar">
                    <div class="contact-summary sticky-summary" style="background-color: #003f59 !important; border: 1px solid #004B68 !important; border-radius: 24px !important; padding: 25px !important;">
                        <!-- Google Maps (Moved to Top) -->
                        <div id="route-map" style="width:100%; height:240px; border-radius:12px; margin-bottom:20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);"></div>

                        <h3 style="margin-top:0; color:#ffffff !important; text-transform: uppercase;">Resumen del Viaje</h3>
                        <p style="color: #fff;"><strong>Vehículo:</strong> <span id="summary-vehicle" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff;"><strong>Tipo:</strong> <span id="summary-trip-type" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff;"><strong>Origen:</strong> <span id="summary-origin" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff;"><strong>Destino:</strong> <span id="summary-destination" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff;"><strong>Distancia:</strong> <span id="summary-distance" style="color: #FFD700;">-</span> km (<span id="summary-duration" style="color: #FFD700;">-</span>)</p>
                        <hr style="margin: 15px 0; border: none; border-top: 1px solid #004B68;">
                        <h2 style="margin-bottom:0; margin-top:10px; color:#ffffff !important;">Total: <span id="summary-price" style="color:#FFD700;">EUR 0.00</span></h2>
                    </div>
                </div>
                
                <!-- FORM -->
                <div class="form-content" style="background: transparent !important;">
                    <form id="wptb-details-form" action="javascript:void(0);" onsubmit="return false;">
                        <div class="contact-container wptb-bento-grid" style="background: transparent !important;">
                            
                            <div class="floating-label">
                                <input type="number" id="wptb-passengers" min="1" max="50" required placeholder=" ">
                                <label>Número de Pasajeros</label>
                            </div>
                            
                            <!-- Return Trip Details (Hidden by default) -->
                            <div id="wptb-return-details" style="display:none; width: 100%; grid-column: 1 / -1; background: #003f59; padding: 20px; border-radius: 12px; border: 1px solid #004B68; margin-bottom: 20px;">
                                <h3 style="margin-top:0; font-size:16px; color:#004B68 !important; margin-bottom:15px;">Detalles de la Vuelta</h3>
                                <div class="wptb-bento-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                                    <div class="floating-label full-width" style="grid-column: 1 / -1;">
                                        <input type="date" id="wptb-return-date" placeholder=" ">
                                        <label>Fecha de Vuelta</label>
                                    </div>
                                    <div class="floating-label full-width" style="grid-column: 1 / -1;">
                                        <input type="time" id="wptb-return-time" placeholder=" ">
                                        <label>Hora de Vuelta</label>
                                    </div>
                                    <div class="floating-label full-width" style="grid-column: 1 / -1;">
                                        <input type="text" id="wptb-return-origin" placeholder=" ">
                                        <label>Recogida Vuelta (Origen)</label>
                                    </div>
                                    <div class="floating-label full-width" style="grid-column: 1 / -1;">
                                        <input type="text" id="wptb-return-destination" placeholder=" ">
                                        <label>Destino Vuelta</label>
                                    </div>
                                </div>
                            </div>

                            <div class="floating-label">
                                <input type="text" id="wptb-fullname" required placeholder=" ">
                                <label>Nombre Completo</label>
                            </div>
                            
                            <div class="floating-label">
                                <input type="tel" id="wptb-phone" required placeholder=" ">
                                <label>Teléfono</label>
                            </div>
                            
                            <div class="floating-label full-width">
                                <input type="email" id="wptb-email" required placeholder=" ">
                                <label>Email</label>
                            </div>
                            
                            <div class="floating-label">
                                <input type="number" id="wptb-suitcases" min="0" placeholder=" ">
                                <label>Maletas Grandes</label>
                            </div>
                            
                            <div class="floating-label">
                                <input type="number" id="wptb-carryOns" min="0" placeholder=" ">
                                <label>Maletas de Mano</label>
                            </div>

                            <div class="floating-label full-width">
                                <input type="text" id="wptb-flight" placeholder=" ">
                                <label>Número de Vuelo (Opcional)</label>
                            </div>
                            
                            <div class="floating-label full-width">
                                <textarea id="wptb-notes" placeholder=" "></textarea>
                                <label>Notas Adicionales</label>
                            </div>
                        </div>

                        <div class="form-actions" style="background: transparent !important; margin-top: 30px;">
                            <button type="button" class="btn-secondary" id="wptb-back-step3" style="background: transparent !important; color: #004B68 !important; border: 2px solid #004B68 !important; border-radius: 24px !important; min-height: 55px; font-weight: 700; cursor: pointer;">VOLVER</button>
                            <button type="submit" class="btn-primary" id="wptb-confirm-btn" style="background: #004B68 !important; color: #fff !important; border: 2px solid #004B68 !important; border-radius: 24px !important; min-height: 55px; font-weight: 700; cursor: pointer;">CONFIRMAR RESERVA</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- STEP 4: SUCCESS -->
        <div id="wptb-step-4" class="booking-success" style="display: <?php echo (isset($_GET['payment_result']) && $_GET['payment_result'] === 'ok') ? 'block' : 'none'; ?>; background-color: #003f59 !important; border-radius: 24px !important; border: 1px solid #004B68 !important; color: #fff !important;">
            <div class="success-icon">
                <span class="dashicons dashicons-yes" style="color: #004B68 !important;"></span>
            </div>
            <h2 style="color: #fff !important;">¡Reserva Confirmada!</h2>
            <p style="color: #ccc !important;">Hemos enviado los detalles a tu correo electrónico.</p>
            
            <?php
            if ( isset($_GET['payment_result']) && $_GET['payment_result'] === 'ok' && isset($_GET['oid']) ) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'wptb_bookings';
                $order_id = sanitize_text_field( $_GET['oid'] );
                $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE payment_intent_id = %s", $order_id ) );
                
                if ( $booking ) {
                    // Fetch vehicle name
                    $vehicle_table = $wpdb->prefix . 'wptb_vehicles';
                    $vehicle_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $vehicle_table WHERE id = %d", $booking->vehicle_id ) );
                    if ( !$vehicle_name ) $vehicle_name = 'Vehículo Asignado';

                    echo '<div class="order-details-box" style="border: 1px solid #004B68 !important; padding: 20px; border-radius: 12px; margin: 20px 0; text-align: left; color: #fff !important;">';
                    echo '<h3 style="color: #fff !important; margin-bottom: 15px; border-bottom: 1px solid #004B68; padding-bottom: 10px;">Detalles de tu viaje</h3>';
                    echo '<p style="color: #ccc !important; margin-bottom: 5px;"><strong style="color: #fff !important;">Referencia:</strong> <span style="color: #fff !important;">#' . esc_html($booking->id) . '</span></p>';
                    echo '<p style="color: #ccc !important; margin-bottom: 5px;"><strong style="color: #fff !important;">Cliente:</strong> <span style="color: #fff !important;">' . esc_html($booking->customer_name) . '</span></p>';
                    echo '<p style="color: #ccc !important; margin-bottom: 5px;"><strong style="color: #fff !important;">Fecha y Hora:</strong> <span style="color: #fff !important;">' . esc_html($booking->booking_date . ' ' . $booking->booking_time) . '</span></p>';
                    echo '<p style="color: #ccc !important; margin-bottom: 5px;"><strong style="color: #fff !important;">Ruta:</strong> <span style="color: #fff !important;">' . esc_html($booking->origin) . ' <br> &rarr; ' . esc_html($booking->destination) . '</span></p>';
                    echo '<p style="color: #ccc !important; margin-bottom: 5px;"><strong style="color: #fff !important;">Vehículo:</strong> <span style="color: #fff !important;">' . esc_html($vehicle_name) . '</span></p>';
                    echo '<p style="color: #ccc !important; margin-bottom: 5px;"><strong style="color: #fff !important;">Precio Final:</strong> <span style="color: #66d0ff !important; font-weight: bold;">€' . esc_html($booking->price) . '</span></p>';
                    echo '</div>';
                } else {
                    echo '<div class="order-details-box" style="border: 1px solid #004B68 !important; padding: 20px; border-radius: 12px; margin: 20px 0; color: #fff !important;">';
                    echo '<p style="color: #ccc !important;"><strong style="color: #fff !important;">Referencia:</strong> <span id="success-order-id" style="color: #fff !important;">#' . esc_html($order_id) . '</span></p>';
                    echo '</div>';
                }
            } else {
            ?>
                <div class="order-details-box" style="border: 1px solid #004B68 !important; padding: 20px; border-radius: 12px; margin: 20px 0;">
                    <p style="color: #fff;"><strong>Referencia:</strong> <span id="success-order-id" style="color: #004B68;">#...</span></p>
                </div>
            <?php } ?>
            
            <a href="/" class="btn-primary" style="background: #004B68 !important; border-radius: 24px !important; padding: 15px 30px; display: inline-block; color: #fff; text-decoration: none; margin-top: 15px;">Volver al Inicio</a>
        </div>

    </div>
</div>
<style>
/* Make progress bar numbers transparent background compatible */
#wptb-plugin-container .step {
    background-color: #003A52 !important;
    border: 2px solid rgba(173, 214, 233, 0.2) !important;
    color: rgba(217, 235, 245, 0.38) !important;
}
#wptb-plugin-container .step.active, 
#wptb-plugin-container .step.completed {
    background-color: #0077B6 !important;
    color: #fff !important;
    border-color: #0077B6 !important;
}
</style>
