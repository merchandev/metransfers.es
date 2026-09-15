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

    function setStep(step) {
        $('.hqp-step').stop(true, true).hide();
        $('#hqp-step-' + step).fadeIn(180);
        $('[data-step-indicator]').removeClass('is-active is-complete');
        $('[data-step-indicator]').each(function () {
            const current = parseInt($(this).attr('data-step-indicator'), 10);
            if (current < step) $(this).addClass('is-complete');
            if (current === step) $(this).addClass('is-active');
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
        $('#hqp-btn-calculate').trigger('click');
    });

    $('#hqp-btn-calculate').on('click', function (event) {
        event.preventDefault();

        const route = currentRoute();
        const date = String($('#hqp-date').val() || '');
        const time = String($('#hqp-time').val() || '');
        const passengers = parseInt($('#hqp-passengers').val(), 10) || 1;

        if (!route) {
            $('#hqp-destination').trigger('focus');
            alert('Selecciona el origen o destino del trayecto.');
            return;
        }
        if (!date) {
            $('#hqp-date').trigger('focus');
            alert('Selecciona la fecha del traslado.');
            return;
        }
        if (!time) {
            $('#hqp-time').trigger('focus');
            alert('Selecciona la hora del traslado.');
            return;
        }

        const $button = $(this).prop('disabled', true).text('Consultando disponibilidad…');
        bookingData = null;
        window.hqpBookingData = null;
        setStep(2);
        showLoading('Consultando vehículos y tarifas…');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
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
            if (response && response.success) {
                renderVehicles(response.data);
                return;
            }
            showGridError(responseMessage(response, 'No se pudieron consultar los vehículos disponibles.'));
        }).fail(function (xhr) {
            showGridError(ajaxErrorMessage(xhr, 'No se pudo conectar con el servidor. Inténtalo de nuevo.'));
        }).always(function () {
            $button.prop('disabled', false).text('Ver vehículos y tarifas');
        });
    });

    $(document).on('click', '.hqp-vehicle-card', function () {
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

        window.setTimeout(function () { setStep(3); }, 120);
    });

    $('#hqp-back-to-step-1').on('click', function (event) {
        event.preventDefault();
        bookingData = null;
        window.hqpBookingData = null;
        setStep(1);
    });

    $('#hqp-back-to-step-2').on('click', function (event) {
        event.preventDefault();
        setStep(2);
    });

    $('#hqp-btn-book').on('click', function (event) {
        event.preventDefault();

        const $button = $(this);
        const customerName = String($('#hqp-name').val() || '').trim();
        const customerEmail = String($('#hqp-email').val() || '').trim();
        const customerPhone = String($('#hqp-phone').val() || '').trim();
        const route = currentRoute();

        if (!bookingData || !bookingData.vehicleId || !route) {
            alert('Selecciona nuevamente el vehículo para continuar.');
            setStep(2);
            return;
        }
        if (!customerName) {
            $('#hqp-name').trigger('focus');
            alert('Escribe tu nombre completo.');
            return;
        }
        if (!customerEmail || !$('#hqp-email')[0].checkValidity()) {
            $('#hqp-email').trigger('focus');
            alert('Escribe un email válido.');
            return;
        }
        if (!customerPhone) {
            $('#hqp-phone').trigger('focus');
            alert('Escribe un teléfono de contacto.');
            return;
        }

        $button.text('Preparando pago seguro…').prop('disabled', true);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: bookingAction,
                hotel_id: hotelId,
                hotel_token: hotelToken,
                vehicle_id: bookingData.vehicleId,
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
                alert(responseMessage(response, 'No se pudo procesar la reserva.'));
                return;
            }

            const data = response.data || {};
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (!data.url || !data.ds_signature_version || !data.ds_merchant_parameters || !data.ds_signature) {
                alert('La pasarela de pago no devolvió una respuesta válida. Contacta con soporte.');
                return;
            }

            const $paymentForm = $('<form>', { action: data.url, method: 'POST' });
            $paymentForm.append($('<input>', { type: 'hidden', name: 'Ds_SignatureVersion', value: data.ds_signature_version }));
            $paymentForm.append($('<input>', { type: 'hidden', name: 'Ds_MerchantParameters', value: data.ds_merchant_parameters }));
            $paymentForm.append($('<input>', { type: 'hidden', name: 'Ds_Signature', value: data.ds_signature }));
            $('body').append($paymentForm);
            $paymentForm.trigger('submit');
        }).fail(function (xhr) {
            alert(ajaxErrorMessage(xhr, 'No se pudo conectar con el servidor. Inténtalo de nuevo.'));
        }).always(function () {
            $button.text('Confirmar reserva y pagar').prop('disabled', false);
        });
    });
});
