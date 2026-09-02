(function () {
    'use strict';

    var toggle = document.querySelector('.mt-hotel-menu-toggle');
    var sidebar = document.getElementById('mt-hotel-sidebar');
    var overlay = document.querySelector('.mt-hotel-drawer-overlay');
    if (toggle && sidebar) {
        var setDrawerOpen = function (open, restoreFocus) {
            sidebar.classList.toggle('is-open', open);
            if (overlay) overlay.classList.toggle('is-open', open);
            document.body.classList.toggle('has-open-drawer', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (restoreFocus) toggle.focus();
        };
        toggle.addEventListener('click', function () {
            setDrawerOpen(!sidebar.classList.contains('is-open'), false);
        });
        if (overlay) overlay.addEventListener('click', function () { setDrawerOpen(false, true); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar.classList.contains('is-open')) setDrawerOpen(false, true);
        });
    }

    var hotelSelect = document.querySelector('[data-auto-submit]');
    if (hotelSelect && hotelSelect.form) {
        hotelSelect.addEventListener('change', function () {
            hotelSelect.form.submit();
        });
    }

    var passwordToggle = document.querySelector('.mt-hotel-password-toggle');
    if (passwordToggle) {
        passwordToggle.addEventListener('click', function () {
            var input = document.getElementById(passwordToggle.getAttribute('aria-controls'));
            if (!input) return;
            var visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            passwordToggle.setAttribute('aria-pressed', visible ? 'false' : 'true');
            var labels = window.mtHotelPortal || {};
            passwordToggle.textContent = visible ? (labels.showPassword || 'Mostrar') : (labels.hidePassword || 'Ocultar');
        });
    }
}());
