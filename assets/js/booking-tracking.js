(function (window, document) {
    'use strict';

    const allowedEvents = new Set([
        'booking_start',
        'route_search',
        'vehicle_select',
        'begin_checkout',
        'add_payment_info',
        'purchase',
        'generate_lead',
        'click_whatsapp',
        'click_phone',
        'booking_error',
        'payment_error'
    ]);

    function cleanParameters(parameters) {
        const clean = {};
        Object.keys(parameters || {}).forEach(function (key) {
            const value = parameters[key];
            if (value !== undefined && value !== null && value !== '') {
                clean[key] = value;
            }
        });
        return clean;
    }

    window.mtBookingTrack = function (eventName, parameters) {
        if (!allowedEvents.has(eventName)) {
            return;
        }

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(Object.assign({ event: eventName }, cleanParameters(parameters)));
    };

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }

        const href = link.getAttribute('href') || '';
        if (/^tel:/i.test(href)) {
            window.mtBookingTrack('click_phone', { booking_context: true });
        } else if (/^(https?:\/\/)?(wa\.me|api\.whatsapp\.com)\//i.test(href)) {
            window.mtBookingTrack('click_whatsapp', { booking_context: true });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('wptb-plugin-container');
        if (!container || container.dataset.paymentState !== 'confirmed') {
            return;
        }

        const transactionId = container.dataset.bookingId || '';
        const value = Number.parseFloat(container.dataset.paymentValue || '0');
        if (!transactionId || !Number.isFinite(value) || value <= 0) {
            return;
        }

        const storageKey = 'mt_purchase_' + transactionId;
        try {
            if (window.localStorage.getItem(storageKey)) {
                return;
            }
            window.localStorage.setItem(storageKey, '1');
        } catch (error) {
            // Tracking must never block a confirmed booking page.
        }

        window.mtBookingTrack('purchase', {
            ecommerce: {
                transaction_id: transactionId,
                value: value,
                currency: container.dataset.paymentCurrency || 'EUR'
            }
        });
    });
})(window, document);
