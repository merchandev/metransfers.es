<?php
$file_path = 'front-page.php';
$content = file_get_contents($file_path);

$replacements = [
    'Cancelación gratuita hasta 24 h antes' => 'Cancelación sujeta a condiciones',
    'Cancelación gratuita hasta 24 horas antes' => 'Cancelación sujeta a condiciones',
    'Vehículos Mercedes seleccionados para cada servicio' => 'Vehículos premium seleccionados para cada servicio',
    'Vehículos Mercedes seleccionados' => 'Vehículos premium seleccionados',
    'Atención 24/7' => 'Atención 24/7 bajo reserva',
    'Atención 24/7 por teléfono, email y WhatsApp' => 'Atención continuada (bajo reserva)'
];

$content = str_replace(array_keys($replacements), array_values($replacements), $content);
file_put_contents($file_path, $content);

$file_path = 'footer.php';
$content = file_get_contents($file_path);

$replacements = [
    'Cancelación gratuita hasta 24 h antes' => 'Cancelación sujeta a condiciones',
    'Atención personalizada 24/7' => 'Atención 24/7 bajo reserva',
    'Vehículos Mercedes' => 'Vehículos premium',
];

$content = str_replace(array_keys($replacements), array_values($replacements), $content);
file_put_contents($file_path, $content);

echo "Done";
