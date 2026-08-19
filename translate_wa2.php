<?php
$file = 'footer.php';
$content = file_get_contents($file);

$replacements = [
    '<p class="footer-help-text">Necesitas ayuda con tu reserva?<br>Atencin por telǸfono y WhatsApp, 24 horas.</p>' => '<p class="footer-help-text"><?php echo mt_translate("¿Necesitas ayuda con tu reserva?"); ?><br><?php echo mt_translate("Atención por teléfono y WhatsApp, 24 horas."); ?></p>',
    "Hablar por WhatsApp\n\t\t\t\t</button>" => "<?php echo mt_translate('Hablar por WhatsApp'); ?>\n\t\t\t\t</button>"
];

// Let's use preg_replace instead to be safe from mojibake differences

$content = preg_replace(
    '/<p class="footer-help-text">.*?Necesitas ayuda con tu reserva\?<br>Atenci.*?n por tel.*?fono y WhatsApp, 24 horas.<\/p>/s',
    '<p class="footer-help-text"><?php echo mt_translate("¿Necesitas ayuda con tu reserva?"); ?><br><?php echo mt_translate("Atención por teléfono y WhatsApp, 24 horas."); ?></p>',
    $content
);

$content = preg_replace(
    '/\s*Hablar por WhatsApp\s*<\/button>/s',
    "\n\t\t\t\t\t<?php echo mt_translate('Hablar por WhatsApp'); ?>\n\t\t\t\t</button>",
    $content
);

file_put_contents($file, $content);
