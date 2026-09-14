jQuery(function ($) {
    'use strict';

    const $form = $('#hqp-booking-form');
    if (!$form.length) return;

    const vars = window.hqpBookingVars || {};
    const ajaxUrl = vars.ajax_url || '';
    const nonce = vars.nonce || '';
    const hotelId = parseInt(vars.hotel_id || $('#hqp-hotel-id').val(), 10) || 0;
    const hotelToken = vars.hotel_token || '';
    const actions = vars.actions || {};
    const routeLocations = vars.route_locations || {};
    const hotelAddress = String($('#hqp-hotel-address').val() || '').trim();

    if (!ajaxUrl || !nonce || !hotelId || !hotelToken || !hotelAddress) {
        $form.html('<div class="hqp-error">No se pudo validar el código QR del hotel. Vuelve a escanearlo o contacta con recepción.</div>');
        return;
    }

    const pricingAction = actions.pricing || 'mt_hotel_get_fixed_pricing';
    const bookingAction = actions.booking || 'mt_hotel_create_booking';
    let bookingData = null;
    let currentStep = 1;
    let quoteRequest = null;
    let quoteVersion = 0;
    let paymentPending = false;
    let leavingForPayment = false;

    function showFeedback(message) {
        $('#hqp-feedback').text(message).prop('hidden', false);
    }

    function clearFeedback() {
        $('#hqp-feedback').empty().prop('hidden', true);
    }

    function setStep(step) {
        currentStep = step;
        clearFeedback();
        $('.hqp-step').stop(true, true).hide();
        $('#hqp-step-' + step).show().find('h3').trigger('focus');
        $('[data-step-indicator]').removeClass('is-active is-complete').removeAttr('aria-current');
        $('[data-step-indicator]').each(function () {
            const current = parseInt($(this).attr('data-step-indicator'), 10);
            if (current < step) $(this).addClass('is-complete');
            if (current === step) $(this).addClass('is-active').attr('aria-current', 'step');
        });

        const wrapperTop = $('#hqp-booking-wrapper').offset();
        if (wrapperTop && window.matchMedia('(max-width: 680px)').matches) {
            window.scrollTo({ top: Math.max(0, wrapperTop.top - 16), behavior: 'smooth' });
        }
    }

    function updateDirectionUI() {
        const direction = $('input[name="route_direction"]:checked').val();
        const $group = $('input[name="route_direction"]').closest('.hqp-radio-options-dark');
        $group.find('.hqp-radio-dark').removeClass('active');
        $('input[name="route_direction"]:checked').closest('.hqp-radio-dark').addClass('active');

        if (direction === 'from_hotel') {
            $('#label-origin').text('Origen · Hotel');
            $('#label-destination').text('Destino');
        } else {
            $('#label-origin').text('Destino · Hotel');
            $('#label-destination').text('Origen');
        }
    }

    function currentRoute() {
        const direction = $('input[name="route_direction"]:checked').val();
        const locationId = String($('#hqp-destination').val() || '').trim();
        const location = locationId && routeLocations[locationId] ? routeLocations[locationId] : null;
        const externalLocation = location && location.address ? String(location.address).trim() : '';
        if (!locationId || !externalLocation || (direction !== 'from_hotel' && direction !== 'to_hotel')) return null;

        if (direction === 'from_hotel') {
            return {
                origin: hotelAddress,
                destination: externalLocation,
                direction: direction,
                locationId: locationId
            };
        }
        return {
            origin: externalLocation,
            destination: hotelAddress,
            direction: direction,
            locationId: locationId
        };
    }

    function responseMessage(response, fallback) {
        if (response && response.data && typeof response.data.message === 'string' && response.data.message) {
            return response.data.message;
        }
        return fallback;
    }

    function ajaxErrorMessage(xhr, fallback) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            return xhr.responseJSON.data.message;
        }
        return fallback;
    }

    function showLoading(message) {
        $('#hqp-vehicles-grid').empty().append(
            $('<div>', { class: 'hqp-loading' })
                .append($('<span>', { class: 'hqp-spinner', 'aria-hidden': 'true' }))
                .append(document.createTextNode(' ' + message))
        );
    }

    function showGridError(message) {
        $('#hqp-vehicles-grid').empty().append($('<div>', { class: 'hqp-error', text: message }));
    }

    function renderVehicles(vehicles) {
        const $grid = $('#hqp-vehicles-grid').empty();
        if (!Array.isArray(vehicles) || !vehicles.length) {
            showGridError('No hay vehículos disponibles para esta selección.');
            return;
        }

        vehicles.forEach(function (vehicle) {
            const id = parseInt(vehicle.id, 10) || 0;
            const capacity = parseInt(vehicle.capacity, 10) || 1;
            const name = String(vehicle.name || 'Vehículo');
            const description = String(vehicle.description || '').trim();
            const price = String(vehicle.price || '0.00');

            const $card = $('<button>', {
                type: 'button',
                class: 'hqp-vehicle-card',
                'data-id': id,
                'data-price': price,
                'data-price-cents': parseInt(vehicle.price_cents, 10),
                'data-name': name,
                'aria-label': 'Seleccionar ' + name + ', €' + price
            });

            const $icon = $('<span>', { class: 'hqp-vehicle-icon', 'aria-hidden': 'true' }).html(
                '<svg viewBox="0 0 24 24" width="28" height="28" focusable="false"><path d="M5 11 6.7 6h10.6l1.7 5H5Zm1.5 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm11 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3ZM19.8 5.4A2.7 2.7 0 0 0 17.3 3.5H6.7a2.7 2.7 0 0 0-2.5 1.9L2 12v7.2c0 .7.6 1.3 1.3 1.3h1.2c.7 0 1.3-.6 1.3-1.3v-.7h12.4v.7c0 .7.6 1.3 1.3 1.3h1.2c.7 0 1.3-.6 1.3-1.3V12l-2.2-6.6Z"/></svg>'
            );

            const $info = $('<span>', { class: 'hqp-vehicle-info' })
                .append($('<strong>', { class: 'hqp-vehicle-name', text: name }));
            if (description) {
                $info.append($('<span>', { class: 'hqp-desc', text: description }));
            }
            $info.append($('<span>', { class: 'hqp-sub', text: 'Hasta ' + capacity + ' pasajeros' }));

            const $price = $('<span>', { class: 'hqp-vehicle-price' })
                .append($('<strong>', { text: '€' + price }))
                .append($('<small>', { text: 'Precio fijo' }));

            $card.append($icon, $info, $price);
            $grid.append($card);
        });
    }

    $('input[name="route_direction"]').on('change', updateDirectionUI);
    updateDirectionUI();

    $form.on('submit', function (event) {
        event.preventDefault();
        if (currentStep === 1) $('#hqp-btn-calculate').trigger('click');
        else if (currentStep === 3) $('#hqp-btn-book').trigger('click');
    });

    // The browser otherwise clicks the first submit button, even when its
    // route step is hidden. Preserve Enter in textareas for multiline notes.
    $form.on('keydown', 'input', function (event) {
        if (event.key === 'Enter' && !event.originalEvent?.isComposing) {
            event.preventDefault();
            $form.trigger('submit');
        }
    });

    $('#hqp-btn-calculate').on('click', function (event) {
        event.preventDefault();

        if (paymentPending || currentStep !== 1 || $(this).prop('disabled')) return;
        clearFeedback();

        const route = currentRoute();
        const date = String($('#hqp-date').val() || '');
        const time = String($('#hqp-time').val() || '');
        const passengers = parseInt($('#hqp-passengers').val(), 10) || 1;

        if (!route) {
            $('#hqp-destination').trigger('focus');
            showFeedback('Selecciona el origen o destino del trayecto.');
            return;
        }
        if (!date || !$('#hqp-date')[0].checkValidity()) {
            $('#hqp-date').trigger('focus');
            showFeedback('Selecciona una fecha válida para el traslado.');
            return;
        }
        if (!time || !$('#hqp-time')[0].checkValidity()) {
            $('#hqp-time').trigger('focus');
            showFeedback('Selecciona la hora del traslado.');
            return;
        }

        const $button = $(this).prop('disabled', true).text('Consultando disponibilidad…');
        bookingData = null;
        window.hqpBookingData = null;
        setStep(2);
        showLoading('Consultando vehículos y tarifas…');

        const version = ++quoteVersion;
        quoteRequest = $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            timeout: 30000,
            data: {
                action: pricingAction,
                hotel_id: hotelId,
                hotel_token: hotelToken,
                passengers: passengers,
                date: date,
                time: time,
                security: nonce
            }
        }).done(function (response) {
            if (version !== quoteVersion) return;
            if (response && response.success) {
                renderVehicles(response.data);
                return;
            }
            showGridError(responseMessage(response, 'No se pudieron consultar los vehículos disponibles.'));
        }).fail(function (xhr, status) {
            if (version !== quoteVersion || status === 'abort') return;
            showGridError(ajaxErrorMessage(xhr, 'No se pudo conectar con el servidor. Inténtalo de nuevo.'));
        }).always(function () {
            if (version !== quoteVersion) return;
            quoteRequest = null;
            $button.prop('disabled', false).text('Ver vehículos y tarifas');
        });
    });

    $(document).on('click', '.hqp-vehicle-card', function () {
        if (currentStep !== 2 || paymentPending || quoteRequest) return;
        const $card = $(this);
        const route = currentRoute();
        if (!route) {
            setStep(1);
            return;
        }

        $('.hqp-vehicle-card').removeClass('selected').attr('aria-pressed', 'false');
        $card.addClass('selected').attr('aria-pressed', 'true');

        bookingData = {
            vehicleId: parseInt($card.attr('data-id'), 10) || 0,
            vehicleName: String($card.attr('data-name') || ''),
            price: String($card.attr('data-price') || '0.00'),
            priceCents: parseInt($card.attr('data-price-cents'), 10),
            date: String($('#hqp-date').val() || ''),
            time: String($('#hqp-time').val() || ''),
            origin: route.origin,
            destination: route.destination,
            passengers: parseInt($('#hqp-passengers').val(), 10) || 1
        };
        window.hqpBookingData = bookingData;

        $('#summary-route').text(bookingData.origin + ' → ' + bookingData.destination);
        $('#summary-date').text(bookingData.date + ' · ' + bookingData.time);
        $('#summary-vehicle').text(bookingData.vehicleName);
        $('#summary-price').text('€' + bookingData.price);

        setStep(3);
    });

    $('#hqp-back-to-step-1').on('click', function (event) {
        event.preventDefault();
        if (paymentPending) return;
        ++quoteVersion;
        if (quoteRequest) quoteRequest.abort();
        quoteRequest = null;
        $('#hqp-btn-calculate').prop('disabled', false).text('Ver vehículos y tarifas');
        bookingData = null;
        window.hqpBookingData = null;
        setStep(1);
    });

    $('#hqp-back-to-step-2').on('click', function (event) {
        event.preventDefault();
        if (paymentPending) return;
        setStep(2);
    });

    $('#hqp-btn-book').on('click', function (event) {
        event.preventDefault();
        if (paymentPending || currentStep !== 3) return;
        clearFeedback();

        const $button = $(this);
        const customerName = String($('#hqp-name').val() || '').trim();
        const customerEmail = String($('#hqp-email').val() || '').trim();
        const customerPhone = String($('#hqp-phone').val() || '').trim();
        const route = currentRoute();

        if (!bookingData || !bookingData.vehicleId || !route) {
            setStep(2);
            showFeedback('Selecciona nuevamente el vehículo para continuar.');
            return;
        }
        if (!customerName) {
            $('#hqp-name').trigger('focus');
            showFeedback('Escribe tu nombre completo.');
            return;
        }
        if (!customerEmail || !$('#hqp-email')[0].checkValidity()) {
            $('#hqp-email').trigger('focus');
            showFeedback('Escribe un email válido.');
            return;
        }
        if (!customerPhone) {
            $('#hqp-phone').trigger('focus');
            showFeedback('Escribe un teléfono de contacto.');
            return;
        }

        paymentPending = true;
        $button.text('Preparando pago seguro…').prop('disabled', true);
        $('#hqp-back-to-step-2').attr('aria-disabled', 'true');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: bookingAction,
                hotel_id: hotelId,
                hotel_token: hotelToken,
                vehicle_id: bookingData.vehicleId,
                quoted_price_cents: bookingData.priceCents,
                route_direction: route.direction,
                route_location: route.locationId,
                // Keep canonical addresses for compatibility/logging; the server
                // rebuilds the route from the hotel and authorized location ID.
                origin: route.origin,
                destination: route.destination,
                date: bookingData.date,
                time: bookingData.time,
                customer_name: customerName,
                customer_email: customerEmail,
                customer_phone: customerPhone,
                flight_number: String($('#hqp-flight').val() || '').trim(),
                notes: String($('#hqp-notes').val() || '').trim(),
                passengers: bookingData.passengers,
                security: nonce
            }
        }).done(function (response) {
            if (!response || !response.success) {
                showFeedback(responseMessage(response, 'No se pudo procesar la reserva.'));
                return;
            }

            const data = response.data || {};
            if (data.redirect) {
                leavingForPayment = true;
                window.location.href = data.redirect;
                return;
            }
            if (!data.url || !data.ds_signature_version || !data.ds_merchant_parameters || !data.ds_signature) {
                showFeedback('La pasarela de pago no devolvió una respuesta válida. Contacta con soporte.');
                return;
            }

            const $paymentForm = $('<form>', { action: data.url, method: 'POST' });
            $paymentForm.append($('<input>', { type: 'hidden', name: 'Ds_SignatureVersion', value: data.ds_signature_version }));
            $paymentForm.append($('<input>', { type: 'hidden', name: 'Ds_MerchantParameters', value: data.ds_merchant_parameters }));
            $paymentForm.append($('<input>', { type: 'hidden', name: 'Ds_Signature', value: data.ds_signature }));
            $('body').append($paymentForm);
            leavingForPayment = true;
            $paymentForm[0].submit();
        }).fail(function (xhr) {
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.code === 'hotel_price_changed') {
                bookingData = null;
                window.hqpBookingData = null;
                setStep(1);
            }
            showFeedback(ajaxErrorMessage(xhr, 'No se pudo confirmar la respuesta del servidor. Consulta con recepción antes de repetir la reserva.'));
        }).always(function () {
            if (leavingForPayment) return;
            paymentPending = false;
            $('#hqp-back-to-step-2').removeAttr('aria-disabled');
            $button.text('Confirmar reserva y pagar').prop('disabled', false);
        });
    });
});
