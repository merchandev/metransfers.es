<?php
$file = 'front-page.php';
$content = file_get_contents($file);

$patterns = [
    // Tours
    '/(<p class="tag tc"[^>]*>)\s*Tours y excursiones privadas\s*(<\/p>)/' => '$1<?php echo mt_translate("Tours y excursiones privadas"); ?>$2',
    '/(<h2 class="tc">)\s*Descubre Catalu.*?a a tu ritmo\s*(<\/h2>)/' => '$1<?php echo mt_translate("Descubre Cataluña a tu ritmo"); ?>$2',
    '/(<p class="lead tc"[^>]*>)\s*Recogida puerta a puerta.*?acompa.*?antes\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Recogida puerta a puerta, horarios flexibles y vehículo premium reservado solo para ti y tus acompañantes."); ?>$2',
    
    // CTA
    '/(<p class="tag">)\s*Reserva tu pr.*?ximo traslado\s*(<\/p>)/' => '$1<?php echo mt_translate("Reserva tu próximo traslado"); ?>$2',
    '/(<p class="cta__lead">)\s*Indica el origen, el destino.*?tu trayecto\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Indica el origen, el destino, la fecha y la hora. Te mostraremos las opciones disponibles para que reserves el vehículo que mejor se adapta a tu trayecto."); ?>$2',
    
    // Contacto
    '/(<p class="tag">)\s*Atenci.*?n personalizada\s*(<\/p>)/' => '$1<?php echo mt_translate("Atención personalizada"); ?>$2',
    '/(<h2>).*?(<\?php echo mt_translate\(".*?\?\s*Necesitas ayuda.*?"\); \?>)(<\/h2>)/' => '$1<?php echo mt_translate("¿Necesitas ayuda para organizar tu traslado?"); ?>$3',
    '/(<h2>).*?(Necesitas ayuda para organizar tu traslado\?).*?(<\/h2>)/' => '$1<?php echo mt_translate("¿Necesitas ayuda para organizar tu traslado?"); ?>$3',
    '/(<p class="lead"[^>]*>)\s*Cu.*?ntanos la ruta.*?lo antes posible\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Cuéntanos la ruta, la fecha, los pasajeros y cualquier necesidad especial. Te respondemos lo antes posible."); ?>$2',
    
    // Contact Info labels
    '/(<div class="contact__label">)\s*Tel.*?fono y WhatsApp\s*(<\/div>)/' => '$1<?php echo mt_translate("Teléfono y WhatsApp"); ?>$2',
    '/(<div class="contact__label">)\s*Ubicaci.*?n\s*(<\/div>)/' => '$1<?php echo mt_translate("Ubicación"); ?>$2',
    '/(<span class="contact__val".*?>)\s*Barcelona, Espa.*?a\s*(<\/span>)/' => '$1<?php echo mt_translate("Barcelona, España"); ?>$2',
    '/(<div class="contact__label">)\s*Horario de atenci.*?n\s*(<\/div>)/' => '$1<?php echo mt_translate("Horario de atención"); ?>$2',
    '/(<span class="contact__val".*?>)\s*24 horas, 7 d.*?as a la semana\s*(<\/span>)/' => '$1<?php echo mt_translate("24 horas, 7 días a la semana"); ?>$2',
    
    // Form fields
    '/(<label for="contact-email">)\s*Correo electr.*?nico\s*(<\/label>)/' => '$1<?php echo mt_translate("Correo electrónico"); ?>$2',
    '/(<label for="contact-telefono">)\s*Tel.*?fono\s*(<\/label>)/' => '$1<?php echo mt_translate("Teléfono"); ?>$2',
    '/(<label for="contact-servicio">)\s*Qu.*? servicio necesitas\?\s*(<\/label>)/' => '$1<?php echo mt_translate("¿Qué servicio necesitas?"); ?>$2',
    
    // Select options
    '/(<option value="" disabled selected>)\s*Selecciona una opci.*?n\s*(<\/option>)/' => '$1<?php echo mt_translate("Selecciona una opción"); ?>$2',
    '/(<option value="Traslado Privado">)\s*Traslado Privado\s*(<\/option>)/' => '$1<?php echo mt_translate("Traslado Privado"); ?>$2',
    '/(<option value="Disposici.*?n por horas">)\s*Disposici.*?n por horas\s*(<\/option>)/' => '$1<?php echo mt_translate("Disposición por horas"); ?>$2',
    '/(<option value="Tour \/ Excursi.*?n">)\s*Tour \/ Excursi.*?n\s*(<\/option>)/' => '$1<?php echo mt_translate("Tour / Excursión"); ?>$2',
    '/(<option value="Empresas \/ Grupos">)\s*Empresas \/ Grupos\s*(<\/option>)/' => '$1<?php echo mt_translate("Empresas / Grupos"); ?>$2',
    '/(<option value="Otro">)\s*Otro\s*(<\/option>)/' => '$1<?php echo mt_translate("Otro"); ?>$2',
    
    // GDPR
    '/(<span[^>]*>)\s*He le.*?do y acepto la Pol.*?tica de Privacidad y el tratamiento de mis datos\.\s*(<\/span>)/s' => '$1<?php echo mt_translate("He leído y acepto la Política de Privacidad y el tratamiento de mis datos."); ?>$2',
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

// Special case for the broken H2 tags with mojibake
$content = preg_replace('/<h2>\s*<\?php echo mt_translate\("Necesitas ayuda para organizar tu traslado\?"\); \?>\s*<\/h2>/', '<h2><?php echo mt_translate("¿Necesitas ayuda para organizar tu traslado?"); ?></h2>', $content);

file_put_contents($file, $content);
echo "Replaced missing strings in front-page.php\n";
