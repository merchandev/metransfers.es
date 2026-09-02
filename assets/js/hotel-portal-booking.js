(function () {
    'use strict';
    var root = document.querySelector('[data-hotel-booking]');
    if (!root || !window.mtHotelBooking) return;
    var form = root.querySelector('[data-booking-form]');
    var calculate = root.querySelector('[data-calculate]');
    var status = root.querySelector('[data-quote-status]');
    var results = root.querySelector('[data-results]');
    var vehicles = root.querySelector('[data-vehicles]');
    var routeSummary = root.querySelector('[data-route-summary]');
    var finalSummary = root.querySelector('[data-final-summary]');
    var vehicleId = root.querySelector('[data-vehicle-id]');
    var quotedPrice = root.querySelector('[data-quoted-price]');
    var submit = root.querySelector('[data-submit]');
    var returnFields = root.querySelector('[data-return-fields]');
    var quoteFields = ['booking_date', 'booking_time', 'origin', 'destination', 'trip_type', 'return_date', 'return_time', 'return_origin', 'return_destination', 'passengers', 'suitcases', 'carry_ons'];

    function text(value) { var node = document.createElement('span'); node.textContent = value == null ? '' : String(value); return node.innerHTML; }
    function money(cents) { return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(Number(cents) / 100); }
    function message(value, error) { status.textContent = value; status.hidden = !value; status.className = 'mt-hnb-status ' + (error ? 'mt-hnb-status--error' : 'mt-hnb-status--info'); }
    function invalidate(showMessage) {
        vehicleId.value = ''; quotedPrice.value = ''; submit.disabled = true;
        finalSummary.textContent = 'Calcula la ruta y selecciona un vehículo.';
        vehicles.querySelectorAll('.is-selected').forEach(function (node) { node.classList.remove('is-selected'); });
        if (showMessage && !results.hidden) message('Los datos cambiaron. Vuelve a calcular la tarifa.', false);
    }
    function selectedTrip() { var selected = form.querySelector('[name="trip_type"]:checked'); return selected ? selected.value : 'one_way'; }
    function toggleReturn() {
        var round = selectedTrip() === 'round_trip'; returnFields.hidden = !round;
        returnFields.querySelectorAll('input').forEach(function (input) { input.required = round; });
        if (round) {
            if (!form.elements.return_origin.value) form.elements.return_origin.value = form.elements.destination.value;
            if (!form.elements.return_destination.value) form.elements.return_destination.value = form.elements.origin.value;
        }
        invalidate(true);
    }
    form.querySelectorAll('[name="trip_type"]').forEach(function (input) { input.addEventListener('change', toggleReturn); });
    quoteFields.forEach(function (name) { form.querySelectorAll('[name="' + name + '"]').forEach(function (input) { input.addEventListener('change', function () { if (name !== 'trip_type') invalidate(true); }); }); });
    root.querySelectorAll('[data-hotel-fill]').forEach(function (button) { button.addEventListener('click', function () { var input = form.elements[button.getAttribute('data-hotel-fill')]; input.value = window.mtHotelBooking.hotelAddress || ''; input.dispatchEvent(new Event('change')); }); });

    function initPlaces() {
        if (!window.google || !google.maps || !google.maps.places) return;
        ['origin', 'destination', 'return_origin', 'return_destination'].forEach(function (name) {
            var input = form.elements[name]; if (!input) return;
            var autocomplete = new google.maps.places.Autocomplete(input, { componentRestrictions: { country: 'es' }, fields: ['formatted_address', 'geometry', 'name'] });
            autocomplete.addListener('place_changed', function () { var place = autocomplete.getPlace(); if (place.formatted_address) input.value = place.formatted_address; input.dispatchEvent(new Event('change')); });
        });
    }
    initPlaces();

    calculate.addEventListener('click', function () {
        if (!form.reportValidity()) return;
        calculate.disabled = true; message('Calculando ruta y tarifas…', false); invalidate(false);
        var data = new FormData(); data.append('action', 'mt_hotel_booking_quote'); data.append('security', window.mtHotelBooking.nonce);
        quoteFields.forEach(function (name) { var field = form.elements[name]; if (field) data.append(name, field.value); });
        fetch(window.mtHotelBooking.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data }).then(function (response) { return response.json(); }).then(function (response) {
            if (!response.success) throw new Error(response.data && response.data.message ? response.data.message : 'No se pudo calcular la tarifa.');
            var payload = response.data; results.hidden = false; message('', false);
            routeSummary.innerHTML = '<strong>' + text(payload.route.origin) + ' → ' + text(payload.route.destination) + '</strong><div class="mt-hnb-route-meta">' + text(payload.route.total_distance_km) + ' km · ' + text(payload.route.duration_minutes) + ' min</div>';
            vehicles.innerHTML = '';
            payload.vehicles.forEach(function (vehicle) {
                var button = document.createElement('button'); button.type = 'button'; button.className = 'mt-hnb-vehicle-card' + (vehicle.available ? '' : ' is-unavailable'); button.disabled = !vehicle.available;
                button.innerHTML = '<span class="mt-hnb-vehicle-info"><strong>' + text(vehicle.name) + '</strong><span class="mt-hnb-vehicle-meta">Hasta ' + text(vehicle.capacity) + ' pasajeros · ' + text(vehicle.luggage_capacity) + ' maletas</span></span><span class="mt-hnb-vehicle-price">' + money(vehicle.price_cents) + '</span>';
                button.addEventListener('click', function () { vehicles.querySelectorAll('.is-selected').forEach(function (node) { node.classList.remove('is-selected'); }); button.classList.add('is-selected'); vehicleId.value = vehicle.id; quotedPrice.value = vehicle.price_cents; submit.disabled = false; finalSummary.innerHTML = '<strong>' + text(vehicle.name) + '</strong><div>' + text(payload.route.origin) + ' → ' + text(payload.route.destination) + '</div><div class="mt-hnb-quote-total"><span>Total</span><strong>' + money(vehicle.price_cents) + '</strong></div>'; });
                vehicles.appendChild(button);
            });
        }).catch(function (error) { results.hidden = true; message(error.message, true); }).finally(function () { calculate.disabled = false; });
    });
}());
