(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('wptb-plugin-container');
        if (!container || container.dataset.paymentState !== 'confirmed') {
            return;
        }

        const transactionId = container.dataset.bookingId || '';
        const value = Number.parseFloat(container.dataset.paymentValue || '0');
        if (!transactionId || !Number.isFinite(value) || value <= 0 || typeof window.mtBookingTrack !== 'function') {
            return;
        }

        const storageKey = 'mt_purchase_' + transactionId;
        try {
            if (window.localStorage.getItem(storageKey)) {
                return;
            }
            window.localStorage.setItem(storageKey, '1');
        } catch (error) {
            // Analytics must never block a confirmed booking page.
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
