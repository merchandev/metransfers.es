<?php
/**
 * Banner de Cookies - MeTransfers
 */
?>
<style>
/* Cookie Banner Minimalista */
.mt-cookie-banner {
    position: fixed;
    bottom: 20px;
    left: 20px;
    width: calc(100% - 40px);
    max-width: 360px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    z-index: 99999;
    padding: 20px;
    font-family: var(--ff-body, system-ui, -apple-system, sans-serif);
    transform: translateY(150%);
    opacity: 0;
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mt-cookie-banner.show {
    transform: translateY(0);
    opacity: 1;
}

.mt-cookie-header {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mt-cookie-icon {
    width: 24px;
    height: 24px;
    color: var(--blue, #004E9A);
}

.mt-cookie-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.mt-cookie-text {
    font-size: 0.85rem;
    color: #475569;
    line-height: 1.5;
    margin: 0;
}

.mt-cookie-options {
    display: none; /* Hidden by default */
    flex-direction: column;
    gap: 8px;
    margin-top: 5px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}

.mt-cookie-options.expanded {
    display: flex;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

.mt-cookie-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #334155;
}

.mt-cookie-option label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    margin: 0;
}

.mt-cookie-option input[type="checkbox"] {
    accent-color: var(--blue, #004E9A);
    width: 16px;
    height: 16px;
    cursor: pointer;
    margin: 0;
}

.mt-cookie-option .required {
    font-size: 0.75rem;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
}

.mt-cookie-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.mt-cookie-btn {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s ease;
    border: none;
}

.mt-cookie-btn-accept {
    background: var(--blue, #004E9A);
    color: #ffffff;
}

.mt-cookie-btn-accept:hover {
    background: var(--deep, #0A2744);
}

.mt-cookie-btn-settings {
    background: #f1f5f9;
    color: #475569;
}

.mt-cookie-btn-settings:hover {
    background: #e2e8f0;
}

@media (max-width: 480px) {
    .mt-cookie-banner {
        bottom: 15px;
        left: 15px;
        width: calc(100% - 30px);
        max-width: none;
    }
}
</style>

<div id="mt-cookie-banner" class="mt-cookie-banner">
    <div class="mt-cookie-header">
        <svg class="mt-cookie-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
            <path d="M8.5 8.5v.01"></path>
            <path d="M16 12.5v.01"></path>
            <path d="M12 16v.01"></path>
            <path d="M11 12.5v.01"></path>
        </svg>
        <h3 class="mt-cookie-title"><?php echo mt_translate('Gestión de Cookies'); ?></h3>
    </div>
    
    <p class="mt-cookie-text">
        Utilizamos cookies propias y de terceros para asegurar el correcto funcionamiento de la web, personalizar su experiencia y analizar el tráfico.
    </p>

    <div id="mt-cookie-options" class="mt-cookie-options">
        <div class="mt-cookie-option">
            <label>
                <input type="checkbox" checked disabled>
                Técnicas (Necesarias)
            </label>
            <span class="required"><?php echo mt_translate('Obligatorio'); ?></span>
        </div>
        <div class="mt-cookie-option">
            <label>
                <input type="checkbox" id="mt-cookie-analytics">
                Analíticas y Rendimiento
            </label>
        </div>
        <div class="mt-cookie-option">
            <label>
                <input type="checkbox" id="mt-cookie-marketing">
                Marketing y Publicidad
            </label>
        </div>
    </div>

    <div class="mt-cookie-actions" style="flex-wrap: wrap;">
        <button id="mt-btn-cookie-reject" class="mt-cookie-btn mt-cookie-btn-settings" style="flex-basis: 100%; margin-bottom: 4px;"><?php echo mt_translate('Rechazar todas'); ?></button>
        <button id="mt-btn-cookie-settings" class="mt-cookie-btn mt-cookie-btn-settings"><?php echo mt_translate('Configurar'); ?></button>
        <button id="mt-btn-cookie-accept" class="mt-cookie-btn mt-cookie-btn-accept"><?php echo mt_translate('Aceptar todas'); ?></button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('mt-cookie-banner');
    const btnSettings = document.getElementById('mt-btn-cookie-settings');
    const btnAccept = document.getElementById('mt-btn-cookie-accept');
    const btnReject = document.getElementById('mt-btn-cookie-reject');
    const optionsPanel = document.getElementById('mt-cookie-options');
    
    const cookieName = 'mt_cookie_consent_v2';
    // 14 días en segundos
    const maxAge = 14 * 24 * 60 * 60; 

    // Comprobar si ya existe la cookie
    const hasConsent = document.cookie.split('; ').find(row => row.startsWith(cookieName + '='));

    if (!hasConsent) {
        // Mostrar el banner
        setTimeout(() => {
            if (banner) {
                banner.classList.add('show');
            }
        }, 500); // Mostramos antes en vez de a los 5s, porque ya no hay tácito
    }

    // Interacción: Configurar / Guardar
    if (btnSettings) {
        btnSettings.addEventListener('click', function() {
            if (optionsPanel.classList.contains('expanded')) {
                // Si ya está expandido, funciona como "Guardar selección"
                saveConsent();
            } else {
                // Expandir panel
                optionsPanel.classList.add('expanded');
                btnSettings.textContent = 'Guardar selección';
            }
        });
    }

    // Interacción: Aceptar todas
    if (btnAccept) {
        btnAccept.addEventListener('click', function() {
            document.getElementById('mt-cookie-analytics').checked = true;
            document.getElementById('mt-cookie-marketing').checked = true;
            saveConsent();
        });
    }

    // Interacción: Rechazar todas
    if (btnReject) {
        btnReject.addEventListener('click', function() {
            document.getElementById('mt-cookie-analytics').checked = false;
            document.getElementById('mt-cookie-marketing').checked = false;
            saveConsent();
        });
    }

    function saveConsent() {
        const analytics = document.getElementById('mt-cookie-analytics').checked;
        const marketing = document.getElementById('mt-cookie-marketing').checked;
        
        const consentData = {
            necessary: true,
            analytics: analytics,
            marketing: marketing,
            timestamp: new Date().toISOString()
        };

        // Guardar cookie por 14 días
        document.cookie = `${cookieName}=${encodeURIComponent(JSON.stringify(consentData))}; max-age=${maxAge}; path=/; samesite=lax`;

        // Actualizar Consent Mode v2 de Google
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': marketing ? 'granted' : 'denied',
                'ad_user_data': marketing ? 'granted' : 'denied',
                'ad_personalization': marketing ? 'granted' : 'denied',
                'analytics_storage': analytics ? 'granted' : 'denied'
            });
        }

        // Si se necesitan disparar eventos adicionales:
        if (analytics) {
            document.dispatchEvent(new CustomEvent('mt_analytics_granted'));
        }
        if (marketing) {
            document.dispatchEvent(new CustomEvent('mt_marketing_granted'));
        }

        // Ocultar banner
        banner.classList.remove('show');
        
        // Quitarlo del DOM tras la animación
        setTimeout(() => {
            banner.remove();
        }, 600);
    }
});
</script>
