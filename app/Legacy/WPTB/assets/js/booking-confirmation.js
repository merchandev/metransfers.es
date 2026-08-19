(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('wptb-plugin-container');
        if (!container || container.dataset.paymentState !== 'confirmed') {
            return;
        }

        try {
            window.sessionStorage.removeItem('wptb_booking_data');
        } catch (error) {
            // Storage availability must never affect a confirmed receipt.
        }
    });
})(window, document);
