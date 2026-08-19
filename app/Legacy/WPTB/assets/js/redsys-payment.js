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

        // Confirmation and receipts have a dedicated, server-authoritative
        // bundle. This file runs only on the payment-start page.
        if ($('#wptb-payment-step').length === 0) {
            return;
        }

        // ===== STEP 1: VALIDATE CONFIGURATION =====
        if (typeof wptb_vars === 'undefined') {
            console.error('❌ FATAL: wptb_vars not defined');
            track('payment_error', { error_type: 'configuration' });
            showError(t('configuration_error', 'Error de configuración. Contacta con soporte.'));
            return;
        }

        // ===== STEP 2: LOAD SERVER-SIDE DRAFT =====
        const draftToken = loadDraftToken();

        if (!draftToken) {
            return;
        }

        setLoading(true);
        loadBookingData(draftToken, function (bookingData) {
            // ===== STEP 3: POPULATE SUMMARY =====
            populateSummary(bookingData);
            setLoading(false);

            // ===== STEP 4: ATTACH HANDLER =====
            $('#submit-payment').off('click').on('click', function (e) {
                e.preventDefault();
                initiateRedsysPayment(draftToken, bookingData);
            });

            $('#payment-form').off('submit').on('submit', function (e) {
                e.preventDefault();
                initiateRedsysPayment(draftToken, bookingData);
            });
        }, function () {
            setLoading(false);
            track('payment_error', { error_type: 'draft_invalid_or_expired' });
            showError(t('invalid_booking_data', 'No hay datos de reserva válidos. Inicia una nueva reserva.'));
        });

        $('#wptb-payment-back').off('click').on('click', function () {
            window.location.href = wptb_vars.home_url || '/';
        });

        // ===== FUNCTIONS =====

        function loadDraftToken() {
            const saved = sessionStorage.getItem('wptb_booking_data');
            if (!saved) {
                track('payment_error', { error_type: 'booking_data_missing' });
                showError(t('invalid_booking_data', 'No hay datos de reserva válidos. Inicia una nueva reserva.'));
                setTimeout(() => {
                    window.location.href = (typeof wptb_vars !== 'undefined' && wptb_vars.home_url) ? wptb_vars.home_url : '/';
                }, 3000);
                return null;
            }

            try {
                const stored = JSON.parse(saved);
                if (stored && /^[a-f0-9]{64}$/.test(stored.draft_token || '')) {
                    return stored.draft_token;
                }
                throw new Error('Invalid draft token');
            } catch (error) {
                track('payment_error', { error_type: 'booking_data_corrupt' });
                showError(t('corrupt_booking_data', 'Los datos de la reserva están dañados.'));
                return null;
            }
        }

        function loadBookingData(token, onSuccess, onFailure) {
            $.ajax({
                url: wptb_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wptb_get_booking_draft',
                    draft_token: token,
                    security: wptb_vars.nonce
                }
            }).done(function (response) {
                if (response && response.success && response.data && response.data.booking) {
                    onSuccess(response.data.booking);
                    return;
                }

                if (typeof onFailure === 'function') {
                    onFailure();
                    return;
                }
                track('payment_error', { error_type: 'draft_invalid_or_expired' });
                const message = response && response.data && response.data.message
                    ? response.data.message
                    : t('invalid_booking_data', 'No hay datos de reserva válidos. Inicia una nueva reserva.');
                showError(message);
            }).fail(function () {
                if (typeof onFailure === 'function') {
                    onFailure();
                    return;
                }
                track('payment_error', { error_type: 'draft_connection' });
                showError(t('connection_error', 'Error de conexión.'));
            });
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
            $('#payment-passengers').text(data.passengers || '1');
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

        function initiateRedsysPayment(token, bookingData) {

            const $terms = $('#wptb-accept-terms');
            if ($terms.length && !$terms.is(':checked')) {
                $('#wptb-terms-error').show();
                track('payment_error', { error_type: 'terms_not_accepted' });
                return;
            }
            $('#wptb-terms-error').hide();
            setLoading(true);

            const analyticsClientId = typeof window.mtAnalyticsClientId === 'function' ? window.mtAnalyticsClientId() : '';
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
                    draft_token: token,
                    terms_accepted: '1',
                    terms_version: wptb_vars.terms_version || '',
                    analytics_client_id: analyticsClientId,
                    security: wptb_vars.nonce
                },
                success: function (response) {

                    if (!response.success) {
                        const errorCode = response.data && response.data.code ? response.data.code : 'gateway_initialization';
                        track('payment_error', { error_type: errorCode });
                        if (response.data && response.data.code === 'price_changed') {
                            bookingData.price = parseFloat(response.data.server_price);
                            bookingData.price_cents = Number.parseInt(response.data.server_price_cents, 10);
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

    });
})(jQuery);
