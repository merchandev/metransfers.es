/**
 * Debug helper for Metransfers Plugin
 * Add this in WordPress admin to test AJAX endpoints
 */

// Test AJAX endpoint for getting vehicles
jQuery(document).ready(function ($) {
    console.log('[WPTB] Debug Helper Loaded');
    console.log('[WPTB] AJAX URL:', wptb_vars.ajax_url);

    // Test vehicles endpoint
    window.testVehiclesEndpoint = function () {
        console.log('[WPTB] Testing vehicles endpoint...');

        $.post(wptb_vars.ajax_url, {
            action: 'wptb_get_vehicles'
        }, function (response) {
            console.log('[WPTB] Response received:', response);

            if (response.success) {
                console.log(`[WPTB] Found ${response.data.length} vehicles:`);
                response.data.forEach((v, index) => {
                    console.log(`${index + 1}. ${v.name} (${v.type}) - Capacity: ${v.capacity}`);
                });
            } else {
                console.error('[WPTB] Error response:', response);
            }
        }).fail(function (xhr, status, error) {
            console.error('[WPTB] AJAX Failed:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
        });
    };

    // Test pricing endpoint
    window.testPricingEndpoint = function (vehicleId, distance, tripType) {
        console.log('[WPTB] Testing pricing endpoint...');
        console.log(`Vehicle ID: ${vehicleId}, Distance: ${distance}km, Trip: ${tripType}`);

        $.post(wptb_vars.ajax_url, {
            action: 'wptb_calculate_price',
            vehicle_id: vehicleId || 1,
            distance_km: distance || 50,
            trip_type: tripType || 'one_way',
            duration_minutes: 60
        }, function (response) {
            console.log('[WPTB] Pricing response:', response);
            if (response.success) {
                console.log(`Price: EUR ${response.data.price}`);
            }
        }).fail(function (xhr, status, error) {
            console.error('[WPTB] Pricing AJAX Failed:', error);
        });
    };

    console.log('[WPTB] Available commands:');
    console.log('  testVehiclesEndpoint() - Test getting vehicles');
    console.log('  testPricingEndpoint(vehicleId, distance, tripType) - Test pricing');
    console.log('  checkGoogleMaps() - Check Google Maps API Status');

    window.checkGoogleMaps = function () {
        console.log('[WPTB] Checking Google Maps Status...');
        const script = document.querySelector('script[src*="maps.googleapis.com"]');
        if (script) {
            console.log('[WPTB] Script Tag FOUND:', script.src.substring(0, 50) + '...');
        } else {
            console.error('[WPTB] Script Tag NOT FOUND in DOM');
        }

        if (typeof google !== 'undefined' && google.maps) {
            console.log('[WPTB] Google Maps API Object: READY');
            console.log('Core maps loaded correctly.');
        } else {
            console.error('[WPTB] Google Maps API Object: NOT READY (or undefined)');
        }
    };
});
