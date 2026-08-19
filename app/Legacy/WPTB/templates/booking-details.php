<?php
/**
 * Template for Booking Details & Confirmation Page
 * Steps 3 & 4
 */
$wptb_payment_state = 'none';
$wptb_payment_order_id = '';
$wptb_payment_booking = null;
$wptb_i18n = \MeTransfers\Booking\I18n::strings();

if ( isset( $_GET['payment_result'] ) ) {
    $wptb_payment_result = sanitize_key( wp_unslash( $_GET['payment_result'] ) );
    $wptb_payment_order_raw = isset( $_GET['oid'] )
        ? sanitize_text_field( wp_unslash( $_GET['oid'] ) )
        : '';
    $wptb_payment_order_id = preg_replace( '/[^0-9A-Za-z]/', '', $wptb_payment_order_raw );

    if ( 'ko' === $wptb_payment_result ) {
        $wptb_payment_state = 'failed';
    } elseif ( 'ok' === $wptb_payment_result
        && '' !== $wptb_payment_order_id
        && $wptb_payment_order_id === $wptb_payment_order_raw ) {
        $wptb_payment_token = isset( $_GET['token'] )
            ? sanitize_text_field( wp_unslash( $_GET['token'] ) )
            : '';

        if ( ! \MeTransfers\Payments\Redsys\Gateway::verify_confirmation_token( $wptb_payment_order_id, $wptb_payment_token ) ) {
            $wptb_payment_state = 'invalid';
        } else {
            global $wpdb;
            $wptb_bookings_table = $wpdb->prefix . 'wptb_bookings';
            $wptb_payment_booking = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, status, payment_status, price FROM $wptb_bookings_table WHERE payment_intent_id = %s",
                    $wptb_payment_order_id
                )
            );

            $wptb_payment_state = $wptb_payment_booking
                && 'paid' === $wptb_payment_booking->payment_status
                && in_array( $wptb_payment_booking->status, array( 'confirmed', 'completed' ), true )
                    ? 'confirmed'
                    : 'pending';
        }
    } else {
        $wptb_payment_state = 'invalid';
    }
}
?>
<div
    id="wptb-plugin-container"
    class="wptb-iso"
    data-payment-state="<?php echo esc_attr( $wptb_payment_state ); ?>"
    <?php if ( 'confirmed' === $wptb_payment_state && $wptb_payment_booking ) : ?>
        data-booking-id="<?php echo esc_attr( $wptb_payment_booking->id ); ?>"
        data-payment-value="<?php echo esc_attr( $wptb_payment_booking->price ); ?>"
        data-payment-currency="EUR"
    <?php endif; ?>
    style="margin-top: 28px;"
