<?php
$file = 'front-page.php';
$content = file_get_contents($file);

$patterns = [
    '/(<p class="tag">)(Reserva f.*?cil)(<\/p>)/',
    '/(<h2>)(Reserva en pocos minutos y viaja sin complicaciones)(<\/h2>)/',
    '/(<p class="lead"[^>]*>)(El proceso est.*?confirmar tu reserva\.)(<\/p>)/',
    '/(<a href="#panel"[^>]*>)(Iniciar reserva)(<\/a>)/',
    
    '/(<div class="how__n">1<\/div>\s*<div class="how__text">\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>)/s',
    '/(<div class="how__n">2<\/div>\s*<div class="how__text">\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>)/s',
    '/(<div class="how__n">3<\/div>\s*<div class="how__text">\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>)/s',
    '/(<div class="how__n">4<\/div>\s*<div class="how__text">\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>)/s',
];

foreach ($patterns as $pattern) {
    $content = preg_replace_callback($pattern, function($m) {
        if (count($m) == 4) {
            $text = trim($m[2]);
            return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode($text)) . '"); ?>' . $m[3];
        } else if (count($m) == 6) {
            $title = trim($m[2]);
            $desc = trim($m[4]);
            return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode($title)) . '"); ?>' . $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode($desc)) . '"); ?>' . $m[5];
        }
        return $m[0];
    }, $content);
}

file_put_contents($file, $content);
echo "Section 2 done\n";
