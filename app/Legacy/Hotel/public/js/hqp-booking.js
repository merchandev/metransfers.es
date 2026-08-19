jQuery(document).ready(function ($) {
    if (!$('#hqp-booking-form').length) return;

    const SEDAN_MAX_PASSENGERS = 3;
    const VAN_MAX_PASSENGERS = 7;

    // Data from hidden fields
    const hotelId = $('#hqp-hotel-id').val();
    // const hotelName = $('#hqp-hotel-name').val();
    const hotelAddress = $('#hqp-hotel-address').val();

    // 1. Handle Direction Toggle
    // -------------------------------------------------------------
    $('input[name="route_direction"]').on('change', function () {
        const direction = $(this).val();

        // Note: #hqp-origin is the Fixed Hotel Input
        // #hqp-destination is the Select Dropdown

        if (direction === 'from_hotel') {
            // Direction: Hotel -> External
            // Left Input (Hotel) is Origin
            // Right Input (Select) is Destination
            $('#label-origin').text('ORIGEN (Hotel)');
            $('#label-destination').text('DESTINO');
        } else {
            // Direction: External -> Hotel
            // Left Input (Hotel) is Destination
            // Right Input (Select) is Origin
            $('#label-origin').text('DESTINO (Hotel)');
            $('#label-destination').text('ORIGEN');
        }

        // Update Radio UI
        $('.hqp-radio-dark').removeClass('active');
        $(this).closest('.hqp-radio-dark').addClass('active');
    });

    // 2. Handle Vehicle Type Toggle (Sedan vs Minivan)
    // -------------------------------------------------------------
    function updatePassengerOptions() {
        var type = $('input[name="vehicle_type"]:checked').val();
        var $select = $('#hqp-passengers');
        var currentVal = parseInt($select.val(), 10) || 1;

        $select.empty();

        var max = (type === 'van') ? VAN_MAX_PASSENGERS : SEDAN_MAX_PASSENGERS;

        for (var i = 1; i <= max; i++) {
            var labelText = (i === 1) ? 'Persona' : 'Personas';
            $select.append('<option value="' + i + '">' + i + ' ' + labelText + '</option>');
        }

        if (currentVal <= max) {
            $select.val(currentVal);
        } else {
            $select.val(max);
        }

        $('input[name="vehicle_type"]').closest('.hqp-radio-dark').removeClass('active');
        $('input[name="vehicle_type"]:checked').closest('.hqp-radio-dark').addClass('active');
    }

    $('input[name="vehicle_type"]').on('change', updatePassengerOptions);
    updatePassengerOptions();


    // 3. Calculate Price & Show Vehicles
    // -------------------------------------------------------------
    $('#hqp-booking-form').on('submit', function (e) {
        e.preventDefault();
    });

    $('#hqp-btn-calculate').on('click', function (e) {
        e.preventDefault();

        const direction = $('input[name="route_direction"]:checked').val();

        // Logic:
        // if from_hotel: Origin=Hotel, Dest=Select
        // if to_hotel: Origin=Select, Dest=Hotel

        const selectedLocation = $('#hqp-destination').val(); // The dropdown value
        if (!selectedLocation) {
            alert('Por favor selecciona un destino/origen.');
            return;
        }

        // For pricing, 'user_address' is the variable part. 
        // The backend logic for 'fixed pricing' uses hotel_id anyway, 
        // so user_address is mostly for distance calc validation if needed, 
        // but here we rely on fixed prices per hotel.
        const userAddress = selectedLocation;

        const date = $('#hqp-date').val();
        const time = $('#hqp-time').val();

        if (!date || !time) {
            alert('Por favor completa fecha y hora.');
            return;
        }

        // Show loading
        $('#hqp-step-1').slideUp();
        $('#hqp-step-2').slideDown();
        $('#hqp-vehicles-grid').html('<div class="hqp-loading">Calculando ruta y consultando tarifas...</div>');

        // AJAX Request
        $.ajax({
            url: wptb_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'hqp_get_fixed_pricing',
                hotel_id: hotelId,
                user_address: userAddress,
                direction: direction,
                passengers: $('#hqp-passengers').val(),
                vehicle_type: $('input[name="vehicle_type"]:checked').val(),
                security: wptb_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    renderVehicles(response.data);
                } else {
                    $('#hqp-vehicles-grid').html('<p class="hqp-error">' + response.data.message + '</p>');
                }
            },
            error: function () {
                $('#hqp-vehicles-grid').html('<p class="hqp-error">Error de conexión. Inténtalo de nuevo.</p>');
            }
        });
    });

    function renderVehicles(vehicles) {
        var html = '';
        var count = 0;

        for (var i = 0; i < vehicles.length; i++) {
            var vehicle = vehicles[i];
            var desc = vehicle.description || '';
            html += '<div class="hqp-vehicle-card" onclick="" data-id="' + vehicle.id + '" data-price="' + vehicle.price + '" data-name="' + vehicle.name + '">' +
                '<div class="hqp-vehicle-icon">' +
                    '<svg viewBox="0 0 24 24" fill="#006597" width="24" height="24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>' +
                '</div>' +
                '<div class="hqp-vehicle-info">' +
                    '<h4>' + vehicle.name + '</h4>' +
                    '<p class="hqp-desc">' + desc + '</p>' +
                    '<p class="hqp-sub">Capacidad: ' + vehicle.capacity + ' pax</p>' +
                '</div>' +
                '<div class="hqp-vehicle-price">€' + vehicle.price + '</div>' +
            '</div>';
            count++;
        }

        if (count === 0) {
            html = '<p class="hqp-error">No hay vehículos disponibles para esta selección.</p>';
        }

        $('#hqp-vehicles-grid').html(html);
    }

    // 4. Vehicle Selection
    // -------------------------------------------------------------
    $(document).on('click', '.hqp-vehicle-card', function () {
        const vehicleId = $(this).data('id');
        const vehicleName = $(this).data('name');
        const price = $(this).data('price');

        const direction = $('input[name="route_direction"]:checked').val();
        const externalLoc = $('#hqp-destination').val();

        // Determine Origin/Dest based on direction
        let origin, destination;
        if (direction === 'from_hotel') {
            origin = hotelAddress;
            destination = externalLoc;
        } else {
            origin = externalLoc;
            destination = hotelAddress;
        }

        // Highlight
        $('.hqp-vehicle-card').removeClass('selected');
        $(this).addClass('selected');

        // Store selection
        window.hqpBookingData = {
            vehicleId: vehicleId,
            vehicleName: vehicleName,
            price: price,
            route: origin + ' ➝ ' + destination,
            date: $('#hqp-date').val(),
            time: $('#hqp-time').val(),
            origin: origin,
            destination: destination
        };

        // Populate Summary
        $('#summary-route').text(window.hqpBookingData.route);
        $('#summary-date').text(window.hqpBookingData.date + ' ' + window.hqpBookingData.time);
        $('#summary-vehicle').text(window.hqpBookingData.vehicleName);
        $('#summary-price').text('€' + window.hqpBookingData.price);

        // Move to Step 3
        setTimeout(function() {
            $('#hqp-step-2').slideUp();
            $('#hqp-step-3').slideDown();
        }, 300);
    });

    // 5. Back Buttons
    // -------------------------------------------------------------
    $('#hqp-back-to-step-1').on('click', function (e) {
        e.preventDefault();
        $('#hqp-step-2').slideUp();
        $('#hqp-step-1').slideDown();
    });

    $('#hqp-back-to-step-2').on('click', function (e) {
        e.preventDefault();
        $('#hqp-step-3').slideUp();
        $('#hqp-step-2').slideDown();
    });

    // 6. Final Booking Submission
    // -------------------------------------------------------------
    $('#hqp-btn-book').on('click', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const customerName = $('#hqp-name').val();
        const customerEmail = $('#hqp-email').val();
        const customerPhone = $('#hqp-phone').val();

        if (!customerName || !customerEmail || !customerPhone) {
            alert('Por favor completa los datos de contacto.');
            return;
        }

        $btn.text('Procesando...').prop('disabled', true);

        // Get final values again to be safe
        const currentDirection = $('input[name="route_direction"]:checked').val();
        const currentExternal = $('#hqp-destination').val();

        let safeOrigin, safeDestination;
        if (currentDirection === 'from_hotel') {
            safeOrigin = hotelAddress;
            safeDestination = currentExternal;
        } else {
            safeOrigin = currentExternal;
            safeDestination = hotelAddress;
        }

        if (!window.hqpBookingData || !window.hqpBookingData.vehicleId) {
            alert('Por favor selecciona un vehículo.');
            $btn.text('Reservar Ahora').prop('disabled', false);
            return;
        }

        const bookingData = {
            action: 'hqp_create_booking',
            hotel_id: hotelId,
            vehicle_id: window.hqpBookingData.vehicleId,
            vehicle_name: window.hqpBookingData.vehicleName,
            price: window.hqpBookingData.price,
            origin: safeOrigin || window.hqpBookingData.origin,
            destination: safeDestination || window.hqpBookingData.destination,
            date: window.hqpBookingData.date,
            time: window.hqpBookingData.time,
            customer_name: customerName,
            customer_email: customerEmail,
            customer_phone: customerPhone,
            flight_number: $('#hqp-flight').val(),
            notes: $('#hqp-notes').val(),
            passengers: $('#hqp-passengers').val(),
            security: wptb_vars.nonce
        };

        $.ajax({
            url: wptb_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: bookingData,
            success: function (response) {
                if (response.success) {
                    const data = response.data; if(data.redirect) { window.location.href = data.redirect; return; }
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

                } else {
                    alert(response.data.message || 'Error al procesar la reserva.');
                    $btn.text('CONFIRMAR RESERVA').prop('disabled', false);
                }
            },
            error: function () {
                alert('Error de conexión. Por favor inténtalo de nuevo.');
                $btn.text('CONFIRMAR RESERVA').prop('disabled', false);
            }
        });
    });

});
