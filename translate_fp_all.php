<?php
$file = 'front-page.php';
$content = file_get_contents($file);

$reps = [
    // Section 1
    'Transfer Aeropuerto de Barcelona: llegadas y salidas' => '<?php echo mt_translate("Transfer Aeropuerto de Barcelona: llegadas y salidas"); ?>',
    '/(>)\s*Te recogemos en El Prat para llevarte al centro.*?horario de tu vuelo\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Te recogemos en El Prat para llevarte al centro de Barcelona, tu hotel, el puerto o cualquier otro destino. También coordinamos traslados desde Barcelona hacia el aeropuerto, adaptando la recogida al horario de tu vuelo."); ?>$2',
    'Ver traslados al aeropuerto' => '<?php echo mt_translate("Ver traslados al aeropuerto"); ?>',
    
    // Section 2
    '/(<h3.*?>)\s*Traslados al Puerto desde Barcelona\s*(<\/h3>)/' => '$1<?php echo mt_translate("Traslados al Puerto desde Barcelona"); ?>$2',
    '/(>)\s*Conectamos tu hotel o direcci.*?equipaje\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Conectamos tu hotel o dirección en Barcelona con todas las terminales de cruceros. Tu chófer te recoge puntual y ayuda con el equipaje."); ?>$2',
    'Ver traslados al puerto' => '<?php echo mt_translate("Ver traslados al puerto"); ?>',
    
    // Section 3
    '/(<h3.*?>)\s*Ch.*?fer privado por horas\s*(<\/h3>)/' => '$1<?php echo mt_translate("Chófer privado por horas"); ?>$2',
    '/(>)\s*Disp.*?n de un veh.*?varias paradas\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Dispón de un vehículo con conductor durante el tiempo contratado. Ideal para reuniones, compras, cenas o agendas con varias paradas."); ?>$2',
    'Ver servicio por horas' => '<?php echo mt_translate("Ver servicio por horas"); ?>',

    // Section 4
    'Empresas y Grupos' => '<?php echo mt_translate("Empresas y Grupos"); ?>',
    '/(>)\s*Coordinamos la movilidad de directivos.*?equipaje\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Coordinamos la movilidad de directivos, invitados y familias numerosas. Vehículos MINI VAN «V» Class disponibles para hasta 7 pasajeros con equipaje."); ?>$2',
    'Consultar para empresas y grupos' => '<?php echo mt_translate("Consultar para empresas y grupos"); ?>',
    
    // Reserva Facil
    '/(>)\s*Reserva f.*?cil\s*(<\/p>)/' => '$1<?php echo mt_translate("Reserva fácil"); ?>$2',
    'Reserva en pocos minutos y viaja sin complicaciones' => '<?php echo mt_translate("Reserva en pocos minutos y viaja sin complicaciones"); ?>',
    '/(>)\s*El proceso est.*?confirmar tu reserva\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("El proceso está diseñado para que conozcas la ruta, el vehículo, las opciones y las condiciones antes de confirmar tu reserva."); ?>$2',
    'Iniciar reserva' => '<?php echo mt_translate("Iniciar reserva"); ?>',
    'Indica tu trayecto' => '<?php echo mt_translate("Indica tu trayecto"); ?>',
    '/(>)\s*A.*?ade el punto de recogida.*?salida\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Añade el punto de recogida, el destino, la fecha y la hora de salida."); ?>$2',
    '/(<h3.*?>)\s*Elige tu veh.*?culo\s*(<\/h3>)/' => '$1<?php echo mt_translate("Elige tu vehículo"); ?>$2',
    '/(>)\s*Selecciona la opci.*?confort\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Selecciona la opción que mejor encaje con los pasajeros, el equipaje y el nivel de confort."); ?>$2',
    'Confirma la reserva' => '<?php echo mt_translate("Confirma la reserva"); ?>',
    '/(>)\s*Revisa las opciones y datos.*?segura\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Revisa las opciones y datos, completa la información y realiza el pago de forma segura."); ?>$2',
    '/(<h3.*?>)\s*Encuentra a tu ch.*?fer\s*(<\/h3>)/' => '$1<?php echo mt_translate("Encuentra a tu chófer"); ?>$2',
    '/(>)\s*El conductor te espera.*?equipaje\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("El conductor te espera en el punto acordado con cartel identificativo y te ayuda con el equipaje."); ?>$2',
    
    // La Experiencia
    'La experiencia MeTransfers' => '<?php echo mt_translate("La experiencia MeTransfers"); ?>',
    '/(>)\s*Puntualidad, comodidad y atenci.*?n en cada trayecto\s*(<\/h2>)/' => '$1<?php echo mt_translate("Puntualidad, comodidad y atención en cada trayecto"); ?>$2',
    '/(>)\s*No solo te llevamos de un punto.*?cuando la necesites\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("No solo te llevamos de un punto a otro. Coordinamos cada detalle para que disfrutes de una recogida clara, un viaje cómodo y asistencia cuando la necesites."); ?>$2',
    'Presupuesto a medida y condiciones visibles antes de confirmar' => '<?php echo mt_translate("Presupuesto a medida y condiciones visibles antes de confirmar"); ?>',
    'Seguimiento del vuelo y recogida en la terminal' => '<?php echo mt_translate("Seguimiento del vuelo y recogida en la terminal"); ?>',
    '/(<\/svg>)\s*Ch.*?feres profesionales, discretos y biling.*?es\s*(<\/li>)/' => '$1 <?php echo mt_translate("Chóferes profesionales, discretos y bilingües"); ?>$2',
    '/(<\/svg>)\s*Veh.*?culos premium seleccionados para cada servicio\s*(<\/li>)/' => '$1 <?php echo mt_translate("Vehículos premium seleccionados para cada servicio"); ?>$2',
    '/(<\/svg>)\s*Cancelaci.*?n sujeta a condiciones\s*(<\/li>)/' => '$1 <?php echo mt_translate("Cancelación sujeta a condiciones"); ?>$2',
    '/(<\/svg>)\s*Atenci.*?n 24\/7 bajo reserva por tel.*?fono, email y WhatsApp\s*(<\/li>)/' => '$1 <?php echo mt_translate("Atención 24/7 bajo reserva por teléfono, email y WhatsApp"); ?>$2',
    'Puedes solicitar sillas infantiles, paradas adicionales o transporte de equipaje especial durante la reserva. La disponibilidad se confirma para cada servicio.' => '<?php echo mt_translate("Puedes solicitar sillas infantiles, paradas adicionales o transporte de equipaje especial durante la reserva. La disponibilidad se confirma para cada servicio."); ?>',
    
    // Flota
    'Flota premium' => '<?php echo mt_translate("Flota premium"); ?>',
    'El espacio y el confort adecuados para cada reserva' => '<?php echo mt_translate("El espacio y el confort adecuados para cada reserva"); ?>',
    '/(>)\s*Asignamos el veh.*?seguridad\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Asignamos el vehículo según el número de pasajeros, el equipaje y el tipo de viaje. Todos los modelos se mantienen bajo estándares de limpieza y seguridad."); ?>$2',
    'Berlina ejecutiva' => '<?php echo mt_translate("Berlina ejecutiva"); ?>',
    'Minivan premium' => '<?php echo mt_translate("Minivan premium"); ?>',
    '/(>)\s*C.*?moda y elegante para traslados de aeropuerto, hoteles, reuniones y recorridos urbanos\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Cómoda y elegante para traslados de aeropuerto, hoteles, reuniones y recorridos urbanos."); ?>$2',
    '/(>)\s*Amplia y vers.*?il, perfecta para familias o grupos de trabajo con equipaje extra\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Amplia y versátil, perfecta para familias o grupos de trabajo con equipaje extra."); ?>$2',
    'Hasta 3 pasajeros' => '<?php echo mt_translate("Hasta 3 pasajeros"); ?>',
    'Hasta 7 pasajeros' => '<?php echo mt_translate("Hasta 7 pasajeros"); ?>',
    '/(<\/svg>)\s*Equipaje seg.*?n configuraci.*?n\s*(<\/span>)/' => '$1 <?php echo mt_translate("Equipaje según configuración"); ?>$2',
    
    // Rutas
    'Rutas y destinos mǭs solicitados' => '<?php echo mt_translate("Rutas y destinos más solicitados"); ?>',
    'Aeropuerto de Barcelona' => '<?php echo mt_translate("Aeropuerto de Barcelona"); ?>',
    '/(<h3.*?>)Aeropuerto de Barcelona\s*"\s*centro(<\/h3>)/' => '$1<?php echo mt_translate("Aeropuerto de Barcelona — centro"); ?>$2',
    'Recogida privada con seguimiento del vuelo y traslado puerta a puerta.' => '<?php echo mt_translate("Recogida privada con seguimiento del vuelo y traslado puerta a puerta."); ?>',
    '/(<h3.*?>)Aeropuerto de Barcelona\s*"\s*Puerto(<\/h3>)/' => '$1<?php echo mt_translate("Aeropuerto de Barcelona — Puerto"); ?>$2',
    '/(>)\s*Conexi.*?n privada con las terminales de cruceros\.\s*(<\/p>)/' => '$1<?php echo mt_translate("Conexión privada con las terminales de cruceros."); ?>$2',
    '/(<h3.*?>)Barcelona\s*"\s*Costa Brava(<\/h3>)/' => '$1<?php echo mt_translate("Barcelona — Costa Brava"); ?>$2',
    'Traslados privados a localidades de la Costa Brava.' => '<?php echo mt_translate("Traslados privados a localidades de la Costa Brava."); ?>',
    '/(<h3.*?>)Barcelona\s*"\s*Girona(<\/h3>)/' => '$1<?php echo mt_translate("Barcelona — Girona"); ?>$2',
    'Traslado directo a la ciudad o al aeropuerto de Girona.' => '<?php echo mt_translate("Traslado directo a la ciudad o al aeropuerto de Girona."); ?>',
    '/(>)\s*Seg.*?n tr.*?fico\s*(<\/span>)/' => '$1<?php echo mt_translate("Según tráfico"); ?>$2',
    '/(>)\s*Seg.*?n destino\s*(<\/span>)/' => '$1<?php echo mt_translate("Según destino"); ?>$2',

    // Reviews
    'Opiniones de viajeros' => '<?php echo mt_translate("Opiniones de viajeros"); ?>',
    'La confianza se gana en cada recogida' => '<?php echo mt_translate("La confianza se gana en cada recogida"); ?>',
    'Algunas experiencias de clientes que han reservado traslados y tours privados con MeTransfers.' => '<?php echo mt_translate("Algunas experiencias de clientes que han reservado traslados y tours privados con MeTransfers."); ?>',
    'Ver mǭs opiniones' => '<?php echo mt_translate("Ver más opiniones"); ?>',

    // FAQ
    'Preguntas frecuentes' => '<?php echo mt_translate("Preguntas frecuentes"); ?>',
    'Todo lo que necesitas saber antes de reservar' => '<?php echo mt_translate("Todo lo que necesitas saber antes de reservar"); ?>',
    '/(>)\s*Condiciones principales de nuestros traslados privados, ch.*?fer por horas y tours con salida desde Barcelona\.\s*(<\/p>)/s' => '$1<?php echo mt_translate("Condiciones principales de nuestros traslados privados, chófer por horas y tours con salida desde Barcelona."); ?>$2',
    '/(>)\s*Desde d.*?nde pod.*?is recogerme en Barcelona\?\s*(<span)/s' => '$1<?php echo mt_translate("¿Desde dónde podéis recogerme en Barcelona?"); ?>$2',
    '/(>)\s*Te recogemos en cualquier punto.*?hacer la reserva\.\s*(<\/div>)/s' => '$1<?php echo mt_translate("Te recogemos en cualquier punto de Barcelona: hotel, apartamento, oficina, Aeropuerto El Prat, Puerto de Cruceros o estación de tren. Solo indícanos la dirección exacta y la hora al hacer la reserva."); ?>$2',
    '/(>)\s*Pod.*?is llevarme desde Barcelona al Aeropuerto de El Prat\?\s*(<span)/s' => '$1<?php echo mt_translate("¿Podéis llevarme desde Barcelona al Aeropuerto de El Prat?"); ?>$2',
    '/(>)\s*S.*?\.\s*Puedes reservar traslados.*?recogida.*?ptima\.\s*(<\/div>)/s' => '$1<?php echo mt_translate("Sí. Puedes reservar traslados privados desde Barcelona al Aeropuerto Josep Tarradellas Barcelona-El Prat. Al confirmar la reserva, indica la hora de salida de tu vuelo para que calculemos la hora de recogida óptima."); ?>$2',
    '/(>)\s*D.*?nde me espera el conductor\?\s*(<span)/s' => '$1<?php echo mt_translate("¿Dónde me espera el conductor?"); ?>$2',
    '/(>)\s*En el aeropuerto, el ch.*?fer te espera.*?datos de la reserva\.\s*(<\/div>)/s' => '$1<?php echo mt_translate("En el aeropuerto, el chófer te espera en la zona de llegadas con un cartel identificativo. En hoteles, viviendas, puertos y otros puntos, la ubicación exacta se confirma en los datos de la reserva."); ?>$2',
    '/(>)\s*Puedo cancelar o modificar mi reserva\?\s*(<span)/s' => '$1<?php echo mt_translate("¿Puedo cancelar o modificar mi reserva?"); ?>$2',
    '/(>)\s*S.*?\.\s*Ofrecemos cancelaci.*?n gratuita.*?antes de la recogida\.\s*(<\/div>)/s' => '$1<?php echo mt_translate("Sí. Ofrecemos cancelación gratuita en la mayoría de reservas si nos avisas con un mínimo de 24 horas de antelación. Puedes revisar la política específica durante el proceso de reserva y contactarnos en cualquier momento para solicitar un cambio de hora o destino, sujeto a disponibilidad antes de la recogida."); ?>$2',
];

foreach ($reps as $pattern => $replacement) {
    if (strpos($pattern, '/') === 0) {
        // Regex
        $content = preg_replace($pattern, $replacement, $content);
    } else {
        // Direct string
        $content = str_replace($pattern, $replacement, $content);
    }
}

file_put_contents($file, $content);
echo "Replaced";
