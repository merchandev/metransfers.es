<?php
$file = 'front-page.php';
$content = file_get_contents($file);

$patterns = [
    '/(<p class="tag tc"[^>]*>)(Rutas y destinos m.*?s solicitados)(<\/p>)/',
    '/(<h3.*?>)(Aeropuerto de Barcelona .*? centro)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<\/div>\s*<span class="route__time">)(.*?)(<\/span>)/s',
    '/(<h3.*?>)(Aeropuerto de Barcelona .*? Puerto)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<\/div>\s*<span class="route__time">)(.*?)(<\/span>)/s',
    '/(<h3.*?>)(Barcelona .*? Costa Brava)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<\/div>\s*<span class="route__time">)(.*?)(<\/span>)/s',
    '/(<h3.*?>)(Barcelona .*? Girona)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<\/div>\s*<span class="route__time">)/s', // Time is dynamic
];

// Let's replace the fixed Rutas tags
$content = preg_replace_callback('/(<p class="tag tc"[^>]*>)(Rutas y destinos m.*?s solicitados)(<\/p>)/', function($m) {
    return $m[1] . '<?php echo mt_translate("Rutas y destinos más solicitados"); ?>' . $m[3];
}, $content);

$content = preg_replace_callback('/(<h3.*?>)(Aeropuerto de Barcelona [^<]*? centro)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<\/div>\s*<span class="route__time">)(.*?)(<\/span>)/s', function($m) {
    return $m[1] . '<?php echo mt_translate("Aeropuerto de Barcelona — centro"); ?>' . $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[4]))) . '"); ?>' . $m[5] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[6]))) . '"); ?>' . $m[7];
}, $content);

$content = preg_replace_callback('/(<h3.*?>)(Aeropuerto de Barcelona [^<]*? Puerto)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<\/div>\s*<span class="route__time">)(.*?)(<\/span>)/s', function($m) {
    return $m[1] . '<?php echo mt_translate("Aeropuerto de Barcelona — Puerto"); ?>' . $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[4]))) . '"); ?>' . $m[5] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[6]))) . '"); ?>' . $m[7];
}, $content);

$content = preg_replace_callback('/(<h3.*?>)(Barcelona [^<]*? Costa Brava)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<\/div>\s*<span class="route__time">)(.*?)(<\/span>)/s', function($m) {
    return $m[1] . '<?php echo mt_translate("Barcelona — Costa Brava"); ?>' . $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[4]))) . '"); ?>' . $m[5] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[6]))) . '"); ?>' . $m[7];
}, $content);

$content = preg_replace_callback('/(<h3.*?>)(Barcelona [^<]*? Girona)(<\/h3>\s*<p>)(.*?)(<\/p>)/s', function($m) {
    return $m[1] . '<?php echo mt_translate("Barcelona — Girona"); ?>' . $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[4]))) . '"); ?>' . $m[5];
}, $content);

// Tours
$content = preg_replace_callback('/(<p class="tag tc"[^>]*>)(Tours y excursiones desde Barcelona)(<\/p>)/', function($m) {
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . $m[3];
}, $content);
$content = preg_replace_callback('/(<h2 class="tc">)(Descubre Catalu.*?a con ch.*?fer privado)(<\/h2>)/', function($m) {
    return $m[1] . '<?php echo mt_translate("Descubre Cataluña con chófer privado"); ?>' . $m[3];
}, $content);

$content = preg_replace_callback('/(<div class="tour__content">\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<a href=".*?" class="tour__cta-btn">)(.*?)(<\/a>)/s', function($m) {
    // If it already has php inside, ignore
    if (strpos($m[2], '<?php') !== false) return $m[0];
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[4]))) . '"); ?>' . $m[5] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[6]))) . '"); ?>' . $m[7];
}, $content);

$content = str_replace('>Ver todos los tours<', '><?php echo mt_translate("Ver todos los tours"); ?><', $content);

// Opinions
$content = str_replace('>Opiniones de viajeros<', '><?php echo mt_translate("Opiniones de viajeros"); ?><', $content);
$content = str_replace('>La confianza se gana en cada recogida<', '><?php echo mt_translate("La confianza se gana en cada recogida"); ?><', $content);
$content = preg_replace_callback('/(<p class="lead tc"[^>]*>)(Algunas experiencias de clientes.*?)(<\/p>)/', function($m) {
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . $m[3];
}, $content);
$content = str_replace('>Opiniones verificadas en GetYourGuide &rarr; Ver opiniones<', '><?php echo mt_translate("Opiniones verificadas en GetYourGuide"); ?> &rarr; <?php echo mt_translate("Ver opiniones"); ?><', $content);
$content = preg_replace_callback('/(<p class="rev__quote">)(.*?)(<\/p>)/s', function($m) {
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . $m[3];
}, $content);
$content = preg_replace_callback('/(<span class="rev__meta">)(.*?)(<\/span>)/s', function($m) {
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . $m[3];
}, $content);

// FAQ
$content = str_replace('>Preguntas frecuentes<', '><?php echo mt_translate("Preguntas frecuentes"); ?><', $content);
$content = str_replace('>Todo lo que necesitas saber antes de reservar<', '><?php echo mt_translate("Todo lo que necesitas saber antes de reservar"); ?><', $content);
$content = preg_replace_callback('/(<p class="lead tc"[^>]*>)(Condiciones principales de nuestros.*?)(<\/p>)/', function($m) {
    return $m[1] . '<?php echo mt_translate("Condiciones principales de nuestros traslados privados, chófer por horas y tours con salida desde Barcelona."); ?>' . $m[3];
}, $content);
$content = preg_replace_callback('/(<button type="button" class="faq__q"[^>]*>\s*)(.*?)(?=\s*<span class="faq__icon")/s', function($m) {
    if (strpos($m[2], '<?php') !== false) return $m[0];
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . "\n          ";
}, $content);
$content = preg_replace_callback('/(<div class="faq__a"[^>]*>)(.*?)(<\/div>)/s', function($m) {
    if (strpos($m[2], '<?php') !== false) return $m[0];
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . $m[3];
}, $content);

file_put_contents($file, $content);
echo "Done\n";
