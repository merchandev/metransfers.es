<?php
$file = 'front-page.php';
$content = file_get_contents($file);

$patterns = [
    '/(<p class="tag tc"[^>]*>)(Flota premium)(<\/p>)/',
    '/(<h2 class="tc">)(El espacio y el confort adecuados para cada reserva)(<\/h2>)/',
    '/(<p class="lead tc"[^>]*>)(Asignamos el veh.*?seguridad\.)(<\/p>)/s',
];

foreach ($patterns as $pattern) {
    $content = preg_replace_callback($pattern, function($m) {
        $text = trim($m[2]);
        return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode($text)) . '"); ?>' . $m[3];
    }, $content);
}

// Cars logic:
// <span class="fleet__cat">Berlina ejecutiva</span>
// <h3>ECONOMIC CLASS</h3>
// <p>Cmoda y elegante para traslados de aeropuerto, hoteles, reuniones y recorridos urbanos.</p>
$content = preg_replace_callback('/(<span class="fleet__cat">)(.*?)(<\/span>\s*<h3>.*?<\/h3>\s*<p>)(.*?)(<\/p>)/s', function($m) {
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . 
           $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[4]))) . '"); ?>' . 
           $m[5];
}, $content);

// specs:
$content = preg_replace_callback('/(<span class="fleet__spec">.*?<\/svg>\s*)(Hasta \d pasajeros)(<\/span>)/', function($m) {
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . $m[3];
}, $content);

$content = preg_replace_callback('/(<span class="fleet__spec">.*?<\/svg>\s*)(Equipaje seg.*?n configuraci.*?n)(<\/span>)/', function($m) {
    return $m[1] . '<?php echo mt_translate("Equipaje según configuración"); ?>' . $m[3];
}, $content);


file_put_contents($file, $content);
echo "Flota done\n";
