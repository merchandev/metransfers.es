<?php
$file = 'header.php';
$content = file_get_contents($file);

$reps = [
    '>Reservar Ya<' => '><?php echo mt_translate("Reservar Ya"); ?><',
    'aria-label="Abrir menǧ"' => 'aria-label="<?php echo esc_attr(mt_translate("Abrir menú")); ?>"',
    'aria-label="Abrir menú"' => 'aria-label="<?php echo esc_attr(mt_translate("Abrir menú")); ?>"',
];

foreach ($reps as $k => $v) {
    $content = str_replace($k, $v, $content);
}

file_put_contents($file, $content);
