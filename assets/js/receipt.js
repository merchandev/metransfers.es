(function (window, document) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const button = document.querySelector('[data-print-receipt]');
        if (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        }
    });
})(window, document);
