<?php
$file = 'front-page.php';
$content = file_get_contents($file);

$reps = [
    '>Presupuestar y reservar<' => '><?php echo mt_translate("Presupuestar y reservar"); ?><',
    '>Consultar por WhatsApp<' => '><?php echo mt_translate("Consultar por WhatsApp"); ?><',
];

foreach ($reps as $k => $v) {
    $content = str_replace($k, $v, $content);
}

file_put_contents($file, $content);
