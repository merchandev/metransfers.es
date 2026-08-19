<?php
$file = 'footer.php';
$content = file_get_contents($file);

$reps = [
    'Traslados privados, tours y servicios con chfer en Barcelona y larga distancia. Vehculos premium y atencin personalizada 24/7.' => '<?php echo mt_translate("Traslados privados, tours y servicios con chófer en Barcelona y larga distancia. Vehículos premium y atención personalizada 24/7."); ?>',
    'Servicios</button>' => '<?php echo mt_translate("Servicios"); ?></button>',
    'Aeropuerto</a>' => '<?php echo mt_translate("Aeropuerto"); ?></a>',
    'Puerto</a>' => '<?php echo mt_translate("Puerto"); ?></a>',
    'Por horas</a>' => '<?php echo mt_translate("Por horas"); ?></a>',
    'Empresas</a>' => '<?php echo mt_translate("Empresas"); ?></a>',
    'Grupos</a>' => '<?php echo mt_translate("Grupos"); ?></a>',
    'Destinos y rutas</button>' => '<?php echo mt_translate("Destinos y rutas"); ?></button>',
    'Traslados privados Barcelona</a>' => '<?php echo mt_translate("Traslados privados Barcelona"); ?></a>',
    'Costa Brava</a>' => '<?php echo mt_translate("Costa Brava"); ?></a>',
    'Salou</a>' => '<?php echo mt_translate("Salou"); ?></a>',
    'PortAventura</a>' => '<?php echo mt_translate("PortAventura"); ?></a>',
    'Girona</a>' => '<?php echo mt_translate("Girona"); ?></a>',
    'Todas las rutas</a>' => '<?php echo mt_translate("Todas las rutas"); ?></a>',
    'Informacin</button>' => '<?php echo mt_translate("Información"); ?></button>',
    'Sobre nosotros</a>' => '<?php echo mt_translate("Sobre nosotros"); ?></a>',
    'Preguntas frecuentes</a>' => '<?php echo mt_translate("Preguntas frecuentes"); ?></a>',
    'Contact</a>' => '<?php echo mt_translate("Contacto"); ?></a>',
    'Necesitas ayuda con tu reserva?' => '<?php echo mt_translate("¿Necesitas ayuda con tu reserva?"); ?>',
    'Atencin por telfono y WhatsApp, 24 horas.' => '<?php echo mt_translate("Atención por teléfono y WhatsApp, 24 horas."); ?>',
    'Hablar por WhatsApp' => '<?php echo mt_translate("Hablar por WhatsApp"); ?>',
];

foreach ($reps as $k => $v) {
    // try to match with or without the encoding issue
    $content = str_replace($k, $v, $content);
}

file_put_contents($file, $content);
