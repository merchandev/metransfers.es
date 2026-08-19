<?php
$file = 'footer.php';
$content = file_get_contents($file);

$replacements = [
    "\n\t\t\t\tPago seguro\n" => "\n\t\t\t\t<?php echo mt_translate('Pago seguro'); ?>\n",
    "\n\t\t\t\tOpiniones verificadas en GetYourGuide\n" => "\n\t\t\t\t<?php echo mt_translate('Opiniones verificadas en GetYourGuide'); ?>\n",
    'Aviso legal</a>' => '<?php echo mt_translate("Aviso legal"); ?></a>',
];

// Special characters using regex
$content = str_replace(array_keys($replacements), array_values($replacements), $content);

$content = preg_replace('/(>)\s*Cancelaci.*n sujeta a condiciones\s*(<\/span>)/', '$1<?php echo mt_translate("Cancelación sujeta a condiciones"); ?>$2', $content);
$content = preg_replace('/(>)\s*Pol.*tica de privacidad\s*(<\/a>)/', '$1<?php echo mt_translate("Política de privacidad"); ?>$2', $content);
$content = preg_replace('/(>)\s*Pol.*tica de cookies\s*(<\/a>)/', '$1<?php echo mt_translate("Política de cookies"); ?>$2', $content);
$content = preg_replace('/(>)\s*T.*rminos y condiciones\s*(<\/a>)/', '$1<?php echo mt_translate("Términos y condiciones"); ?>$2', $content);

file_put_contents($file, $content);
