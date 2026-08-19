<?php
$file = 'footer.php';
$content = file_get_contents($file);
$content = preg_replace('/(>)\s*He le.do y acepto la pol.tica de privacidad.\s*(<\/span>)/', '$1<?php echo mt_translate("He leído y acepto la política de privacidad."); ?>$2', $content);
file_put_contents($file, $content);
