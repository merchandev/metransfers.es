<?php
$file = 'front-page.php';
$content = file_get_contents($file);

$patterns = [
    '/(<option value="">)\s*Selecciona una opci.*?n\s*(<\/option>)/' => '$1<?php echo mt_translate("Selecciona una opción"); ?>$2',
    '/(<option value="horas">)\s*Ch.*?fer por horas\s*(<\/option>)/' => '$1<?php echo mt_translate("Chófer por horas"); ?>$2',
    '/(<option value="corporativo">)\s*Traslados corporativos\s*(<\/option>)/' => '$1<?php echo mt_translate("Traslados corporativos"); ?>$2',
    '/(<option value="tours">)\s*Tours privados\s*(<\/option>)/' => '$1<?php echo mt_translate("Tours privados"); ?>$2',
    '/(<option value="otro">)\s*Otro\s*(<\/option>)/' => '$1<?php echo mt_translate("Otro"); ?>$2',
    '/(<span class="terms-text">)\s*He le.*?do y acepto la Pol.*?tica de Privacidad y el tratamiento de mis datos\.\s*(<\/span>)/s' => '$1<?php echo mt_translate("He leído y acepto la Política de Privacidad y el tratamiento de mis datos."); ?>$2',
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

file_put_contents($file, $content);
echo "Replaced missing form strings in front-page.php\n";