>
    
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
                
                <h2 style="color: #004B68 !important; margin-bottom: 20px; font-weight: 800;"><?php echo esc_html( $wptb_i18n['select_vehicle'] ); ?></h2>

                <div class="trip-type-selector wptb-vehicle-trip-toggle">
                    <button type="button" class="trip-type-btn active" data-type="one_way"><?php echo esc_html( $wptb_i18n['one_way'] ); ?></button>
                    <button type="button" class="trip-type-btn" data-type="round_trip"><?php echo esc_html( $wptb_i18n['round_trip'] ); ?></button>
                </div>

                <div id="vehicles-grid" class="vehicles-grid wptb-vehicle-grid">
                    <div class="loading-spinner"><?php echo esc_html( $wptb_i18n['loading_vehicles'] ); ?></div>
                </div>

            </div>
        </div>

        <!-- STEP 3: DETAILS -->
        <div id="wptb-step-3" class="booking-step" style="display: <?php echo 'none' === $wptb_payment_state ? 'block' : 'none'; ?>; background: transparent !important; padding: 0 !important; border: none !important;">
            <h2 style="color: #004B68 !important; margin-bottom: 20px; font-weight: 800;"><?php echo esc_html( $wptb_i18n['booking_details'] ); ?></h2>
            
            <div class="booking-layout-wrapper" style="background: transparent !important;">
                <!-- STICKY SUMMARY -->
                <div class="summary-sidebar">
                    <div class="contact-summary sticky-summary" style="background-color: #003f59 !important; border: 1px solid #004B68 !important; border-radius: 24px !important; padding: 25px !important;">
                        <!-- Google Maps (Moved to Top) -->
                        <div id="route-map" style="width:100%; height:240px; border-radius:12px; margin-bottom:20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);"></div>

                        <h3 style="margin-top:0; color:#ffffff !important; text-transform: uppercase;"><?php echo esc_html( $wptb_i18n['trip_summary'] ); ?></h3>
                        <p style="color: #fff;"><strong><?php echo esc_html( $wptb_i18n['vehicle'] ); ?>:</strong> <span id="summary-vehicle" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff;"><strong><?php echo esc_html( $wptb_i18n['type'] ); ?>:</strong> <span id="summary-trip-type" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff;"><strong><?php echo esc_html( $wptb_i18n['origin'] ); ?>:</strong> <span id="summary-origin" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff;"><strong><?php echo esc_html( $wptb_i18n['destination'] ); ?>:</strong> <span id="summary-destination" style="color: #FFD700;">-</span></p>
                        <p style="color: #fff;"><strong><?php echo esc_html( $wptb_i18n['distance'] ); ?>:</strong> <span id="summary-distance" style="color: #FFD700;">-</span> km (<span id="summary-duration" style="color: #FFD700;">-</span>)</p>
                        <hr style="margin: 15px 0; border: none; border-top: 1px solid #004B68;">
                        <h2 style="margin-bottom:0; margin-top:10px; color:#ffffff !important;"><?php echo esc_html( $wptb_i18n['total'] ); ?>: <span id="summary-price" style="color:#FFD700;">EUR 0.00</span></h2>
                    </div>
                </div>
                
                <!-- FORM -->
                <div class="form-content" style="background: transparent !important;">
                    <form id="wptb-details-form" action="javascript:void(0);" onsubmit="return false;">
                        <div class="contact-container wptb-bento-grid" style="background: transparent !important;">
                            
                            <div class="floating-label">
                                <input type="number" id="wptb-passengers" min="1" max="50" required placeholder=" ">
                                <label><?php echo esc_html( $wptb_i18n['passengers'] ); ?></label>
                            </div>
                            
                            <!-- Return Trip Details (Hidden by default) -->
                            <div id="wptb-return-details" style="display:none; width: 100%; grid-column: 1 / -1; background: #003f59; padding: 20px; border-radius: 12px; border: 1px solid #004B68; margin-bottom: 20px;">
                                <h3 style="margin-top:0; font-size:16px; color:#004B68 !important; margin-bottom:15px;"><?php echo esc_html( $wptb_i18n['return_details'] ); ?></h3>
                                <div class="wptb-bento-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                                    <div class="floating-label full-width" style="grid-column: 1 / -1;">
                                        <input type="date" id="wptb-return-date" placeholder=" ">
                                        <label><?php echo esc_html( $wptb_i18n['return_date'] ); ?></label>
                                    </div>
                                    <div class="floating-label full-width" style="grid-column: 1 / -1;">
                                        <input type="time" id="wptb-return-time" placeholder=" ">
                                        <label><?php echo esc_html( $wptb_i18n['return_time'] ); ?></label>
                                    </div>
                                    <div class="floating-label full-width" style="grid-column: 1 / -1;">
                                        <input type="text" id="wptb-return-origin" placeholder=" ">
                                        <label><?php echo esc_html( $wptb_i18n['return_pickup'] ); ?></label>
                                    </div>
                                    <div class="floating-label full-width" style="grid-column: 1 / -1;">
                                        <input type="text" id="wptb-return-destination" placeholder=" ">
                                        <label><?php echo esc_html( $wptb_i18n['return_destination'] ); ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="floating-label">
                                <input type="text" id="wptb-fullname" required placeholder=" ">
                                <label><?php echo esc_html( $wptb_i18n['full_name'] ); ?></label>
                            </div>
                            
                            <div class="floating-label">
                                <input type="tel" id="wptb-phone" required placeholder=" ">
                                <label><?php echo esc_html( $wptb_i18n['phone'] ); ?></label>
                            </div>
                            
                            <div class="floating-label full-width">
                                <input type="email" id="wptb-email" required placeholder=" ">
                                <label><?php echo esc_html( $wptb_i18n['email'] ); ?></label>
                            </div>
                            
                            <div class="floating-label">
                                <input type="number" id="wptb-suitcases" min="0" placeholder=" ">
                                <label><?php echo esc_html( $wptb_i18n['large_suitcases'] ); ?></label>
                            </div>
                            
                            <div class="floating-label">
                                <input type="number" id="wptb-carryOns" min="0" placeholder=" ">
                                <label><?php echo esc_html( $wptb_i18n['carry_on'] ); ?></label>
                            </div>

                            <div class="floating-label full-width">
                                <input type="text" id="wptb-flight" placeholder=" ">
                                <label><?php echo esc_html( $wptb_i18n['flight_optional'] ); ?></label>
                            </div>
                            
                            <div class="floating-label full-width">
                                <textarea id="wptb-notes" placeholder=" "></textarea>
                                <label><?php echo esc_html( $wptb_i18n['additional_notes'] ); ?></label>
                            </div>
                        </div>

                        <div class="form-actions" style="background: transparent !important; margin-top: 30px;">
                            <button type="button" class="btn-secondary" id="wptb-back-step3" style="background: transparent !important; color: #004B68 !important; border: 2px solid #004B68 !important; border-radius: 24px !important; min-height: 55px; font-weight: 700; cursor: pointer;"><?php echo esc_html( $wptb_i18n['back'] ); ?></button>
                            <button type="submit" class="btn-primary" id="wptb-confirm-btn" style="background: #004B68 !important; color: #fff !important; border: 2px solid #004B68 !important; border-radius: 24px !important; min-height: 55px; font-weight: 700; cursor: pointer;"><?php echo esc_html( $wptb_i18n['confirm_booking'] ); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- STEP 4: SUCCESS -->
        <div id="wptb-step-4" class="booking-success" style="display: <?php echo 'confirmed' === $wptb_payment_state ? 'block' : 'none'; ?>; background-color: #003f59 !important; border-radius: 24px !important; border: 1px solid #004B68 !important; color: #fff !important;">
            <div class="success-icon">
                <span class="dashicons dashicons-yes" style="color: #004B68 !important;"></span>
            </div>
            <h2 style="color: #fff !important;"><?php echo esc_html( $wptb_i18n['booking_confirmed'] ); ?></h2>
            <p style="color: #ccc !important;"><?php echo esc_html( $wptb_i18n['confirmation_email'] ); ?></p>
            
            <div class="order-details-box" style="border: 1px solid #004B68 !important; padding: 20px; border-radius: 12px; margin: 20px 0;">
                <p style="color: #fff;"><strong><?php echo esc_html( $wptb_i18n['reference'] ); ?>:</strong> <span id="success-order-id" style="color: #fff;">#<?php echo $wptb_payment_order_id ? esc_html( $wptb_payment_order_id ) : '...'; ?></span></p>
            </div>
            
            <button type="button" id="btn-download-pdf" class="btn-primary" style="background: #0056b3 !important; border-radius: 24px !important; padding: 15px 30px; color: #fff; border: 0; cursor: pointer;">
                <?php echo esc_html( $wptb_i18n['download_receipt'] ); ?>
            </button>
            <a href="<?php echo esc_url( \MeTransfers\Booking\I18n::url( '/' ) ); ?>" class="btn-primary" style="background: #004B68 !important; border-radius: 24px !important; padding: 15px 30px; display: inline-block; color: #fff; text-decoration: none; margin-top: 15px;"><?php echo esc_html( $wptb_i18n['back_home'] ); ?></a>
        </div>

        <div id="wptb-payment-pending" class="booking-success" style="display: <?php echo 'pending' === $wptb_payment_state ? 'block' : 'none'; ?>; background-color: #003f59 !important; border-radius: 24px !important; border: 1px solid #004B68 !important; color: #fff !important;">
            <h2 style="color: #fff !important;"><?php echo esc_html( $wptb_i18n['payment_pending_title'] ); ?></h2>
            <p style="color: #ccc !important;"><?php echo esc_html( $wptb_i18n['payment_pending_text'] ); ?></p>
            <?php if ( $wptb_payment_order_id ) : ?>
                <p style="color: #fff;"><strong><?php echo esc_html( $wptb_i18n['reference'] ); ?>:</strong> #<?php echo esc_html( $wptb_payment_order_id ); ?></p>
            <?php endif; ?>
        </div>

        <div id="wptb-payment-failed" class="booking-success" style="display: <?php echo 'failed' === $wptb_payment_state ? 'block' : 'none'; ?>; background-color: #003f59 !important; border-radius: 24px !important; border: 1px solid #004B68 !important; color: #fff !important;">
            <h2 style="color: #fff !important;"><?php echo esc_html( $wptb_i18n['payment_failed_title'] ); ?></h2>
            <p style="color: #ccc !important;"><?php echo esc_html( $wptb_i18n['payment_failed_text'] ); ?></p>
            <a href="<?php echo esc_url( \MeTransfers\Booking\I18n::url( '/reservas-metransfers/' ) ); ?>" class="btn-primary" style="background: #004B68 !important; border-radius: 24px !important; padding: 15px 30px; display: inline-block; color: #fff; text-decoration: none; margin-top: 15px;"><?php echo esc_html( $wptb_i18n['try_again'] ); ?></a>
        </div>

        <div id="wptb-payment-invalid" class="booking-success" style="display: <?php echo 'invalid' === $wptb_payment_state ? 'block' : 'none'; ?>; background-color: #003f59 !important; border-radius: 24px !important; border: 1px solid #004B68 !important; color: #fff !important;">
            <h2 style="color: #fff !important;"><?php echo esc_html( $wptb_i18n['invalid_confirmation_title'] ); ?></h2>
            <p style="color: #ccc !important;"><?php echo esc_html( $wptb_i18n['invalid_confirmation_text'] ); ?></p>
            <a href="<?php echo esc_url( \MeTransfers\Booking\I18n::url( '/' ) ); ?>" class="btn-primary" style="background: #004B68 !important; border-radius: 24px !important; padding: 15px 30px; display: inline-block; color: #fff; text-decoration: none; margin-top: 15px;"><?php echo esc_html( $wptb_i18n['back_home'] ); ?></a>
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
