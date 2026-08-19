<?php
$file = 'front-page.php';
$content = file_get_contents($file);

$patterns = [
    '/(<p class="tag">)(La experiencia MeTransfers)(<\/p>)/',
    '/(<h2>)(Puntualidad, comodidad y atenci.*?n en cada trayecto)(<\/h2>)/',
    '/(<p class="lead"[^>]*>)(No solo te llevamos de un punto a otro.*?cuando la necesites\.)(<\/p>)/s',
    '/(<p class="vent__note">)(Puedes solicitar sillas infantiles.*?para cada servicio\.)(<\/p>)/s',
];

foreach ($patterns as $pattern) {
    $content = preg_replace_callback($pattern, function($m) {
        $text = trim($m[2]);
        return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode($text)) . '"); ?>' . $m[3];
    }, $content);
}

// For the bullets inside ul.vent__list
$content = preg_replace_callback('/(<ul class="vent__list">\s*<li>.*?<\/svg>\s*)(.*?)(<\/li>\s*<li>.*?<\/svg>\s*)(.*?)(<\/li>\s*<li>.*?<\/svg>\s*)(.*?)(<\/li>\s*<li>.*?<\/svg>\s*)(.*?)(<\/li>\s*<li>.*?<\/svg>\s*)(.*?)(<\/li>\s*<li>.*?<\/svg>\s*)(.*?)(<\/li>\s*<\/ul>)/s', function($m) {
    return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[2]))) . '"); ?>' . 
           $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[4]))) . '"); ?>' . 
           $m[5] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[6]))) . '"); ?>' . 
           $m[7] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[8]))) . '"); ?>' . 
           $m[9] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[10]))) . '"); ?>' . 
           $m[11] . '<?php echo mt_translate("' . addslashes(html_entity_decode(trim($m[12]))) . '"); ?>' . 
           $m[13];
}, $content);

file_put_contents($file, $content);
echo "Section 3 done\n";
