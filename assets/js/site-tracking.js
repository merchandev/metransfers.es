(function (window, document) {
    'use strict';

    const allowedEvents = new Set([
        'booking_start', 'route_search', 'vehicle_select', 'begin_checkout',
        'add_payment_info', 'purchase', 'generate_lead', 'click_whatsapp',
        'click_phone', 'booking_error', 'payment_error'
    ]);

    function clean(parameters) {
        return Object.keys(parameters || {}).reduce(function (result, key) {
            const value = parameters[key];
            if (value !== undefined && value !== null && value !== '') {
                result[key] = value;
            }
            return result;
        }, {});
    }

    window.mtBookingTrack = function (eventName, parameters) {
        if (!allowedEvents.has(eventName)) {
            return;
        }
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(Object.assign({event: eventName}, clean(parameters)));
    };

    window.mtAnalyticsClientId = function () {
        const match = document.cookie.match(/(?:^|;\s*)_ga=GA\d+\.\d+\.(\d+\.\d+)/);
        return match ? match[1] : '';
    };

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }
        const href = link.getAttribute('href') || '';
        if (/^tel:/i.test(href)) {
            window.mtBookingTrack('click_phone', {site_context: window.location.pathname});
        } else if (/^(https?:\/\/)?(wa\.me|api\.whatsapp\.com)\//i.test(href)) {
            window.mtBookingTrack('click_whatsapp', {site_context: window.location.pathname});
        }
    });
})(window, document);
