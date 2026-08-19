<?php
$file = 'footer.php';
$content = file_get_contents($file);

$content = preg_replace('/(<button type="button" class="footer-col-title"[^>]*>)Informaci.*?n(<\/button>)/s', '$1<?php echo mt_translate("Información"); ?>$2', $content);

file_put_contents($file, $content);
