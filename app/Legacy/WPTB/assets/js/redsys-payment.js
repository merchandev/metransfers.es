// ===== REDSYS PAYMENT FLOW =====
(function ($) {
    'use strict';

    $(document).ready(function () {

        const paymentStrings = (typeof wptb_vars !== 'undefined' && wptb_vars.strings) ? wptb_vars.strings : {};

        function t(key, fallback) {
            return paymentStrings[key] || fallback;
        }

        function track(eventName, parameters) {
            if (typeof window.mtBookingTrack === 'function') {
                window.mtBookingTrack(eventName, parameters || {});
            }
        }

        // Check for Return from Redsys (Success/Error)
        const urlParams = new URLSearchParams(window.location.search);
        const paymentResult = urlParams.get('payment_result');
        const paymentOID = urlParams.get('oid');
        const isRedsysReturn = paymentResult === 'ok' || paymentResult === 'ko';
        const isSuccessfulReturn = paymentResult === 'ok';
        const serverPaymentState = $('#wptb-plugin-container').attr('data-payment-state') || 'none';

        // Only run on payment page OR if returning from Redsys
        if ($('#wptb-payment-step').length === 0 && !isRedsysReturn) {
            return;
        }

        if (isSuccessfulReturn) {
            // Force hide other steps if we are on the main booking form
            $('.booking-form, .booking-vehicle-selection, #wptb-step-1, #wptb-step-2, #wptb-step-3').hide();
        }

        // ===== STEP 1: VALIDATE CONFIGURATION =====
        if (typeof wptb_vars === 'undefined') {
            console.error('❌ FATAL: wptb_vars not defined');
            track('payment_error', { error_type: 'configuration' });
            showError(t('configuration_error', 'Error de configuración. Contacta con soporte.'));
            return;
        }

        // ===== STEP 2: LOAD BOOKING DATA =====
        const bookingData = loadBookingData();

        if (paymentResult === 'ok') {
            $('#wptb-payment-step').hide();
            if (serverPaymentState === 'confirmed') {
                handlePaymentSuccess(paymentOID);
            }
            return;
        } else if (paymentResult === 'ko') {
            track('payment_error', { error_type: 'cancelled_or_declined' });
            showError(t('payment_cancelled', 'El pago ha sido cancelado o rechazado por el banco.'));
        }

        if (!bookingData) {
            if (paymentResult !== 'ok') { // Only redirect if not on success page
                // loadBookingData handles redirection or error
            }
            return;
        }

        // ===== STEP 3: POPULATE SUMMARY =====
        populateSummary(bookingData);

        // ===== STEP 4: ATTACH HANDLER =====
        // Explicitly handle click to avoid form submit issues
        $('#submit-payment').off('click').on('click', function (e) {
            e.preventDefault();
            initiateRedsysPayment(bookingData);
        });

        // Also bind form submit just in case
        $('#payment-form').off('submit').on('submit', function (e) {
            e.preventDefault();
            initiateRedsysPayment(bookingData);
        });

        $('#wptb-payment-back').off('click').on('click', function () {
            window.history.back();
        });

        // ===== FUNCTIONS =====

        function loadBookingData() {
            const saved = sessionStorage.getItem('wptb_booking_data');
            if (!saved) {
                if (isRedsysReturn) return null;

                track('payment_error', { error_type: 'booking_data_missing' });
                showError(t('invalid_booking_data', 'No hay datos de reserva válidos. Inicia una nueva reserva.'));
                setTimeout(() => {
                    window.location.href = (typeof wptb_vars !== 'undefined' && wptb_vars.home_url) ? wptb_vars.home_url : '/';
                }, 3000);
                return null;
            }

            try {
                return JSON.parse(saved);
            } catch (error) {
                track('payment_error', { error_type: 'booking_data_corrupt' });
                showError(t('corrupt_booking_data', 'Los datos de la reserva están dañados.'));
                return null;
            }
        }

        function populateSummary(data) {
            if (!data) return;
            $('#payment-vehicle').text(data.vehicle_name || '-');

            const tripLabels = {
                'one_way': t('one_way', 'Solo ida'),
                'round_trip': t('round_trip', 'Ida y vuelta'),
                'return': t('return_details', 'Vuelta')
            };
            $('#payment-trip-type').text(tripLabels[data.trip_type] || t('one_way', 'Solo ida'));

            $('#payment-origin').text(data.origin || '-');
            $('#payment-destination').text(data.destination || '-');
            $('#payment-date').text(data.date + ' ' + (data.time || ''));
            $('#payment-price').text('€' + parseFloat(data.price).toFixed(2));
            // Price removed from button as per user request
            // $('#button-amount').text(parseFloat(data.price).toFixed(2));

            // Render Map
            if ($('#map-canvas').length > 0) {
                waitForGoogleMaps().then(() => {
                    renderMap(data);
                });
            }
        }

        function waitForGoogleMaps() {
            return new Promise((resolve) => {
                if (typeof google !== 'undefined' && google.maps && typeof google.maps.Map === 'function') {
                    resolve();
                } else {
                    let attempts = 0;
                    const checkInterval = setInterval(() => {
                        attempts++;
                        if (typeof google !== 'undefined' && google.maps && typeof google.maps.Map === 'function') {
                            clearInterval(checkInterval);
                            resolve();
                        } else if (attempts > 50) { // 5 seconds timeout
                            clearInterval(checkInterval);
                            console.warn('[Redsys] Google Maps API timeout or missing Map constructor.');
                            resolve(); // resolve anyway so we don't break the page, map just won't render
                        }
                    }, 100);
                }
            });
        }

        function renderMap(data) {
            if (!data.origin || !data.destination) return;
            if (typeof google === 'undefined' || !google.maps || typeof google.maps.Map !== 'function') {
                console.error('[Redsys] Cannot render map: google.maps.Map is not a constructor.');
                return;
            }


            const mapElement = document.getElementById('map-canvas');
            const mapOptions = {
                zoom: 10,
                center: { lat: 41.3851, lng: 2.1734 }, // Default Barcelona
                streetViewControl: false,
                mapTypeControl: false,
                fullscreenControl: false
            };

            const map = new google.maps.Map(mapElement, mapOptions);
            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: false
            });

            const request = {
                origin: data.origin,
                destination: data.destination,
                travelMode: 'DRIVING'
            };

            directionsService.route(request, function (result, status) {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                } else {
                    console.warn('⚠️ Could not calculate route for map:', status);
                }
            });
        }

        function initiateRedsysPayment(bookingData) {

            const $terms = $('#wptb-accept-terms');
            if ($terms.length && !$terms.is(':checked')) {
                $('#wptb-terms-error').show();
                track('payment_error', { error_type: 'terms_not_accepted' });
                return;
            }
            $('#wptb-terms-error').hide();
            setLoading(true);

            bookingData.language = (typeof wptb_vars !== 'undefined' && wptb_vars.language) ? wptb_vars.language : 'es';
            bookingData.terms_accepted = true;
            bookingData.terms_version = wptb_vars.terms_version || '';
            bookingData.analytics_client_id = typeof window.mtAnalyticsClientId === 'function' ? window.mtAnalyticsClientId() : '';
            track('add_payment_info', {
                payment_type: 'redsys',
                vehicle_id: bookingData.vehicle_id,
                value: Number.parseFloat(bookingData.price || 0),
                currency: 'EUR'
            });

            $.ajax({
                url: wptb_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wptb_initiate_redsys',
                    booking_data: JSON.stringify(bookingData),
                    security: wptb_vars.nonce
                },
                success: function (response) {

                    if (!response.success) {
                        const errorCode = response.data && response.data.code ? response.data.code : 'gateway_initialization';
                        track('payment_error', { error_type: errorCode });
                        if (response.data && response.data.code === 'price_changed') {
                            bookingData.price = parseFloat(response.data.server_price);
                            sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));
                            populateSummary(bookingData);
                        }
                        showError((response.data && response.data.message) || t('bank_connection_error', 'No se pudo conectar con el banco.'));
                        setLoading(false);
                        return;
                    }

                    track('generate_lead', {
                        booking_id: response.data.booking_id,
                        value: Number.parseFloat(bookingData.price || 0),
                        currency: 'EUR'
                    });

                    // Submit Form to Redsys
                    submitToRedsys(response.data);
                },
                error: function (xhr, status, error) {
                    console.error('❌ AJAX Error:', error);
                    track('payment_error', { error_type: 'network' });
                    showError(t('connection_error', 'Error de conexión.'));
                    setLoading(false);
                }
            });
        }

        function submitToRedsys(data) {
            // Create Form
            const form = $('<form>', {
                'action': data.url,
                'method': 'POST',
                'target': '_self'
            });

            form.append($('<input>', { 'type': 'hidden', 'name': 'Ds_SignatureVersion', 'value': data.ds_signature_version }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'Ds_MerchantParameters', 'value': data.ds_merchant_parameters }));
            form.append($('<input>', { 'type': 'hidden', 'name': 'Ds_Signature', 'value': data.ds_signature }));

            $('body').append(form);

            form.submit();
        }

        function handlePaymentSuccess(oid) {
            $('#wptb-step-3').hide();
            $('#wptb-step-4').show();
            $('#success-order-id').text('#' + oid);

            // Try to recover data for PDF
            const saved = sessionStorage.getItem('wptb_booking_data');
            if (saved) {
                const data = JSON.parse(saved);
                window.lastBookingData = data;

                // Clean up
                sessionStorage.removeItem('wptb_booking_data');
            }
        }

        // ===== UTILITY =====
        function showError(message) {
            const $msg = $('#payment-message');
            $msg.removeClass('success').addClass('error');
            $msg.text(message).fadeIn();
            setTimeout(() => $msg.fadeOut(), 8000);
        }

        function setLoading(isLoading) {
            const btn = $('#submit-payment');
            if (isLoading) {
                btn.prop('disabled', true);
                $('#button-text').text(t('processing', 'Procesando...'));
                $('#payment-spinner').show();
            } else {
                btn.prop('disabled', false);
                $('#button-text').text(t('pay', 'Pagar'));
                $('#payment-spinner').hide();
            }
        }

        // PDF Generation
        $('#btn-download-pdf').on('click', function (e) {
            e.preventDefault();
            loadPdfLibrary()
                .then(generatePDF)
                .catch(function () {
                    alert(t('pdf_unavailable', 'La librería PDF no está disponible.'));
                });
        });

        function loadPdfLibrary() {
            if (window.jspdf) {
                return Promise.resolve();
            }

            return new Promise(function (resolve, reject) {
                const source = (typeof wptb_vars !== 'undefined' && wptb_vars.pdf_library_url) ? wptb_vars.pdf_library_url : '';
                if (!source) {
                    reject(new Error('Missing PDF library URL.'));
                    return;
                }

                const existing = document.querySelector('script[data-mt-jspdf="1"]');
                if (existing) {
                    existing.addEventListener('load', resolve, { once: true });
                    existing.addEventListener('error', reject, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = source;
                script.async = true;
                script.dataset.mtJspdf = '1';
                script.addEventListener('load', resolve, { once: true });
                script.addEventListener('error', reject, { once: true });
                document.head.appendChild(script);
            });
        }

        function generatePDF() {
            if (!window.jspdf) {
                alert(t('pdf_unavailable', 'La librería PDF no está disponible.'));
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const data = window.lastBookingData || {}; // We rely on data gathered before purge

            // Load Logo
            const logoUrl = 'https://metransfers.es/wp-content/uploads/2026/01/LOGO-CREDITOS-MAIL.png';
            const img = new Image();
            img.crossOrigin = "Anonymous"; // Try to handle CORS
            img.src = logoUrl;

            img.onload = function () {
                renderPDFContent(doc, data, img);
            };

            img.onerror = function () {
                // Fallback if image fails
                renderPDFContent(doc, data, null);
            };
        }

        function renderPDFContent(doc, data, logoImg) {
            // Colors
            const orange = [255, 113, 0]; // #ff7100
            const white = [255, 255, 255];
            const dark = [0, 3, 59]; // #00033b

            // Header
            doc.setFillColor(...dark); // Dark Blue Header
            doc.rect(0, 0, 210, 40, 'F');

            doc.setTextColor(...white);
            doc.setFontSize(22);
            doc.text(t('receipt_title', 'Recibo de reserva'), 20, 25);

            // Logo (Right aligned in header)
            if (logoImg) {
                // Keep aspect ratio roughly (width 40-50)
                const imgWidth = 50;
                const imgHeight = (logoImg.height * imgWidth) / logoImg.width;
                doc.addImage(logoImg, 'PNG', 140, 10, imgWidth, imgHeight);
            } else {
                doc.setFontSize(16);
                doc.text("Metransfers", 150, 25);
            }

            // Horizontal Line
            doc.setDrawColor(...orange);
            doc.setLineWidth(1);
            doc.line(20, 45, 190, 45);

            // Content
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(12);
            let y = 60;
            const lineHeight = 10;

            // Trip Type Label
            const tripLabels = {
                'one_way': t('one_way', 'Solo ida'),
                'round_trip': t('round_trip', 'Ida y vuelta'),
                'return': t('return_details', 'Vuelta')
            };
            const tripType = tripLabels[data.trip_type] || t('one_way', 'Solo ida');

            // Details
            doc.setFont(undefined, 'bold');
            doc.text(t('reference', 'Referencia') + ':', 20, y);
            doc.setFont(undefined, 'normal');
            doc.text("#" + (data.id || (window.lastBookingData ? window.lastBookingData.id : '---')), 60, y);
            y += lineHeight;

            doc.setFont(undefined, 'bold');
            doc.text(t('date', 'Fecha') + ':', 20, y);
            doc.setFont(undefined, 'normal');
            doc.text(data.date + " " + (data.time || ''), 60, y);
            y += lineHeight;

            doc.setFont(undefined, 'bold');
            doc.text(t('trip_type', 'Tipo de viaje') + ':', 20, y);
            doc.setFont(undefined, 'normal');
            doc.text(tripType, 60, y);
            y += lineHeight;

            doc.setFont(undefined, 'bold');
            doc.text(t('vehicle', 'Vehículo') + ':', 20, y);
            doc.setFont(undefined, 'normal');
            doc.text(data.vehicle_name || '-', 60, y);
            y += lineHeight;

            doc.setFont(undefined, 'bold');
            doc.text(t('origin', 'Origen') + ':', 20, y);
            doc.setFont(undefined, 'normal');
            // Split text if too long
            const splitOrigin = doc.splitTextToSize(data.origin || '-', 130);
            doc.text(splitOrigin, 60, y);
            y += (splitOrigin.length * 6) + 4;

            doc.setFont(undefined, 'bold');
            doc.text(t('destination', 'Destino') + ':', 20, y);
            doc.setFont(undefined, 'normal');
            const splitDest = doc.splitTextToSize(data.destination || '-', 130);
            doc.text(splitDest, 60, y);
            y += (splitDest.length * 6) + 10;

            // Total Price Box
            doc.setFillColor(...orange);
            doc.roundedRect(20, y, 170, 15, 3, 3, 'F');
            doc.setTextColor(...white);
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text(t('total_paid', 'Total pagado') + ':', 30, y + 10);
            doc.text("€" + (data.price ? parseFloat(data.price).toFixed(2) : '-'), 150, y + 10);

            doc.save("reserva-metransfers.pdf");
        }

    });
})(jQuery);
