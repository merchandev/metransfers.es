// ===== REDSYS PAYMENT FLOW =====
(function ($) {
    'use strict';

    $(document).ready(function () {
        console.log('🔐 Redsys Payment v1.2 - Initialized (Scoped)');

        // Check for Return from Redsys (Success/Error)
        const urlParams = new URLSearchParams(window.location.search);
        const paymentResult = urlParams.get('payment_result');
        const paymentOID = urlParams.get('oid');
        const isRedsysReturn = (paymentResult === 'ok');

        // Only run on payment page OR if returning from Redsys
        if ($('#wptb-payment-step').length === 0 && !isRedsysReturn) {
            return;
        }

        if (isRedsysReturn) {
            // Force hide other steps if we are on the main booking form
            console.log('✅ Redsys Return Detected: Showing Success Screen');
            $('.booking-form, .booking-vehicle-selection, #wptb-step-1, #wptb-step-2, #wptb-step-3').hide();
        }

        // ===== STEP 1: VALIDATE CONFIGURATION =====
        if (typeof wptb_vars === 'undefined') {
            console.error('❌ FATAL: wptb_vars not defined');
            showError('Error de configuración. Contacta al administrador.');
            return;
        }

        // ===== STEP 2: LOAD BOOKING DATA =====
        const bookingData = loadBookingData();

        if (paymentResult === 'ok') {
            $('#wptb-payment-step').hide();
            // Show success logic
            handlePaymentSuccess(paymentOID);
            return;
        } else if (paymentResult === 'ko') {
            showError('El pago ha sido cancelado o rechazado por el banco.');
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
            console.log('👆 Payment button clicked');
            initiateRedsysPayment(bookingData);
        });

        // Also bind form submit just in case
        $('#payment-form').off('submit').on('submit', function (e) {
            e.preventDefault();
            console.log('📝 Payment form submitted');
            initiateRedsysPayment(bookingData);
        });

        // ===== FUNCTIONS =====

        function loadBookingData() {
            const saved = sessionStorage.getItem('wptb_booking_data');
            if (!saved) {
                if (window.location.search.includes('payment_result=ok')) return null;

                showError('No hay datos de reserva. Por favor inicia una nueva reserva.');
                setTimeout(() => { window.location.href = '/'; }, 3000);
                return null;
            }

            try {
                return JSON.parse(saved);
            } catch (error) {
                showError('Datos de reserva corruptos.');
                return null;
            }
        }

        function populateSummary(data) {
            if (!data) return;
            $('#payment-vehicle').text(data.vehicle_name || '-');

            const tripLabels = { 'one_way': 'Solo Ida', 'round_trip': 'Ida y Vuelta', 'return': 'Vuelta' };
            $('#payment-trip-type').text(tripLabels[data.trip_type] || 'Solo Ida');

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

            console.log('🗺️ Rendering Map for:', data.origin, '->', data.destination);

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
            console.log('🔄 Initiating Redsys Payment...');
            setLoading(true);

            // Check if we already have a booking ID
            const existingBookingId = bookingData.id || null;

            $.ajax({
                url: wptb_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wptb_initiate_redsys',
                    booking_data: JSON.stringify(bookingData),
                    existing_booking_id: existingBookingId,
                    security: wptb_vars.nonce
                },
                success: function (response) {
                    console.log('📥 Redsys Response:', response);

                    if (!response.success) {
                        showError(response.data.message || 'Error al conectar con el banco.');
                        setLoading(false);
                        return;
                    }

                    // Submit Form to Redsys
                    submitToRedsys(response.data);
                },
                error: function (xhr, status, error) {
                    console.error('❌ AJAX Error:', error);
                    showError('Error de conexión.');
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

            console.log('🚀 Submitting form to Redsys...');
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
                $('#success-order-id').text('#' + (data.id || oid));
                window.lastBookingData = data;

                // Clean up
                sessionStorage.removeItem('wptb_booking_data');
            } else {
                $('#success-order-id').text('#' + oid.replace(/^0+/, '')); // Remove leading zeros
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
                $('#button-text').text('PROCESANDO...');
                $('#payment-spinner').show();
            } else {
                btn.prop('disabled', false);
                $('#button-text').text('PAGAR');
                $('#payment-spinner').hide();
            }
        }

        // PDF Generation
        $('#btn-download-pdf').on('click', function (e) {
            e.preventDefault();
            generatePDF();
        });

        function generatePDF() {
            if (!window.jspdf) {
                alert('Librería PDF no cargada.');
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
            doc.text("Recibo de Reserva", 20, 25);

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
            const tripLabels = { 'one_way': 'Solo Ida', 'round_trip': 'Ida y Vuelta', 'return': 'Vuelta' };
            const tripType = tripLabels[data.trip_type] || 'Solo Ida';

            // Details
            doc.setFont(undefined, 'bold');
            doc.text("Referencia:", 20, y);
            doc.setFont(undefined, 'normal');
            doc.text("#" + (data.id || (window.lastBookingData ? window.lastBookingData.id : '---')), 60, y);
            y += lineHeight;

            doc.setFont(undefined, 'bold');
            doc.text("Fecha:", 20, y);
            doc.setFont(undefined, 'normal');
            doc.text(data.date + " " + (data.time || ''), 60, y);
            y += lineHeight;

            doc.setFont(undefined, 'bold');
            doc.text("Tipo de Viaje:", 20, y);
            doc.setFont(undefined, 'normal');
            doc.text(tripType, 60, y);
            y += lineHeight;

            doc.setFont(undefined, 'bold');
            doc.text("Vehículo:", 20, y);
            doc.setFont(undefined, 'normal');
            doc.text(data.vehicle_name || '-', 60, y);
            y += lineHeight;

            doc.setFont(undefined, 'bold');
            doc.text("Origen:", 20, y);
            doc.setFont(undefined, 'normal');
            // Split text if too long
            const splitOrigin = doc.splitTextToSize(data.origin || '-', 130);
            doc.text(splitOrigin, 60, y);
            y += (splitOrigin.length * 6) + 4;

            doc.setFont(undefined, 'bold');
            doc.text("Destino:", 20, y);
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
            doc.text("Total Pagado:", 30, y + 10);
            doc.text("€" + (data.price ? parseFloat(data.price).toFixed(2) : '-'), 150, y + 10);

            doc.save("reserva-metransfers.pdf");
        }

    });
})(jQuery);
