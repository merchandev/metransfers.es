// Premium Transfers Search - Integrado con sistema WPTB
document.addEventListener("DOMContentLoaded", () => {
    // Use data from PHP
    const destinations = ptsData.destinations;
    const ptsStrings = (ptsData && ptsData.strings) || {};
    let currentFilter = "all";
    let currentSearch = "";
    const PTS_ALLOWED_COUNTRIES = ['es', 'fr', 'de', 'pt', 'ad'];
    const PTS_MAPS_TIMEOUT_MS = 12000;
    const PTS_MAPS_POLL_MS = 250;

    function t(key, fallback) {
        return ptsStrings[key] || fallback;
    }

    function escapeHtml(value) {
        const node = document.createElement('div');
        node.textContent = value || '';
        return node.innerHTML;
    }

    function track(eventName, parameters) {
        if (typeof window.mtBookingTrack === 'function') {
            window.mtBookingTrack(eventName, parameters || {});
        }
    }

    function getGoogleMapsConfig() {
        return {
            key: String((ptsData && ptsData.google_maps_api_key) || '').trim(),
            language: String((ptsData && ptsData.google_maps_language) || 'es').trim() || 'es',
            region: String((ptsData && ptsData.google_maps_region) || 'ES').trim() || 'ES'
        };
    }

    function buildGoogleMapsApiUrl(config) {
        const url = new URL('https://maps.googleapis.com/maps/api/js');
        url.searchParams.set('key', config.key);
        url.searchParams.set('language', config.language);
        url.searchParams.set('region', config.region);
        url.searchParams.set('libraries', 'places');
        return url.toString();
    }

    function hidePlacesSuggestionPanels() {
        document.querySelectorAll('.pac-container').forEach((container) => {
            container.style.display = 'none';
            container.style.visibility = 'hidden';
        });
    }

    function finalizeAutocompleteSelection(input) {
        if (!input) {
            return;
        }

        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));

        setTimeout(() => {
            input.blur();
            hidePlacesSuggestionPanels();
        }, 0);
    }

    function ensureGoogleMapsReadyForPTS() {
        if (typeof window.wptbEnsureGoogleMapsReady === 'function') {
            return window.wptbEnsureGoogleMapsReady();
        }

        if (window.wptbGoogleMapsReadyPromise) {
            return window.wptbGoogleMapsReadyPromise;
        }

        window.wptbGoogleMapsReadyPromise = new Promise((resolve, reject) => {
            if (typeof google !== 'undefined' && google.maps) {
                resolve(google.maps);
                return;
            }

            const config = getGoogleMapsConfig();
            let script = document.querySelector('script[data-wptb-google-maps="1"], script[src*="maps.googleapis.com/maps/api/js"]');

            if (!script) {
                if (!config.key) {
                    reject(new Error('Google Maps API key is missing.'));
                    return;
                }

                script = document.createElement('script');
                script.src = buildGoogleMapsApiUrl(config);
                script.async = true;
                script.defer = true;
                script.setAttribute('data-wptb-google-maps', '1');
                document.head.appendChild(script);
            }

            const startedAt = Date.now();
            const pollTimer = setInterval(() => {
                if (typeof google !== 'undefined' && google.maps) {
                    clearInterval(pollTimer);
                    resolve(google.maps);
                    return;
                }

                if ((Date.now() - startedAt) >= PTS_MAPS_TIMEOUT_MS) {
                    clearInterval(pollTimer);
                    reject(new Error('Google Maps Places did not load in time.'));
                }
            }, PTS_MAPS_POLL_MS);

            script.addEventListener('error', () => {
                clearInterval(pollTimer);
                reject(new Error('Google Maps script failed to load.'));
            }, { once: true });
        });

        return window.wptbGoogleMapsReadyPromise;
    }

    function ensurePlacesAutocompleteReadyForPTS() {
        return ensureGoogleMapsReadyForPTS()
            .then(async () => {
                if (google.maps && google.maps.places && google.maps.places.Autocomplete) {
                    return true;
                }

                if (google.maps && typeof google.maps.importLibrary === 'function') {
                    try {
                        await google.maps.importLibrary('places');
                    } catch (error) {
                        console.warn('[PTS] importLibrary("places") fallo:', error);
                    }
                }

                return !!(google.maps && google.maps.places && google.maps.places.Autocomplete);
            });
    }

    function tryParseJsonPayload(payload) {
        if (typeof payload !== "string") {
            return payload && typeof payload === "object" ? payload : null;
        }

        const trimmed = payload.trim();
        if (!trimmed) {
            return null;
        }

        try {
            return JSON.parse(trimmed);
        } catch (error) {
            const firstBrace = trimmed.indexOf("{");
            const lastBrace = trimmed.lastIndexOf("}");

            if (firstBrace !== -1 && lastBrace > firstBrace) {
                const candidate = trimmed.slice(firstBrace, lastBrace + 1);
                try {
                    return JSON.parse(candidate);
                } catch (innerError) {
                    return null;
                }
            }
        }

        return null;
    }

    function normalizeVehiclesResponse(response, xhr) {
        if (response && typeof response === "object") {
            return response;
        }

        const parsed = tryParseJsonPayload(response);
        if (parsed) {
            return parsed;
        }

        if (xhr && typeof xhr.responseText === "string") {
            const parsedFromXhr = tryParseJsonPayload(xhr.responseText);
            if (parsedFromXhr) {
                return parsedFromXhr;
            }
        }

        return null;
    }

    function extractVehiclesFromResponse(response) {
        if (!response || response.success !== true) {
            return [];
        }

        if (Array.isArray(response.data)) {
            return response.data;
        }

        if (response.data && Array.isArray(response.data.vehicles)) {
            return response.data.vehicles;
        }

        return [];
    }

    function applyServerRouteFromResponse(response) {
        const route = response && response.data ? response.data.route : null;
        if (!route || !window.bookingData) return;
        window.bookingData.distance_km = Number.parseFloat(route.total_distance_km || route.distance_km || 0);
        window.bookingData.duration_minutes = Number.parseInt(route.duration_minutes || 0, 10);
        window.bookingData.duration_text = window.bookingData.duration_minutes > 0
            ? window.bookingData.duration_minutes + ' min'
            : '';
        window.bookingData.quote_verified = true;
    }

    function getVehiclesResponseMessage(response) {
        if (!response || typeof response !== "object") {
            return "";
        }

        if (response.data && typeof response.data.message === "string") {
            return response.data.message;
        }

        if (typeof response.message === "string") {
            return response.message;
        }

        return "";
    }

    function calculateRouteMetrics(origin, destination, onSuccess, onError) {
        ensureGoogleMapsReadyForPTS()
            .then(() => {
                if (!google.maps || !google.maps.DistanceMatrixService) {
                    throw new Error('Google Maps Distance Matrix is not available.');
                }

                const service = new google.maps.DistanceMatrixService();
                service.getDistanceMatrix(
                    {
                        origins: [origin],
                        destinations: [destination],
                        travelMode: google.maps.TravelMode.DRIVING,
                        unitSystem: google.maps.UnitSystem.METRIC
                    },
                    (response, status) => {
                        if (status !== 'OK') {
                            if (typeof onError === 'function') {
                                onError(t('route_error', 'No se pudo calcular la ruta.'));
                            }
                            return;
                        }

                        const element = response
                            && response.rows
                            && response.rows[0]
                            && response.rows[0].elements
                            && response.rows[0].elements[0]
                            ? response.rows[0].elements[0]
                            : null;

                        if (!element || element.status !== 'OK' || !element.distance || !element.duration) {
                            if (typeof onError === 'function') {
                                onError(t('route_error', 'No se pudo calcular la ruta.'));
                            }
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
            })
            .catch((error) => {
                if (typeof onError === 'function') {
                    onError(t('route_error', 'No se pudo calcular la ruta.'));
                }
                console.error('[PTS] Distance matrix failed:', error);
            });
    }

    function formatCurrencyLabel(amount) {
        const numericAmount = Number(amount);
        if (!Number.isFinite(numericAmount)) {
            return 'EUR 0.00';
        }

        return `EUR ${numericAmount.toFixed(2)}`;
    }

    function renderDestinations(filteredDestinations) {
        const grid = document.getElementById("ptsDestinationsGrid");
        const noResults = document.getElementById("ptsNoResults");

        if (!grid) return;

        if (filteredDestinations.length === 0) {
            grid.style.display = "none";
            noResults.style.display = "block";
            return;
        }

        grid.style.display = "grid";
        noResults.style.display = "none";

        grid.innerHTML = filteredDestinations
            .map((dest) => {
                return `
            <div class="pts-destination-card" data-name="${dest.name}">
                <div class="pts-destination-header">
                    <div class="pts-destination-info">
                        <h3 class="pts-destination-name">${dest.name}</h3>
                        <div class="pts-destination-region">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            ${dest.region}
                        </div>
                    </div>
                    <span class="pts-badge pts-badge-category">${dest.category}</span>
                </div>
            </div>
        `;
            })
            .join("");
    }

    function filterDestinations() {
        let filtered = destinations;

        if (currentFilter !== "all") {
            filtered = filtered.filter((dest) => dest.category === currentFilter);
        }

        if (currentSearch) {
            filtered = filtered.filter(
                (dest) =>
                    dest.name.toLowerCase().includes(currentSearch.toLowerCase()) ||
                    dest.region.toLowerCase().includes(currentSearch.toLowerCase())
            );
        }

        renderDestinations(filtered);
    }

    // Initial render
    renderDestinations(destinations);

    function initPTSAutocomplete() {
        ensurePlacesAutocompleteReadyForPTS()
            .then((placesReady) => {
                if (!placesReady) {
                    console.warn('[PTS] Places Autocomplete no disponible.');
                    return;
                }

                const options = {
                    fields: ['formatted_address', 'geometry', 'name'],
                    strictBounds: false,
                    componentRestrictions: { country: PTS_ALLOWED_COUNTRIES }
                };

                const originInput = document.getElementById('pts-origin');
                const destinationInput = document.getElementById('pts-destination-exact');

                if (originInput && originInput.dataset.wptbAutocompleteInitialized !== '1') {
                    const originAutocomplete = new google.maps.places.Autocomplete(originInput, options);
                    originAutocomplete.addListener('place_changed', () => {
                        const place = originAutocomplete.getPlace();
                        if (place && place.formatted_address) {
                            originInput.value = place.formatted_address;
                        }
                        finalizeAutocompleteSelection(originInput);
                    });
                    originInput.addEventListener('blur', hidePlacesSuggestionPanels);
                    originInput.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === 'Tab') {
                            setTimeout(() => hidePlacesSuggestionPanels(), 0);
                        }
                    });
                    originInput.dataset.wptbAutocompleteInitialized = '1';
                }

                if (destinationInput && destinationInput.dataset.wptbAutocompleteInitialized !== '1') {
                    const destinationAutocomplete = new google.maps.places.Autocomplete(destinationInput, options);
                    destinationAutocomplete.addListener('place_changed', () => {
                        const place = destinationAutocomplete.getPlace();
                        if (place && place.formatted_address) {
                            destinationInput.value = place.formatted_address;
                        }
                        finalizeAutocompleteSelection(destinationInput);
                    });
                    destinationInput.addEventListener('blur', hidePlacesSuggestionPanels);
                    destinationInput.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === 'Tab') {
                            setTimeout(() => hidePlacesSuggestionPanels(), 0);
                        }
                    });
                    destinationInput.dataset.wptbAutocompleteInitialized = '1';
                }

            })
            .catch((error) => {
                console.error('[PTS] Google Maps bootstrap is not available:', error.message);
            });
    }

    initPTSAutocomplete();

    // Search functionality
    const searchInput = document.getElementById("ptsSearchInput");
    if (searchInput) {
        searchInput.addEventListener("input", (e) => {
            currentSearch = e.target.value;
            filterDestinations();
        });
    }

    // Filter buttons
    const filterButtons = document.querySelectorAll(".pts-filter-btn");
    filterButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            filterButtons.forEach((b) => b.classList.remove("active"));
            btn.classList.add("active");
            currentFilter = btn.dataset.category;
            filterDestinations();
        });
    });

    // Click on destination card - OPEN PTS MODAL
    const grid = document.getElementById("ptsDestinationsGrid");
    if (grid) {
        grid.addEventListener("click", (e) => {
            const card = e.target.closest(".pts-destination-card");
            if (card) {
                const name = card.dataset.name;

                const modal = document.getElementById('pts-booking-modal');

                if (modal) {
                    // Show modal
                    modal.setAttribute('style', 'display: flex !important;');

                    // Pre-fill form
                    document.getElementById('pts-origin').value = 'Barcelona, Espana';
                    document.getElementById('pts-destination-display').value = name;
                    document.getElementById('pts-region-context').value = name;
                    document.getElementById('pts-destination-exact').value = '';
                    document.getElementById('pts-destination-exact').placeholder = `Ej: Hotel Neptuno, ${name}`;

                    setTimeout(() => {
                        document.getElementById('pts-destination-exact').focus();
                    }, 300);

                }
            }
        });
    }

    // Modal close handlers
    const closeBtn = document.getElementById('pts-modal-close');
    const modal = document.getElementById('pts-booking-modal');

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => {
            modal.setAttribute('style', 'display: none !important;');
            // Reset to step 1
            jQuery('#pts-modal-step-2').hide();
            jQuery('#pts-modal-step-1').show();
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.setAttribute('style', 'display: none !important;');
                jQuery('#pts-modal-step-2').hide();
                jQuery('#pts-modal-step-1').show();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                modal.setAttribute('style', 'display: none !important;');
                jQuery('#pts-modal-step-2').hide();
                jQuery('#pts-modal-step-1').show();
            }
        });
    }

    // Form submission - INTEGRATE WITH WPTB SYSTEM
    const ptsForm = document.getElementById('pts-search-form');
    if (ptsForm) {
        ptsForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const date = document.getElementById('pts-date').value;
            const time = document.getElementById('pts-time').value;
            const origin = document.getElementById('pts-origin').value;
            const destinationDisplay = document.getElementById('pts-destination-display').value;
            const destinationExact = document.getElementById('pts-destination-exact').value;

            if (!date || !time || !origin || !destinationDisplay || !destinationExact) {
                alert(t('complete_all_fields', 'Por favor completa todos los campos.'));
                return;
            }

            track('booking_start', { booking_flow: 'premium_transfers' });
            track('route_search', { booking_flow: 'premium_transfers', trip_type: 'one_way' });

            const fullDestination = destinationExact + ', ' + destinationDisplay;

            if (typeof jQuery === 'undefined' || typeof google === 'undefined') {
                track('booking_error', { error_type: 'maps_unavailable' });
                alert(t('system_unavailable', 'El sistema no está disponible. Recarga la página.'));
                return;
            }

            const $ = jQuery;

            // Initialize global bookingData if needed
            if (!window.bookingData) {
                window.bookingData = { trip_type: 'one_way' };
            }

            window.bookingData.date = date;
            window.bookingData.time = time;
            window.bookingData.origin = origin;
            window.bookingData.destination = fullDestination;
            const $submitBtn = jQuery('#pts-submitBtn');

            $submitBtn.prop('disabled', true).text(t('calculating', 'Calculando...'));

            calculateRouteMetrics(
                origin,
                fullDestination,
                (metrics) => {
                    $submitBtn.prop('disabled', false).text(t('search_vehicles', 'Buscar vehículos'));

                    window.bookingData.distance_km = metrics.distanceKm;
                    window.bookingData.duration_minutes = metrics.durationMinutes;
                    window.bookingData.duration_text = metrics.durationText;

                    // Show step 2
                    jQuery('#pts-modal-step-1').hide();
                    jQuery('#pts-modal-step-2').fadeIn();

                    // Load vehicles
                    loadVehiclesIntoPTSModal();
                },
                () => {
                    $submitBtn.prop('disabled', false).text(t('search_vehicles', 'Buscar vehículos'));
                    track('booking_error', { error_type: 'route_calculation' });
                    alert(t('route_error', 'No se pudo calcular la ruta. Verifica el origen y el destino.'));
                }
            );
        });
    }

    // Load vehicles
    function loadVehiclesIntoPTSModal() {
        const $ = jQuery;
        $('#pts-modal-vehicles-grid').html('<div class="loading-spinner">' + escapeHtml(t('loading_vehicles', 'Buscando vehículos...')) + '</div>');

        if (typeof wptb_vars === 'undefined') {
            track('booking_error', { error_type: 'configuration' });
            $('#pts-modal-vehicles-grid').html('<p style="color:red;">' + escapeHtml(t('configuration_error', 'Error de configuración. Contacta con soporte.')) + '</p>');
            return;
        }

        $.ajax({
            url: wptb_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wptb_get_vehicles',
                security: wptb_vars.nonce,
                date: window.bookingData.date || '',
                time: window.bookingData.time || '',
                origin: window.bookingData.origin || '',
                destination: window.bookingData.destination || '',
                trip_type: window.bookingData.trip_type || 'one_way',
                language: wptb_vars.language || 'es'
            },
            success: function (response, textStatus, xhr) {
                const normalizedResponse = normalizeVehiclesResponse(response, xhr);
                const vehicles = extractVehiclesFromResponse(normalizedResponse);
                applyServerRouteFromResponse(normalizedResponse);

                if (vehicles.length > 0) {
                    displayVehiclesInPTSModal(vehicles);
                } else {
                    const responseMessage = getVehiclesResponseMessage(normalizedResponse);
                    const noVehiclesMessage = responseMessage || t('no_vehicles', 'No se encontraron vehículos disponibles.');
                    track('booking_error', { error_type: 'no_vehicles' });
                    $('#pts-modal-vehicles-grid').html(`<p style="text-align:center;padding:20px;">${escapeHtml(noVehiclesMessage)}</p>`);
                }
            },
            error: function (xhr, status, error) {
                const normalizedResponse = normalizeVehiclesResponse(null, xhr);
                const vehicles = extractVehiclesFromResponse(normalizedResponse);

                if (vehicles.length > 0) {
                    displayVehiclesInPTSModal(vehicles);
                    return;
                }

                const nonceExpired = xhr && typeof xhr.responseText === 'string' && xhr.responseText.trim() === '-1';
                const responseMessage = getVehiclesResponseMessage(normalizedResponse);
                const errorMessage = nonceExpired
                    ? 'La sesion expiro. Recarga la pagina e intenta de nuevo.'
                    : (responseMessage || t('vehicle_load_error', 'Error al cargar los vehículos.'));

                console.error('[PTS] Error cargando vehiculos:', status, error, xhr ? xhr.responseText : '');
                track('booking_error', { error_type: 'vehicle_request' });
                $('#pts-modal-vehicles-grid').html(`<p style="color:red;text-align:center;">${escapeHtml(errorMessage)}</p>`);
            }
        });
    }

    // Display vehicles
    function displayVehiclesInPTSModal(vehicles) {
        const $ = jQuery;
        let html = '';

        vehicles.forEach(function (vehicle) {
            const img = vehicle.image_url || vehicle.image || '';
            const serverPrice = Number.parseFloat(vehicle.price || 0);
            const capacity = parseInt(vehicle.capacity, 10);
            const capacityText = Number.isFinite(capacity) && capacity > 0
                ? `${capacity} pax`
                : t('capacity_unavailable', 'Capacidad no disponible');
            html += `
                <div class="wptb-modal-vehicle-btn pts-vehicle-btn" data-vehicle-id="${vehicle.id}">
                    <div class="vehicle-icon">
                        ${img ? `<img src="${img}" alt="${vehicle.name}">` : '<span class="material-symbols-outlined vehicle-fallback-icon" aria-hidden="true">directions_car</span>'}
                    </div>
                    <div class="vehicle-info-compact">
                        <strong>${vehicle.name}</strong>
                        <span class="vehicle-capacity"><span class="material-symbols-outlined vehicle-inline-icon" aria-hidden="true">group</span>${capacityText}</span>
                        <span class="vehicle-price-compact">${escapeHtml(t('price', 'Precio'))} ${formatCurrencyLabel(serverPrice)}</span>
                    </div>
                </div>
            `;
        });

        $('#pts-modal-vehicles-grid').html(html);

        window.ptsVehicleMap = {};
        vehicles.forEach(v => window.ptsVehicleMap[v.id] = v);

        // Vehicle selection
        $(document).off('click', '.pts-vehicle-btn').on('click', '.pts-vehicle-btn', function () {
            const id = $(this).data('vehicle-id');
            const vehicle = window.ptsVehicleMap[id];

            if (vehicle) {
                $('.pts-vehicle-btn').removeClass('selected');
                $(this).addClass('selected');

                window.bookingData.vehicle_id = vehicle.id;
                window.bookingData.vehicle_name = vehicle.name;
                window.bookingData.vehicle = vehicle;
                window.bookingData.price = Number.parseFloat(vehicle.price);

                track('vehicle_select', {
                    vehicle_id: vehicle.id,
                    vehicle_name: vehicle.name,
                    value: window.bookingData.price,
                    currency: 'EUR'
                });

                sessionStorage.setItem('wptb_booking_data', JSON.stringify(window.bookingData));

                const detailsUrl = wptb_vars.details_url || '/reservas-metransfers/';

                setTimeout(() => window.location.href = detailsUrl, 100);
            }
        });
    }

    // Trip type toggle
    jQuery(document).on('click', '.trip-type-btn-pts', function () {
        jQuery('.trip-type-btn-pts').removeClass('active');
        jQuery(this).addClass('active');
        window.bookingData.trip_type = jQuery(this).data('type');

        if (jQuery('#pts-modal-step-2').is(':visible')) {
            loadVehiclesIntoPTSModal();
        }
    });

    // Back button
    jQuery(document).on('click', '#pts-modal-back', function (e) {
        e.preventDefault();
        jQuery('#pts-modal-step-2').hide();
        jQuery('#pts-modal-step-1').fadeIn();
    });
});

jQuery(document).ready(function($) {
    // Asegurar que al hacer clic en el label o hacer foco en el input, se abra el selector nativo
    $("input[type='date'], input[type='time']").on("focus click", function() {
        if (typeof this.showPicker === "function") {
            try { this.showPicker(); } catch(e) {}
        }
    });
    
    // Si se hace clic en la etiqueta, disparar el click o focus en el input correspondiente
    $(".floating-label label").on("click", function() {
        var forAttr = $(this).attr("for");
        if(forAttr) {
            $("#" + forAttr).trigger("focus");
        }
    });
});

