(function () {
    'use strict';

    var switcher = document.getElementById('mt-lang-switcher');
    if (!switcher) {
        return;
    }

    var trigger = switcher.querySelector('.mt-lang-trigger');
    var menu = switcher.querySelector('.mt-lang-menu');
    if (!trigger || !menu) {
        return;
    }

    document.body.appendChild(menu);

    var backdrop = document.createElement('div');
    backdrop.className = 'mt-lang-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    document.body.appendChild(backdrop);

    var closeButton = menu.querySelector('.mt-lang-close');

    function isOpen() {
        return menu.classList.contains('open');
    }

    function close(returnFocus) {
        if (!isOpen()) {
            return;
        }
        switcher.classList.remove('open');
        menu.classList.remove('open');
        backdrop.classList.remove('open');
        document.body.classList.remove('mt-lang-open');
        trigger.setAttribute('aria-expanded', 'false');
        if (returnFocus) {
            trigger.focus();
        }
    }

    function open() {
        switcher.classList.add('open');
        menu.classList.add('open');
        backdrop.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
        if (window.innerWidth <= 991) {
            document.body.classList.add('mt-lang-open');
        }
        if (closeButton) {
            window.setTimeout(function () {
                closeButton.focus();
            }, 0);
        }
    }

    /**
     * Guarda la preferencia de idioma en una cookie (30 días).
     * PHP la leerá en Language::boot() para redirigir al usuario
     * cuando llegue a una URL sin prefijo de idioma.
     */
    function saveLangCookie(langCode) {
        var expires = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString();
        document.cookie = 'mt_preferred_lang=' + langCode + '; path=/; expires=' + expires + '; SameSite=Lax';
    }

    // Guardar preferencia al hacer clic en cualquier enlace de idioma
    menu.querySelectorAll('a[href]').forEach(function (link) {
        link.addEventListener('click', function () {
            var href = link.getAttribute('href') || '';
            // Extraer el código de idioma del href: /en/contacto → 'en', / → 'es'
            var match = href.match(/\/([a-z]{2})\//);
            var langCode = match ? match[1] : 'es';
            saveLangCookie(langCode);
        });
    });

    trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        if (isOpen()) {
            close(true);
        } else {
            open();
        }
    });

    if (closeButton) {
        closeButton.addEventListener('click', function () {
            close(true);
        });
    }
    backdrop.addEventListener('click', function () {
        close(true);
    });
    document.addEventListener('keydown', function (event) {
        if ('Escape' === event.key) {
            close(true);
        }
    });
    menu.addEventListener('click', function (event) {
        event.stopPropagation();
    });
}());

