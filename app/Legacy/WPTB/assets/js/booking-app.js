jQuery(document).ready(function ($) {
    'use strict';

    const bookingStrings = (typeof wptb_vars !== 'undefined' && wptb_vars.strings) ? wptb_vars.strings : {};

    function t(key, fallback) {
        return bookingStrings[key] || fallback;
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function track(eventName, parameters) {
        if (typeof window.mtBookingTrack === 'function') {
            window.mtBookingTrack(eventName, parameters || {});
        }
    }

    // ===== EARLY REDIRECT: If URL params from BTT and not on vehicle page, redirect there =====
    (function redirectBTTParamsToVehiclePage() {
        const params = new URLSearchParams(window.location.search);
        const hasOrigin = params.get('origin') || params.get('login');
        const hasDestination = params.get('destination');
        const hasDate = params.get('date');
        const hasTime = params.get('time');
        const hasSource = params.get('source') === 'BTT';

        if (hasOrigin && hasDestination && hasDate && hasTime && hasSource) {
            // Check if we're NOT already on the vehicle selection page
            const isOnVehiclePage = document.getElementById('wptb-step-2') !== null
                && document.getElementById('wptb-step-1') === null;

            if (!isOnVehiclePage) {
                const vehiclesUrl = (typeof wptb_vars !== 'undefined' && wptb_vars.vehicles_url)
                    ? wptb_vars.vehicles_url
                    : '/seleccionar-vehiculo/';

                const targetUrl = vehiclesUrl + '?' + params.toString();
                window.location.replace(targetUrl);
            }
        }
    })();

    // ===== GLOBAL STATE =====
    let map, directionsService, directionsRenderer;
    let bookingData = {
        date: '',
        time: '',
        origin: '',
        destination: '',
        distance_km: 0,
        duration_minutes: 0,
        duration_text: '',
        vehicle_id: 0,
        vehicle_name: '',
        trip_type: 'one_way',
        price: 0,
        price_cents: 0
    };

    function vehicleQuoteRequest(data) {
        return {
            language: (typeof wptb_vars !== 'undefined' && wptb_vars.language) ? wptb_vars.language : 'es',
            date: data.date || '',
            time: data.time || '',
            origin: data.origin || '',
            destination: data.destination || '',
            trip_type: data.trip_type || 'one_way',
            return_date: data.return_date || '',
            return_time: data.return_time || '',
            return_origin: data.return_origin || '',
            return_destination: data.return_destination || '',
            passengers: data.passengers || 1,
            suitcases: data.suitcases || 0,
            carry_ons: data.carry_ons || 0
        };
    }

    function applyServerRoute(route, target) {
        if (!route || !target) return;
        target.distance_km = Number.parseFloat(route.total_distance_km || route.distance_km || 0);
        target.duration_minutes = Number.parseInt(route.duration_minutes || 0, 10);
        target.duration_text = target.duration_minutes > 0 ? target.duration_minutes + ' min' : '';
        target.quote_verified = true;
    }

    function vehicleQuotesFromResponse(response) {
        if (!response || !response.success || !response.data) return [];
        applyServerRoute(response.data.route, bookingData);
        return Array.isArray(response.data.vehicles) ? response.data.vehicles : [];
    }

    // ===== ORIGIN & DESTINATION RESTRICTIONS =====
    const DESTINATION_COUNTRIES = ['ES', 'PT', 'FR', 'CH', 'BE', 'DE', 'IT', 'NL', 'AT', 'HR', 'SI', 'PL', 'LU', 'AD'];

    // Validates that origin is within Catalunya (province of Barcelona area)
    function validateOriginArea(place) {
        if (!place || !place.address_components) return false;
        let isCatalunya = false;
        let isBarcelona = false;
        for (let comp of place.address_components) {
            if (comp.types.includes('administrative_area_level_1')) {
                if (comp.short_name === 'CT' || comp.long_name.includes('Catalunya') || comp.long_name.includes('Catalonia')) {
                    isCatalunya = true;
                }
            }
            if (comp.types.includes('administrative_area_level_2')) {
                if (comp.long_name.includes('Barcelona')) {
                    isBarcelona = true;
                }
            }
        }
        return (isCatalunya || isBarcelona);
    }

    // ===== GLOBAL HELPERS (Defined early to avoid crash issues) =====
    window.selectVehicle = function (id) {
        const vehicle = window.vehicleMap ? window.vehicleMap[id] : null;
        if (vehicle) {
            $('.vehicle-card').removeClass('selected');
            $(`[data-vehicle-id="${id}"]`).addClass('selected');

            bookingData.vehicle = vehicle; // Guardar el objeto completo para validaciones
            bookingData.vehicle_id = vehicle.id;
            bookingData.vehicle_name = vehicle.name;
            useServerVehiclePrice(vehicle);
        } else {
            console.error('Vehicle data not found for ID:', id);
        }
    };

    // Function to Initialize Form Logic (Supports Suffix for Modal)
    function initBookingForm(suffix) {
        const dateId = '#wptb-date' + suffix;
        const originId = '#wptb-origin' + suffix;
        const destId = '#wptb-destination' + suffix;
        const searchFormId = '#wptb-search-form' + suffix;
        const locBtnId = 'wptb-location-btn' + suffix; // ID for injection, no hash

        // Set Min Date
        if (typeof wptb_vars !== 'undefined' && wptb_vars.min_date) {
            $(dateId).attr('min', wptb_vars.min_date);
        }

        // Initialize Autocomplete with Retry Logic
        function initAutocomplete() {
            if (typeof google !== 'undefined' && google.maps && google.maps.places) {

                // ORIGIN: Restricted to Barcelona province bounds
                const originOptions = {
                    fields: ["formatted_address", "geometry", "name", "address_components"],
                    bounds: new google.maps.LatLngBounds(
                        new google.maps.LatLng(41.16, 1.63), // SW Barcelona province
                        new google.maps.LatLng(42.33, 2.83)  // NE Barcelona province
                    ),
                    strictBounds: true,
                    componentRestrictions: { country: 'ES' }
                };

                // DESTINATION: All accessible European countries by road
                const destOptions = {
                    fields: ["formatted_address", "geometry", "name", "address_components"],
                    bounds: new google.maps.LatLngBounds(
                        new google.maps.LatLng(36.0, -10.0), // SW Europe
                        new google.maps.LatLng(55.0, 25.0)   // NE Europe
                    ),
                    strictBounds: true
                };

                const originInput = document.querySelector(originId);
                const destInput = document.querySelector(destId);

                if (originInput) {
                    const originAutocomplete = new google.maps.places.Autocomplete(originInput, originOptions);
                    originAutocomplete.addListener('place_changed', () => {
                        const place = originAutocomplete.getPlace();
                        if (place && place.address_components) {
                            if (!validateOriginArea(place)) {
                                alert(t('origin_restriction', 'Lo sentimos, solo operamos transfers con origen en el área de Barcelona.'));
                                originInput.value = '';
                            }
                        }
                    });
                }

                if (destInput) {
                    const destAutocomplete = new google.maps.places.Autocomplete(destInput, destOptions);
                    destAutocomplete.addListener('place_changed', () => {
                        const place = destAutocomplete.getPlace();
                        if (place && place.address_components) {
                            let isAllowed = false;
                            for (let comp of place.address_components) {
                                if (comp.types.includes('country') && DESTINATION_COUNTRIES.includes(comp.short_name.toUpperCase())) {
                                    isAllowed = true;
                                    break;
                                }
                            }
                            if (!isAllowed) {
                                alert(t('destination_restriction', 'El destino debe estar dentro de los países europeos con cobertura.'));
                                destInput.value = '';
                            }
                        }
                    });
                }
            } else {
                // Check if script is even in DOM
                const scriptExists = document.querySelector('script[src*="maps.googleapis.com"]');
                if (!scriptExists) {
                    console.error('❌ CRITICAL: Google Maps API Script NOT found in DOM. Check API Key configuration.');
                } else {
                    console.warn('⚠️ Google Maps script found but object not ready. Billing issue? Retrying...');
                }
                setTimeout(initAutocomplete, 500);
            }
        }

        initAutocomplete();

        // Inject Geolocation Button
        const $originWrapper = $(originId).parent();
        if ($originWrapper.length && $('#' + locBtnId).length === 0) {
            $originWrapper.addClass('wptb-origin-wrapper');

            const locBtn = `
                <button type="button" id="${locBtnId}" class="wptb-geolocation-btn" title="Usar mi ubicación actual">
                    <span class="dashicons dashicons-location"></span>
                </button>
            `;
            $originWrapper.append(locBtn);
        }

        // Geolocation Click Handler
        $(document).on('click', '#' + locBtnId, function () {
            const $btn = $(this);
            const $icon = $btn.find('span');

            if (!navigator.geolocation) {
                alert(t('geolocation_unsupported', 'Tu navegador no soporta geolocalización.'));
                return;
            }

            $icon.removeClass('dashicons-location').addClass('dashicons-update spin');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                        $icon.removeClass('dashicons-update spin').addClass('dashicons-location');
                        if (status === "OK" && results[0]) {
                            if (!validateOriginArea(results[0])) {
                                alert(t('origin_restriction', 'Lo sentimos, solo operamos transfers con origen en el área de Barcelona.'));
                                $(originId).val('');
                                $(originId).focus();
                                return;
                            }
                            $(originId).val(results[0].formatted_address);
                            // Trigger input event for validation/maps
                            const event = new Event('input', { bubbles: true });
                            if (document.querySelector(originId)) {
                                document.querySelector(originId).dispatchEvent(event);
                            }
                        } else {
                            alert(t('geocode_error', 'No se pudo determinar la dirección. Por favor ingrésala manualmente.'));
                            $(originId).focus();
                        }
                    });
                },
                (error) => {
                    $icon.removeClass('dashicons-update spin').addClass('dashicons-location');
                    console.error("Error Geo:", error);
                    alert(t('location_error', 'Permiso denegado o error de ubicación. Por favor escribe tu origen.'));
                    $(originId).focus();
                }
            );
        });

        // Search Form Submit
        $(document).on('submit', searchFormId, function (e) {
            e.preventDefault();
            const date = $(dateId).val();
            const time = $('#wptb-time' + suffix).val();
            const origin = $(originId).val();
            const destination = $(destId).val();

            // Basic validation
            if (!date || !time || !origin || !destination) {
                alert(t('complete_all_fields', 'Por favor completa todos los campos.'));
                return;
            }

            track('booking_start', { booking_flow: suffix === '-modal' ? 'modal' : 'main' });
            track('route_search', {
                booking_flow: suffix === '-modal' ? 'modal' : 'main',
                trip_type: bookingData.trip_type
            });

            // Save to object
            bookingData.date = date;
            bookingData.time = time;
            bookingData.origin = origin;
            bookingData.destination = destination;

            // Check if this is modal form
            if (suffix === '-modal') {
                // Modal flow: Calculate and show vehicles INSIDE modal
                calculateRouteForModal();
            } else {
                // Main form flow: Calculate route normally
                calculateRoute(suffix);
            }
        });
    }

    // Calculate route specifically for modal (shows vehicles inside modal)
    function calculateRouteForModal() {
        if (typeof google === 'undefined') return;

        if (!directionsService) directionsService = new google.maps.DirectionsService();

        const request = {
            origin: bookingData.origin,
            destination: bookingData.destination,
            travelMode: 'DRIVING'
        };

        const $btn = $('#wptb-search-form-modal button[type="submit"]');
        $btn.prop('disabled', true).text(t('calculating', 'Calculando...'));

        directionsService.route(request, function (result, status) {
            $btn.prop('disabled', false).text(t('search_vehicles', 'Buscar vehículos'));

            if (status === 'OK') {
                const route = result.routes[0];
                const leg = route.legs[0];

                bookingData.distance_km = (leg.distance.value / 1000).toFixed(1);
                bookingData.duration_minutes = Math.round(leg.duration.value / 60);
                bookingData.duration_text = leg.duration.text;

                // Switch to Step 2 INSIDE modal
                $('#wptb-modal-step-1').hide();
                $('#wptb-modal-step-2').fadeIn();

                // Load vehicles into modal
                loadVehiclesIntoModal();

            } else {
                track('booking_error', { error_type: 'route_calculation' });
                alert(t('route_error', 'No se pudo calcular la ruta. Verifica el origen y el destino.'));
            }
        });
    }

    // Load vehicles into modal grid
    function loadVehiclesIntoModal() {
        $('#wptb-modal-vehicles-grid').html('<div class="loading-spinner">' + escapeHtml(t('loading_vehicles', 'Buscando vehículos...')) + '</div>');

        if (typeof wptb_vars === 'undefined') {
            console.error('WPTB Vars missing');
            return;
        }

        $.ajax({
            url: wptb_vars.ajax_url,
            type: 'POST',
            data: Object.assign({
                action: 'wptb_get_vehicles',
                security: wptb_vars.nonce
            }, vehicleQuoteRequest(bookingData)),
            success: function (response) {
                const vehicles = vehicleQuotesFromResponse(response);
                if (vehicles.length > 0) {
                    displayVehiclesInModal(vehicles);
                } else {
                    track('booking_error', { error_type: 'no_vehicles' });
                    $('#wptb-modal-vehicles-grid').html('<p class="mt-inline-notice">' + escapeHtml(t('no_vehicles', 'No se encontraron vehículos disponibles.')) + '</p>');
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                track('booking_error', { error_type: 'vehicle_request' });
                $('#wptb-modal-vehicles-grid').html('<p class="mt-inline-notice mt-inline-notice--error">' + escapeHtml(t('vehicle_load_error', 'Error al cargar los vehículos.')) + '</p>');
            }
        });
    }

    // Display vehicles as small buttons in modal
    function displayVehiclesInModal(vehicles) {
        let html = '';

        vehicles.forEach(function (vehicle) {
            const imageUrl = escapeHtml(vehicle.image_url || vehicle.image || '');
            const vehicleName = escapeHtml(vehicle.name || '');
            html += `
                <div class="wptb-modal-vehicle-btn" data-vehicle-id="${vehicle.id}">
                    <div class="vehicle-icon">
                        ${imageUrl ? `<img src="${imageUrl}" alt="${vehicleName}">` : '<span class="mt-vehicle-placeholder">🚗</span>'}
                    </div>
                    <div class="vehicle-info-compact">
                        <strong>${vehicleName}</strong>
                        <span class="vehicle-capacity">👥 ${vehicle.capacity} pax</span>
                        <span class="vehicle-price-compact">${escapeHtml(t('price', 'Precio'))} €${Number.parseFloat(vehicle.price).toFixed(2)}</span>
                    </div>
                </div>
            `;
        });

        $('#wptb-modal-vehicles-grid').html(html);

        // Store vehicle data
        window.modalVehicleMap = {};
        vehicles.forEach(v => window.modalVehicleMap[v.id] = v);

        // Handle vehicle selection in modal
        $(document).off('click', '.wptb-modal-vehicle-btn').on('click', '.wptb-modal-vehicle-btn', function () {
            const id = $(this).data('vehicle-id');
            const vehicle = window.modalVehicleMap[id];

            if (vehicle) {
                $('.wptb-modal-vehicle-btn').removeClass('selected');
                $(this).addClass('selected');

                // Use the authoritative price returned by the server.
                bookingData.vehicle = vehicle; // Guardar el objeto entero
                bookingData.vehicle_id = vehicle.id;
                bookingData.vehicle_name = vehicle.name;
                bookingData.price = Number.parseFloat(vehicle.price);
                bookingData.price_cents = Number.parseInt(vehicle.price_cents, 10);

                track('vehicle_select', {
                    vehicle_id: vehicle.id,
                    vehicle_name: vehicle.name,
                    value: bookingData.price,
                    currency: 'EUR'
                });

                // Save and redirect
                sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));

                const detailsUrl = (typeof wptb_vars !== 'undefined' && wptb_vars.details_url)
                    ? wptb_vars.details_url
                    : '/reservas-metransfers/';

                setTimeout(function () {
                    window.location.href = detailsUrl;
                }, 100);
            }
        });
    }

    // Initialize Main Form
    initBookingForm('');

    // Initialize Modal Form
    initBookingForm('-modal');

    // ===== CAROUSEL & MODAL LOGIC =====

    // CRITICAL: Ensure modal is hidden on page load
    $(document).ready(function () {
        $('#wptb-booking-modal').hide();
    });

    // ===== DIRECTION TOGGLE HANDLER =====
    $(document).on('click', '.mtfs-direction-btn', function () {
        const direction = $(this).data('direction');

        // Update toggle buttons
        $('.mtfs-direction-btn').removeClass('active');
        $(this).addClass('active');

        // Update all slide cards
        $('.mtfs-slide').attr('data-direction', direction);

        // Update direction text
        if (direction === 'from-barcelona') {
            $('.mtfs-slide-direction').text(t('from_barcelona', 'Desde Barcelona'));
        } else {
            $('.mtfs-slide-direction').text(t('to_barcelona', 'Hacia Barcelona'));
        }

    });

    // Open Modal on Carousel Click
    $(document).off('click', '.mtfs-slide'); // Remove previous handlers
    $(document).on('click', '.mtfs-slide', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();


        const destinationName = $(this).data('destination');
        const tripDirection = $(this).data('direction'); // 'from-barcelona' or 'to-barcelona'

        const $modal = $('#wptb-booking-modal');
        const $originInput = $('#wptb-origin-modal');
        const $displayInput = $('#wptb-destination-display-modal');
        const $specificInput = $('#wptb-destination-modal');
        const $regionContext = $('#wptb-region-context-modal');

        if ($modal.length === 0) {
            console.error('❌ Modal not found');
            return false;
        }

        // Open Modal with CSS (Clean force hide first)
        $modal.attr('style', '');
        $modal.css('display', 'flex');

        // BIDIRECTIONAL LOGIC: Switch origin/destination based on direction
        if (tripDirection === 'to-barcelona') {
            // Trip TO Barcelona: Origin = Destination, Destination = Barcelona
            $originInput.val(destinationName);
            $originInput.prop('readonly', false);

            $displayInput.val('Barcelona, España');
            $regionContext.val('Barcelona, España');
            $specificInput.val('');
            $specificInput.attr('placeholder', 'Ej: Calle Principal 123, Barcelona');

            setTimeout(() => $specificInput.focus(), 500);
        } else {
            // Trip FROM Barcelona: Origin = Barcelona, Destination = Selected City
            $originInput.val('Barcelona, España');
            $originInput.prop('readonly', false);

            $displayInput.val(destinationName);
            $regionContext.val(destinationName);
            $specificInput.val('');
            $specificInput.attr('placeholder', 'Ej: Calle Principal 123, ' + destinationName);

            setTimeout(() => $specificInput.focus(), 500);
        }

        return false;
    });

    // Close Modal - Direct binding after DOM ready
    $(document).ready(function () {
        // X button close - NUCLEAR APPROACH
        $(document).on('click', '#wptb-modal-close', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $modal = $('#wptb-booking-modal');
            $modal.hide();

            $('#wptb-modal-step-2').hide();
            $('#wptb-modal-step-1').show();
        });

        // Overlay click close - NUCLEAR APPROACH
        $(document).on('click', '#wptb-booking-modal', function (e) {
            if (e.target.id === 'wptb-booking-modal') {

                const $modal = $(this);
                $modal.hide();

                $('#wptb-modal-step-2').hide();
                $('#wptb-modal-step-1').show();
            }
        });
    });

    // Modal Back Button
    $(document).on('click', '#wptb-modal-back', function (e) {
        e.preventDefault();
        $('#wptb-modal-step-2').hide();
        $('#wptb-modal-step-1').fadeIn();
    });

    // Trip type toggle for modal
    $(document).on('click', '.trip-type-btn-modal', function () {
        $('.trip-type-btn-modal').removeClass('active');
        $(this).addClass('active');
        bookingData.trip_type = $(this).data('type');
        // Reload vehicles with new trip type
        if ($('#wptb-modal-step-2').is(':visible')) {
            loadVehiclesIntoModal();
        }
    });

    // Handle Route Calculation
    function calculateRoute() {
        processCalculation();
    }

    // Initialize Directions Service globally if not already
    // Removed unsafe eager init to avoid crashes if Google API isn't ready.
    // It is initialized lazily in processCalculation() and initRouteMap()

    function processCalculation() {
        if (typeof google === 'undefined') return;

        // Ensure Directions Service exists
        if (!directionsService) directionsService = new google.maps.DirectionsService();

        const request = {
            origin: bookingData.origin,
            destination: bookingData.destination,
            travelMode: 'DRIVING'
        };

        const $btn = $('button[type="submit"]'); // Generic selector for submit btn
        $btn.prop('disabled', true).text(t('calculating', 'Calculando...'));

        directionsService.route(request, function (result, status) {
            $btn.prop('disabled', false).text(t('search_vehicles', 'Buscar vehículos'));

            if (status === 'OK') {
                const route = result.routes[0];
                const leg = route.legs[0];

                bookingData.distance_km = (leg.distance.value / 1000).toFixed(1);
                bookingData.duration_minutes = Math.round(leg.duration.value / 60);
                bookingData.duration_text = leg.duration.text;

                // Guardar en sessionStorage para que la página de destino lo lea
                sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));

                let vehiclesUrl;
                if (typeof wptb_vars !== 'undefined' && wptb_vars.vehicles_url) {
                    vehiclesUrl = wptb_vars.vehicles_url;
                } else {
                    vehiclesUrl = '/seleccionar-vehiculo/';
                }
                window.location.href = vehiclesUrl;

            } else {
                track('booking_error', { error_type: 'route_calculation' });
                alert(t('route_error', 'No se pudo calcular la ruta. Verifica el origen y el destino.'));
            }
        });
    }

    // ===== BTT OVERLAY REMOVER =====
    // The metransfers.es theme injects a .btt-global-loader overlay and hides #page
    // when source=BTT is present. This overlay is never removed by the theme,
    // so the plugin must remove it once content is ready.
    function hideBTTLoader() {
        const loader = document.querySelector('.btt-global-loader');
        const page = document.getElementById('page');
        if (loader) {
            loader.style.transition = 'opacity 0.4s ease';
            loader.style.opacity = '0';
            setTimeout(function () {
                loader.style.setProperty('display', 'none', 'important');
            }, 420);
        }
        if (page) {
            page.style.setProperty('display', 'block', 'important');
        }
    }

    // Function to Load Vehicles (AJAX)
    function loadVehicles() {
        $('#vehicles-grid').html('<div class="loading-spinner">' + escapeHtml(t('loading_vehicles', 'Buscando vehículos...')) + '</div>');

        // Check vars
        if (typeof wptb_vars === 'undefined') {
            console.error('WPTB Vars missing');
            hideBTTLoader(); // Still show page even if vars are missing
            return;
        }

        $.ajax({
            url: wptb_vars.ajax_url,
            type: 'POST',
            data: Object.assign({
                action: 'wptb_get_vehicles',
                security: wptb_vars.nonce
            }, vehicleQuoteRequest(bookingData)),
            success: function (response) {
                hideBTTLoader(); // Always reveal page once we have a response
                const vehicles = vehicleQuotesFromResponse(response);
                if (vehicles.length > 0) {
                    displayVehicles(vehicles);
                } else {
                    displayNoVehicles();
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                hideBTTLoader();
                track('booking_error', { error_type: 'vehicle_request' });
                $('#vehicles-grid').html('<p class="mt-inline-notice mt-inline-notice--error">' + escapeHtml(t('vehicle_load_error', 'Error al cargar los vehículos.')) + '</p>');
            }
        });
    }

    function displayNoVehicles() {
        track('booking_error', { error_type: 'no_vehicles' });
        const $message = $('<div>', { class: 'mt-empty-state' });
        $message.append('<span class="dashicons dashicons-warning mt-empty-state__icon"></span>');
        $message.append($('<p>').text(t('no_vehicles', 'No se encontraron vehículos disponibles.')));
        $('#vehicles-grid').empty().append($message);
    }

    function displayVehicles(vehicles) {
        let html = '';

        vehicles.forEach(function (vehicle) {
            const displayPrice = Number.parseFloat(vehicle.price || 0);
            const formattedPrice = Number.isInteger(displayPrice) ? displayPrice : displayPrice.toFixed(2);

            html += `
                <div class="vehicle-card mt-vehicle-card" data-vehicle-id="${vehicle.id}">
                    <div class="vehicle-image mt-vehicle-card__image">
                        <img src="${escapeHtml(vehicle.image || '')}" alt="${escapeHtml(vehicle.name || '')}">
                    </div>
                    <div class="vehicle-info mt-vehicle-card__body">
                        <div>
                            <h3>${escapeHtml(vehicle.name || '')}</h3>
                            
                            <div class="vehicle-features">
                                <span>
                                    <span class="dashicons dashicons-groups"></span>
                                    ${vehicle.capacity} pax
                                </span>
                            </div>
                        </div>

                        <div class="mt-vehicle-card__footer">
                            <div class="vehicle-price-preview">
                                <span class="price-label">${escapeHtml(t('price', 'Precio'))}</span>
                                <span class="price-value">€${formattedPrice}</span>
                            </div>

                            <button type="button" class="select-vehicle-btn mt-button mt-button--primary">
                                ${escapeHtml(t('select', 'Seleccionar'))}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#vehicles-grid').html(html);

        // Store vehicle data map for easy access
        window.vehicleMap = {};
        vehicles.forEach(v => window.vehicleMap[v.id] = v);
    }

    // ===== TRIP TYPE TOGGLE =====
    $(document).on('click', '.trip-type-btn', function () {
        $('.trip-type-btn').removeClass('active');
        $(this).addClass('active');
        const newTripType = $(this).data('type');

        if (bookingData.trip_type !== newTripType) {
            bookingData.trip_type = newTripType;
            loadVehicles(); // Reload vehicles to update prices
        }
    });

    // ===== SELECT VEHICLE BTN HANDLER =====
    $(document).on('click', '.select-vehicle-btn', function (e) {
        e.stopPropagation(); // Prevent bubbling to card onclick
        const $card = $(this).closest('.vehicle-card');
        const id = $card.data('vehicle-id');
        window.selectVehicle(id);
    });

    function useServerVehiclePrice(vehicle) {
        const price = Number.parseFloat(vehicle.price);
        if (!Number.isFinite(price) || price <= 0) {
            track('booking_error', {error_type: 'invalid_server_price'});
            alert(t('invalid_server_price', 'No se pudo calcular un precio válido para la reserva.'));
            return;
        }

        bookingData.price = price;
        bookingData.price_cents = Number.parseInt(vehicle.price_cents, 10);
        completeVehicleSelection(vehicle);
    }

    function completeVehicleSelection(vehicle) {

        track('vehicle_select', {
            vehicle_id: vehicle.id,
            vehicle_name: vehicle.name,
            value: bookingData.price,
            currency: 'EUR'
        });

        // Save to sessionStorage
        sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));


        // Redirect to details page with fallback
        let detailsUrl;

        if (typeof wptb_vars !== 'undefined' && wptb_vars.details_url) {
            detailsUrl = wptb_vars.details_url;
        } else {
            // Fallback URL
            detailsUrl = '/reservas-metransfers/';
            console.warn('⚠️ wptb_vars not available, using fallback URL');
        }


        // Small delay to ensure sessionStorage is saved
        setTimeout(function () {
            window.location.href = detailsUrl;
        }, 100);
    }

    // ===== UPDATE SUMMARY =====
    function updateSummary() {
        $('#summary-vehicle').text(bookingData.vehicle_name);
        const tripLabels = {
            'one_way': t('one_way', 'Solo ida'),
            'round_trip': t('round_trip', 'Ida y vuelta'),
            'return': t('return_trip', 'Vuelta')
        };
        $('#summary-trip-type').text(tripLabels[bookingData.trip_type] || t('one_way', 'Solo ida'));
        $('#summary-origin').text(bookingData.origin);
        $('#summary-destination').text(bookingData.destination);
        $('#summary-distance').text(bookingData.distance_km + ' km');
        $('#summary-duration').text(bookingData.duration_text);
        $('#summary-price').text('€' + bookingData.price.toFixed(2));

        initRouteMap();
    }

    // ===== GOOGLE MAPS ROUTE (SUMMARY) =====
    function initRouteMap() {
        if (typeof google === 'undefined') {
            setTimeout(initRouteMap, 500);
            return;
        }

        const mapElement = document.getElementById('route-map');
        if (!mapElement) {
            console.error('❌ Route Map element #route-map not found in DOM');
            return;
        }

        // Ensure map and renderer exist
        if (!map) {
            map = new google.maps.Map(mapElement, {
                zoom: 7,
                center: { lat: 40.4168, lng: -3.7038 },
                disableDefaultUI: false, // User requested standard controls
                zoomControl: true,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true
            });
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: false,
                polylineOptions: {
                    strokeColor: '#FF8C00',
                    strokeWeight: 5
                }
            });
        }

        // Use existing directionsService
        if (!directionsService) directionsService = new google.maps.DirectionsService();

        directionsService.route(
            {
                origin: bookingData.origin,
                destination: bookingData.destination,
                travelMode: 'DRIVING'
            },
            function (response, status) {
                if (status === 'OK') {
                    directionsRenderer.setDirections(response);
                    // Double resize trigger: immediate + delayed for containers that were hidden
                    google.maps.event.trigger(map, 'resize');
                    setTimeout(function () {
                        google.maps.event.trigger(map, 'resize');
                    }, 300);
                } else {
                    console.error('❌ Route Map Error:', status);
                    mapElement.classList.add('map-error');
                    mapElement.setAttribute('data-error', t('map_load_error', 'Error al cargar el mapa.') + ' ' + status);
                }
            }
        );
    }

    // ===== BACK BUTTONS =====
    $(document).on('click', '#wptb-back-step2', function () {
        switchStep(2, 1);
    });

    $(document).on('click', '#wptb-back-step3', function () {
        // Go back to Home Page (or reload)
        window.location.href = (typeof wptb_vars !== 'undefined' && wptb_vars.home_url) ? wptb_vars.home_url : '/';
    });

    // ===== STEP SWITCHING =====
    function switchStep(from, to) {
        $('#wptb-step-' + from).fadeOut(300, function () {
            $('#wptb-step-' + to).fadeIn(300);
        });
    }

    // ===== BOOKING CONFIRMATION (REDIRECT TO STRIPE) =====
    $('#wptb-details-form').on('submit', function (e) {
        e.preventDefault();

        if (!bookingData.vehicle_id) {
            track('booking_error', { error_type: 'vehicle_data_missing' });
            alert(t('vehicle_data_lost', 'Error: se perdieron los datos del vehículo.'));
            return;
        }


        // Collect customer details
        bookingData.passengers = $('#wptb-passengers').val();
        bookingData.customer_name = $('#wptb-fullname').val();
        bookingData.customer_phone = $('#wptb-phone').val();
        bookingData.customer_email = $('#wptb-email').val();
        bookingData.suitcases = $('#wptb-suitcases').val();
        bookingData.carry_ons = $('#wptb-carryOns').val();
        bookingData.flight_number = $('#wptb-flight').val();
        bookingData.notes = $('#wptb-notes').val();

        // Capture Return Details if applicable
        if (bookingData.trip_type === 'round_trip') {
            bookingData.return_date = $('#wptb-return-date').val();
            bookingData.return_time = $('#wptb-return-time').val();
            bookingData.return_origin = $('#wptb-return-origin').val();
            bookingData.return_destination = $('#wptb-return-destination').val();
        }

        // Validation
        if (!bookingData.customer_name || !bookingData.customer_email || !bookingData.customer_phone) {
            alert(t('complete_required_fields', 'Por favor completa todos los campos obligatorios.'));
            return;
        }

        const $confirmButton = $('#wptb-confirm-btn').prop('disabled', true);
        $.ajax({
            url: wptb_vars.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: Object.assign({}, bookingData, {
                action: 'wptb_get_quote',
                security: wptb_vars.nonce,
                language: wptb_vars.language || 'es'
            })
        }).done(function (response) {
            if (!response.success || !response.data || !response.data.valid) {
                const message = response.data && response.data.message
                    ? response.data.message
                    : t('invalid_server_price', 'No se pudo calcular un precio válido para la reserva.');
                track('booking_error', { error_type: 'invalid_quote' });
                alert(message);
                $confirmButton.prop('disabled', false);
                return;
            }

            bookingData.price = Number.parseFloat(response.data.price);
            bookingData.price_cents = Number.parseInt(response.data.price_cents, 10);
            bookingData.distance_km = Number.parseFloat(response.data.total_distance_km);
            bookingData.duration_minutes = Number.parseInt(response.data.duration_minutes, 10);
            bookingData.language = response.data.booking_locale || wptb_vars.language || 'es';
            bookingData.quote_verified = true;

            track('begin_checkout', {
                vehicle_id: bookingData.vehicle_id,
                value: bookingData.price,
                currency: 'EUR',
                trip_type: bookingData.trip_type
            });

            $.ajax({
                url: wptb_vars.ajax_url,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'wptb_create_booking_draft',
                    booking_data: JSON.stringify(bookingData),
                    security: wptb_vars.nonce
                }
            }).done(function (draftResponse) {
                const draftToken = draftResponse && draftResponse.success && draftResponse.data
                    ? draftResponse.data.draft_token
                    : '';
                if (!/^[a-f0-9]{64}$/.test(draftToken)) {
                    const message = draftResponse && draftResponse.data && draftResponse.data.message
                        ? draftResponse.data.message
                        : t('invalid_booking_data', 'No hay datos de reserva válidos. Inicia una nueva reserva.');
                    track('booking_error', { error_type: 'draft_creation' });
                    alert(message);
                    return;
                }

                // The opaque token is the only booking value persisted after
                // PII has been collected. The full payload lives server-side.
                sessionStorage.setItem('wptb_booking_data', JSON.stringify({ draft_token: draftToken }));
                if (wptb_vars.payment_url) {
                    window.location.href = wptb_vars.payment_url;
                } else {
                    track('booking_error', { error_type: 'payment_url_missing' });
                    alert(t('configuration_error', 'Error de configuración. Contacta con soporte.'));
                }
            }).fail(function () {
                track('booking_error', { error_type: 'draft_connection' });
                alert(t('connection_error', 'Error de conexión.'));
            }).always(function () {
                $confirmButton.prop('disabled', false);
            });
        }).fail(function () {
            track('booking_error', { error_type: 'quote_connection' });
            alert(t('connection_error', 'Error de conexión.'));
            $confirmButton.prop('disabled', false);
        });
    });

    // ===== READ URL PARAMS (for cross-domain redirects from barcelonatourstransfers.com) =====
    function getBookingDataFromUrl() {
        const params = new URLSearchParams(window.location.search);
        // Accept 'origin' or 'login' (fallback alias used by some BTT form versions)
        const origin = params.get('origin') || params.get('login');
        const destination = params.get('destination');
        const date = params.get('date');
        const time = params.get('time');
        const source = params.get('source');

        if (origin && destination && date && time) {
            return {
                origin: decodeURIComponent(origin),
                destination: decodeURIComponent(destination),
                date: date,
                time: time,
                distance_km: 0,
                duration_minutes: 0,
                duration_text: '',
                vehicle_id: 0,
                vehicle_name: '',
                trip_type: 'one_way',
                price: 0,
                price_cents: 0,
                source: source || ''
            };
        }
        return null;
    }

    // ===== CALCULATE DISTANCE VIA DISTANCE MATRIX (fallback, no DirectionsService billing needed) =====
    function calculateDistanceAndLoadVehicles(origin, destination, onSuccess, onError) {
        function tryWithGoogle() {
            if (typeof google === 'undefined' || !google.maps || !google.maps.DistanceMatrixService) {
                setTimeout(tryWithGoogle, 500);
                return;
            }
            const service = new google.maps.DistanceMatrixService();
            service.getDistanceMatrix(
                {
                    origins: [origin],
                    destinations: [destination],
                    travelMode: google.maps.TravelMode.DRIVING,
                    unitSystem: google.maps.UnitSystem.METRIC
                },
                function (response, status) {
                    if (status !== 'OK') {
                        if (typeof onError === 'function') onError(t('route_error', 'No se pudo calcular la ruta. Verifica el origen y el destino.') + ' ' + status);
                        return;
                    }
                    const element = response &&
                        response.rows &&
                        response.rows[0] &&
                        response.rows[0].elements &&
                        response.rows[0].elements[0]
                        ? response.rows[0].elements[0]
                        : null;

                    if (!element || element.status !== 'OK' || !element.distance || !element.duration) {
                        if (typeof onError === 'function') onError(t('route_not_found', 'No se encontró una ruta entre los puntos indicados.'));
                        return;
                    }

                    if (typeof onSuccess === 'function') {
                        onSuccess({
                            distanceKm: (element.distance.value / 1000).toFixed(1),
                            durationMinutes: Math.round(element.duration.value / 60),
                            durationText: element.duration.text
                        });
                    }
                }
            );
        }
        tryWithGoogle();
    }

    // ===== INIT DETAILS OR VEHICLE SELECTION PAGE =====
    function initDetailsPage() {
        const isVehiclePage = $('#wptb-step-2').length > 0 && $('#wptb-step-3').length === 0;
        const isDetailsPage = $('#wptb-step-3').length > 0;

        if (!isVehiclePage && !isDetailsPage) return; // Not a booking page


        let savedData = sessionStorage.getItem('wptb_booking_data');

        // ===== CROSS-DOMAIN FALLBACK: read URL params if sessionStorage is empty =====
        if (!savedData && isVehiclePage) {
            const urlData = getBookingDataFromUrl();
            if (urlData) {

                // Show loading state
                $('#wptb-step-2').show();
                $('#vehicles-grid').html('<div class="loading-spinner mt-empty-state">' + escapeHtml(t('calculating', 'Calculando...')) + '</div>');

                calculateDistanceAndLoadVehicles(
                    urlData.origin,
                    urlData.destination,
                    function (metrics) {
                        urlData.distance_km = metrics.distanceKm;
                        urlData.duration_minutes = metrics.durationMinutes;
                        urlData.duration_text = metrics.durationText;

                        bookingData = urlData;
                        sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));


                        if ($('#wptb-vehicle-summary-route').length) {
                            $('#wptb-vehicle-summary-route').text(bookingData.origin + ' → ' + bookingData.destination + ' (' + bookingData.distance_km + ' km)');
                        }

                        $('.trip-type-btn').removeClass('active');
                        $('.trip-type-btn[data-type="one_way"]').addClass('active');

                        loadVehicles();
                    },
                    function (errMsg) {
                        const message = errMsg || t('route_error', 'No se pudo calcular la ruta. Verifica el origen y el destino.');
                        $('#vehicles-grid').html(
                            '<div class="mt-alert mt-alert--error mt-empty-state" role="alert">' +
                            escapeHtml(message) +
                            '</div>'
                        );
                    }
                );
                return;
            }
        }

        if (!savedData) {
            console.warn('⚠️ No booking data found in sessionStorage.');
            return;
        }

        bookingData = JSON.parse(savedData);

        // ===== VEHICLE SELECTION PAGE (/seleccionar-vehiculo/) =====
        if (isVehiclePage) {
            if (!bookingData.distance_km) {
                console.warn('⚠️ No distance_km in bookingData. Cannot load vehicles.');
                return;
            }
            // Always reset vehicle_id so the user can pick fresh
            bookingData.vehicle_id = 0;
            bookingData.vehicle_name = '';
            bookingData.price = 0;
            bookingData.price_cents = 0;
            sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));

            // Show search summary (route info at top)
            if ($('#wptb-vehicle-summary-route').length) {
                $('#wptb-vehicle-summary-route').text(bookingData.origin + ' → ' + bookingData.destination + ' (' + bookingData.distance_km + ' km)');
            }
            // Sync trip type buttons
            $('.trip-type-btn').removeClass('active');
            $(`.trip-type-btn[data-type="${bookingData.trip_type}"]`).addClass('active');

            loadVehicles();
            return; // Done for vehicle page
        }

        // ===== DETAILS PAGE (/reservas-metransfers/) =====
        if (isDetailsPage) {
            if (!bookingData.vehicle_id || !bookingData.vehicle) {
                console.warn('⚠️ No vehicle selected or session is outdated. Redirecting back to vehicle selection.');
                sessionStorage.removeItem('wptb_booking_data');
                window.location.href = (typeof wptb_vars !== 'undefined' && wptb_vars.vehicles_url)
                    ? wptb_vars.vehicles_url : '/seleccionar-vehiculo/';
                return;
            }

            // Handle Return Details Visibility
            if (bookingData.trip_type === 'round_trip') {
                $('#wptb-return-details').show();
                if (!$('#wptb-return-origin').val()) {
                    $('#wptb-return-origin').val(bookingData.destination);
                }
                if (!$('#wptb-return-destination').val()) {
                    $('#wptb-return-destination').val(bookingData.origin);
                }
            } else {
                $('#wptb-return-details').hide();
            }

            // Apply vehicle limits
            if (bookingData.vehicle) {
                const maxPax = parseInt(bookingData.vehicle.capacity) || 50;
                $('#wptb-passengers').attr('max', maxPax);
                $('#wptb-passengers').on('input', function () {
                    if (parseInt($(this).val()) > maxPax) {
                        $(this).val(maxPax);
                    }
                });

                const maxLuggage = Math.max(0, parseInt(bookingData.vehicle.luggage_capacity, 10) || 0);
                const $luggageFields = $('#wptb-suitcases, #wptb-carryOns').attr('max', maxLuggage);
                $luggageFields.on('input', function () {
                    const otherId = this.id === 'wptb-suitcases' ? '#wptb-carryOns' : '#wptb-suitcases';
                    const otherCount = Math.max(0, parseInt($(otherId).val(), 10) || 0);
                    const currentCount = Math.max(0, parseInt($(this).val(), 10) || 0);
                    if (currentCount + otherCount > maxLuggage) {
                        $(this).val(Math.max(0, maxLuggage - otherCount));
                    }
                });
            }

            // Wait for DOM paint then render summary + map
            setTimeout(function () {
                updateSummary();
            }, 100);
        }
    }

    // Call init on load
    initDetailsPage();
});
