<?php
$file = 'footer.php';
$content = file_get_contents($file);

// Use preg_replace to catch the strings even with mojibake
$reps = [
    '/(<p class="footer-brand-desc">)(.*?)(<\/p>)/s' => '$1<?php echo mt_translate("Traslados privados, tours y servicios con chófer en Barcelona y larga distancia. Vehículos premium y atención personalizada 24/7."); ?>$3',
    '/(<button type="button" class="footer-col-title"[^>]*>)Servicios(<\/button>)/' => '$1<?php echo mt_translate("Servicios"); ?>$2',
    '/(>)\s*Aeropuerto\s*(<\/a>)/' => '$1<?php echo mt_translate("Aeropuerto"); ?>$2',
    '/(>)\s*Puerto\s*(<\/a>)/' => '$1<?php echo mt_translate("Puerto"); ?>$2',
    '/(>)\s*Por horas\s*(<\/a>)/' => '$1<?php echo mt_translate("Por horas"); ?>$2',
    '/(>)\s*Empresas\s*(<\/a>)/' => '$1<?php echo mt_translate("Empresas"); ?>$2',
    '/(>)\s*Grupos\s*(<\/a>)/' => '$1<?php echo mt_translate("Grupos"); ?>$2',
    '/(<button type="button" class="footer-col-title"[^>]*>)Destinos y rutas(<\/button>)/' => '$1<?php echo mt_translate("Destinos y rutas"); ?>$2',
    '/(>)\s*Traslados privados Barcelona\s*(<\/a>)/' => '$1<?php echo mt_translate("Traslados privados Barcelona"); ?>$2',
    '/(>)\s*Costa Brava\s*(<\/a>)/' => '$1<?php echo mt_translate("Costa Brava"); ?>$2',
    '/(>)\s*Salou\s*(<\/a>)/' => '$1<?php echo mt_translate("Salou"); ?>$2',
    '/(>)\s*PortAventura\s*(<\/a>)/' => '$1<?php echo mt_translate("PortAventura"); ?>$2',
    '/(>)\s*Girona\s*(<\/a>)/' => '$1<?php echo mt_translate("Girona"); ?>$2',
    '/(>)\s*Todas las rutas\s*(<\/a>)/' => '$1<?php echo mt_translate("Todas las rutas"); ?>$2',
    '/(<button type="button" class="footer-col-title"[^>]*>)Informaci.n(<\/button>)/' => '$1<?php echo mt_translate("Información"); ?>$2',
    '/(>)\s*Sobre nosotros\s*(<\/a>)/' => '$1<?php echo mt_translate("Sobre nosotros"); ?>$2',
    '/(>)\s*Preguntas frecuentes\s*(<\/a>)/' => '$1<?php echo mt_translate("Preguntas frecuentes"); ?>$2',
    '/(>)\s*Contact\s*(<\/a>)/' => '$1<?php echo mt_translate("Contacto"); ?>$2',
    '/(<p class="footer-wa-title">).*?(<\/p>)/s' => '$1<?php echo mt_translate("¿Necesitas ayuda con tu reserva?"); ?>$2',
    '/(<p class="footer-wa-desc">).*?(<\/p>)/s' => '$1<?php echo mt_translate("Atención por teléfono y WhatsApp, 24 horas."); ?>$2',
    '/(<span class="footer-wa-btn-text">).*?(<\/span>)/s' => '$1<?php echo mt_translate("Hablar por WhatsApp"); ?>$2',
    '/(<span class="trust-stat">).*?(<\/span>)/s' => '$1<?php echo mt_translate("Opiniones verificadas en GetYourGuide"); ?>$2',
];

foreach ($reps as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

file_put_contents($file, $content);
