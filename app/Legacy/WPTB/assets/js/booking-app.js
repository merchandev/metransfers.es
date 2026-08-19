jQuery(document).ready(function ($) {
    'use strict';
    console.log('🚀 WPTB Booking App Initialized v3.1');

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
                console.log('🔀 BTT redirect: routing to vehicle page with URL params:', targetUrl);
                window.location.replace(targetUrl);
            }
        }
    })();

    // ===== GLOBAL STATE =====
    let originAutocomplete, destinationAutocomplete;
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
        price: 0
    };

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
            calculatePrice(vehicle);
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
                console.log('🗺️ Google Maps detected. Initializing inputs for:', suffix);

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
            $originWrapper.addClass('wptb-origin-wrapper').css('position', 'relative');

            const locBtn = `
                <button type="button" id="${locBtnId}" class="wptb-geolocation-btn" title="Usar mi ubicación actual"
                    style="
                        position: absolute !important;
                        right: 15px !important;
                        left: auto !important;
                        top: 50% !important;
                        transform: translateY(-50%) !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        background: none !important;
                        border: none !important;
                        padding: 0 !important;
                        margin: 0 !important;
                        width: auto !important;
                        height: auto !important;
                        min-width: 0 !important;
                        z-index: 9999 !important;
                        cursor: pointer !important;
                        color: var(--wptb-primary) !important;
                    ">
                    <span class="dashicons dashicons-location" style="font-size:24px !important; width:24px !important; height:24px !important; margin:0 !important;"></span>
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
        console.log('🚗 Cargando vehículos en modal...');
        $('#wptb-modal-vehicles-grid').html('<div class="loading-spinner">' + escapeHtml(t('loading_vehicles', 'Buscando vehículos...')) + '</div>');

        if (typeof wptb_vars === 'undefined') {
            console.error('WPTB Vars missing');
            return;
        }

        $.ajax({
            url: wptb_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'wptb_get_vehicles',
                security: wptb_vars.nonce,
                distance_km: bookingData.distance_km,
                trip_type: bookingData.trip_type,
                language: wptb_vars.language || 'es'
            },
            success: function (response) {
                console.log('✅ Vehículos recibidos:', response);
                if (response.success && response.data && response.data.length > 0) {
                    displayVehiclesInModal(response.data);
                } else {
                    track('booking_error', { error_type: 'no_vehicles' });
                    $('#wptb-modal-vehicles-grid').html('<p style="color:#999; text-align:center; padding:20px;">' + escapeHtml(t('no_vehicles', 'No se encontraron vehículos disponibles.')) + '</p>');
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                track('booking_error', { error_type: 'vehicle_request' });
                $('#wptb-modal-vehicles-grid').html('<p style="color:red; text-align:center;">' + escapeHtml(t('vehicle_load_error', 'Error al cargar los vehículos.')) + '</p>');
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
                        ${imageUrl ? `<img src="${imageUrl}" alt="${vehicleName}">` : '<span style="font-size:32px;">🚗</span>'}
                    </div>
                    <div class="vehicle-info-compact">
                        <strong>${vehicleName}</strong>
                        <span class="vehicle-capacity">👥 ${vehicle.capacity} pax</span>
                        <span class="vehicle-price-compact">${escapeHtml(t('from_price', 'Desde'))} €${vehicle.pricing.min_oneway}</span>
                    </div>
                </div>
            `;
        });

        $('#wptb-modal-vehicles-grid').html(html);

        // Store vehicle data
        window.modalVehicleMap = {};
        vehicles.forEach(v => window.modalVehicleMap[v.id] = v);

        // Handle vehicle selection in modal
        $(document).on('click', '.wptb-modal-vehicle-btn', function () {
            const id = $(this).data('vehicle-id');
            const vehicle = window.modalVehicleMap[id];

            if (vehicle) {
                $('.wptb-modal-vehicle-btn').removeClass('selected');
                $(this).addClass('selected');

                // Calculate price and redirect
                bookingData.vehicle = vehicle; // Guardar el objeto entero
                bookingData.vehicle_id = vehicle.id;
                bookingData.vehicle_name = vehicle.name;

                const distance = parseFloat(bookingData.distance_km);
                const pricing = vehicle.pricing;
                let price = 0;

                if (bookingData.trip_type === 'one_way') {
                    price = Math.max(
                        distance * pricing.price_per_km_oneway,
                        pricing.min_oneway,
                        pricing.min_transfer
                    );
                } else {
                    price = Math.max(
                        distance * pricing.price_per_km_roundtrip,
                        pricing.min_roundtrip,
                        pricing.min_transfer
                    );
                }

                bookingData.price = parseFloat(price.toFixed(2));

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
        $('#wptb-booking-modal').attr('style', 'display: none !important;');
        console.log('Modal forced hidden on load (Force)');
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

        console.log('🔄 Dirección cambiada a:', direction);
    });

    // Open Modal on Carousel Click
    $(document).off('click', '.mtfs-slide'); // Remove previous handlers
    $(document).on('click', '.mtfs-slide', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        console.log('🖱️ Click en carrusel');

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
            console.log('🔵 Viaje HACIA Barcelona desde', destinationName);
            $originInput.val(destinationName);
            $originInput.prop('readonly', false);

            $displayInput.val('Barcelona, España');
            $regionContext.val('Barcelona, España');
            $specificInput.val('');
            $specificInput.attr('placeholder', 'Ej: Calle Principal 123, Barcelona');

            setTimeout(() => $specificInput.focus(), 500);
        } else {
            // Trip FROM Barcelona: Origin = Barcelona, Destination = Selected City
            console.log('🟢 Viaje DESDE Barcelona hacia', destinationName);
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
            console.log('✖️ X button clicked');

            const $modal = $('#wptb-booking-modal');
            $modal.removeAttr('style'); // Remove all inline styles
            $modal.attr('style', 'display: none !important;'); // Force hide with !important

            $('#wptb-modal-step-2').hide();
            $('#wptb-modal-step-1').show();
        });

        // Overlay click close - NUCLEAR APPROACH
        $(document).on('click', '#wptb-booking-modal', function (e) {
            if (e.target.id === 'wptb-booking-modal') {
                console.log('📦 Overlay clicked');

                const $modal = $(this);
                $modal.removeAttr('style');
                $modal.attr('style', 'display: none !important;');

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
    function calculateRoute(suffix) {
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
        console.log('🔓 BTT loader hidden, page shown.');
    }

    // Function to Load Vehicles (AJAX)
    function loadVehicles() {
        console.log('🚗 Cargando vehículos...');
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
            data: {
                action: 'wptb_get_vehicles',
                security: wptb_vars.nonce,
                distance_km: bookingData.distance_km,
                trip_type: bookingData.trip_type,
                language: wptb_vars.language || 'es'
            },
            success: function (response) {
                console.log('✅ Respuesta recibida:', response);
                hideBTTLoader(); // Always reveal page once we have a response
                if (response.success && response.data && response.data.length > 0) {
                    displayVehicles(response.data);
                } else {
                    displayNoVehicles(response);
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                hideBTTLoader();
                track('booking_error', { error_type: 'vehicle_request' });
                $('#vehicles-grid').html('<p style="color:red; text-align:center;">' + escapeHtml(t('vehicle_load_error', 'Error al cargar los vehículos.')) + '</p>');
            }
        });
    }

    function displayNoVehicles(response) {
        track('booking_error', { error_type: 'no_vehicles' });
        const $message = $('<div>', { style: 'text-align:center;padding:40px;' });
        $message.append('<span class="dashicons dashicons-warning" style="font-size:40px;color:#f0ad4e;"></span>');
        $message.append($('<p>', { style: 'margin-top:20px;' }).text(t('no_vehicles', 'No se encontraron vehículos disponibles.')));
        $('#vehicles-grid').empty().append($message);
    }

    function displayVehicles(vehicles) {
        let html = '';
        const distance = parseFloat(bookingData.distance_km || 0);

        vehicles.forEach(function (vehicle) {
            // Calculate Price for Display
            const pricing = vehicle.pricing;
            let displayPrice = 0;

            if (bookingData.trip_type === 'round_trip') {
                const effectiveDistance = distance * 2;
                displayPrice = Math.max(
                    effectiveDistance * parseFloat(pricing.price_per_km_roundtrip),
                    parseFloat(pricing.min_roundtrip),
                    parseFloat(pricing.min_transfer)
                );
            } else {
                displayPrice = Math.max(
                    distance * parseFloat(pricing.price_per_km_oneway),
                    parseFloat(pricing.min_oneway),
                    parseFloat(pricing.min_transfer)
                );
            }

            // Format Price (Standard integer for clean look if no decimals, or fixed if needed)
            const formattedPrice = Number.isInteger(displayPrice) ? displayPrice : displayPrice.toFixed(2);

            html += `
                <div class="vehicle-card" data-vehicle-id="${vehicle.id}" onclick="selectVehicle(${vehicle.id})" style="cursor:pointer; background: #fff; border-radius: 24px; overflow: hidden; border: 2px solid #f0f0f0; transition: all 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%; min-height: 450px;">
                    <div class="vehicle-image" style="background: #f5f7fa; height: 200px; padding: 20px; display:flex; align-items:center; justify-content:center; flex-shrink: 0;">
                        <img src="${vehicle.image}" alt="${vehicle.name}" style="max-height:100%; max-width:100%; width: auto; object-fit: contain;">
                    </div>
                    <div class="vehicle-info" style="padding: 25px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h3 style="margin:0 0 10px; font-size:20px; color:#00033b; font-weight:800; line-height: 1.2;">${vehicle.name}</h3>
                            
                            <div class="vehicle-features" style="font-size:14px; color:#666; margin-bottom:15px; display:flex; gap:15px; align-items: center;">
                                <span style="display: flex; align-items: center; gap: 6px;">
                                    <span class="dashicons dashicons-groups" style="color: #ff7100;"></span> 
                                    ${vehicle.capacity} pax
                                </span>
                            </div>
                        </div>

                        <div style="margin-top: auto;">
                            <div class="vehicle-price-preview" style="background: #00033b; border-radius: 16px; padding: 15px; text-align: center; margin-bottom: 15px; border: 1px solid #ff7100;">
                                <span class="price-label" style="display:block; font-size:11px; text-transform:uppercase; color:#fff; letter-spacing: 1px; margin-bottom: 4px;">${escapeHtml(t('final_price', 'Precio final'))}</span>
                                <span class="price-value" style="display:block; font-size:26px; font-weight:800; color:#ff7100;">€${formattedPrice}</span>
                            </div>

                            <button type="button" class="select-vehicle-btn" style="width:100%; border:none; background-color:#ff7100; color:#fff; padding:15px; border-radius: 50px; font-weight:800; cursor:pointer; text-transform:uppercase; letter-spacing: 0.5px; font-size: 14px; transition: background 0.3s;">
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
            console.log('🔄 Trip type changed to:', newTripType);
            loadVehicles(); // Reload vehicles to update prices
        }
    });

    // ===== SELECT VEHICLE BTN HANDLER =====
    $(document).on('click', '.select-vehicle-btn', function (e) {
        e.stopPropagation(); // Prevent bubbling to card onclick
        const $card = $(this).closest('.vehicle-card');
        const id = $card.data('vehicle-id');
        selectVehicle(id);
    });

    function calculatePrice(vehicle) {
        console.log('💰 Calculating price for vehicle:', vehicle);

        const distance = parseFloat(bookingData.distance_km);
        const pricing = vehicle.pricing;
        let price = 0;

        if (bookingData.trip_type === 'one_way') {
            price = Math.max(
                distance * pricing.price_per_km_oneway,
                pricing.min_oneway,
                pricing.min_transfer
            );
        } else {
            const effectiveDistance = distance * 2;
            price = Math.max(
                effectiveDistance * pricing.price_per_km_roundtrip,
                pricing.min_roundtrip,
                pricing.min_transfer
            );
        }

        bookingData.price = parseFloat(price.toFixed(2));

        track('vehicle_select', {
            vehicle_id: vehicle.id,
            vehicle_name: vehicle.name,
            value: bookingData.price,
            currency: 'EUR'
        });

        // Save to sessionStorage
        sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));

        console.log('💾 Booking data saved:', bookingData);

        // Redirect to details page with fallback
        let detailsUrl;

        if (typeof wptb_vars !== 'undefined' && wptb_vars.details_url) {
            detailsUrl = wptb_vars.details_url;
        } else {
            // Fallback URL
            detailsUrl = '/reservas-metransfers/';
            console.warn('⚠️ wptb_vars not available, using fallback URL');
        }

        console.log('🔄 Redirecting to details page:', detailsUrl);

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
            console.log('⚠️ Google Maps not ready for Route Map. Retrying in 500ms...');
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
            console.log('🗺️ Initializing Google Maps Instance...');
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
                    console.log('✅ Route Map Loaded Successfully');
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

        track('begin_checkout', {
            vehicle_id: bookingData.vehicle_id,
            value: Number.parseFloat(bookingData.price || 0),
            currency: 'EUR',
            trip_type: bookingData.trip_type
        });

        console.log('✅ Booking complete, redirecting to payment...');

        // Save to sessionStorage
        sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));

        // Redirect to payment page
        if (wptb_vars && wptb_vars.payment_url) {
            window.location.href = wptb_vars.payment_url;
        } else {
            console.error('Payment URL missing');
            track('booking_error', { error_type: 'payment_url_missing' });
            alert(t('configuration_error', 'Error de configuración. Contacta con soporte.'));
        }
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
            console.log('🔗 Booking data found in URL params (source: ' + (source || 'unknown') + ')');
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

        console.log('📄 Initializing booking page. isVehiclePage:', isVehiclePage, '| isDetailsPage:', isDetailsPage);

        let savedData = sessionStorage.getItem('wptb_booking_data');

        // ===== CROSS-DOMAIN FALLBACK: read URL params if sessionStorage is empty =====
        if (!savedData && isVehiclePage) {
            const urlData = getBookingDataFromUrl();
            if (urlData) {
                console.log('🔗 Building booking data from URL params:', urlData);

                // Show loading state
                $('#wptb-step-2').show();
                $('#vehicles-grid').html('<div class="loading-spinner" style="text-align:center;padding:40px;">' + escapeHtml(t('calculating', 'Calculando...')) + '</div>');

                calculateDistanceAndLoadVehicles(
                    urlData.origin,
                    urlData.destination,
                    function (metrics) {
                        urlData.distance_km = metrics.distanceKm;
                        urlData.duration_minutes = metrics.durationMinutes;
                        urlData.duration_text = metrics.durationText;

                        bookingData = urlData;
                        sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));

                        console.log('✅ Distance calculated from URL params:', metrics);

                        if ($('#wptb-vehicle-summary-route').length) {
                            $('#wptb-vehicle-summary-route').text(bookingData.origin + ' → ' + bookingData.destination + ' (' + bookingData.distance_km + ' km)');
                        }

                        $('.trip-type-btn').removeClass('active');
                        $('.trip-type-btn[data-type="one_way"]').addClass('active');

                        loadVehicles();
                    },
                    function (errMsg) {
                        console.error('❌ Distance Matrix error:', errMsg);
                        // Still load vehicles without distance filter as fallback
                        urlData.distance_km = 50; // safe default
                        bookingData = urlData;
                        sessionStorage.setItem('wptb_booking_data', JSON.stringify(bookingData));
                        loadVehicles();
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
        console.log('📂 Loaded Data:', bookingData);

        // ===== VEHICLE SELECTION PAGE (/seleccionar-vehiculo/) =====
        if (isVehiclePage) {
            if (!bookingData.distance_km) {
                console.warn('⚠️ No distance_km in bookingData. Cannot load vehicles.');
                return;
            }
            console.log('🚗 Vehicle selection page: Loading vehicles...');
            // Always reset vehicle_id so the user can pick fresh
            bookingData.vehicle_id = 0;
            bookingData.vehicle_name = '';
            bookingData.price = 0;
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

                // Lógica personalizada de equipaje (basada en el límite de pasajeros)
                let maxSuit = 4; // Sedan (hasta 4 pax)
                if (maxPax > 4) {
                    maxSuit = 9; // Van (más de 4 pax)
                }
                
                $('#wptb-suitcases').attr('max', maxSuit);
                $('#wptb-suitcases').on('input', function () {
                    if (parseInt($(this).val()) > maxSuit) {
                        $(this).val(maxSuit);
                    }
                });

                const maxCarry = 1; // Siempre 1 maleta de mano
                $('#wptb-carryOns').attr('max', maxCarry);
                $('#wptb-carryOns').on('input', function () {
                    if (parseInt($(this).val()) > maxCarry) {
                        $(this).val(maxCarry);
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
