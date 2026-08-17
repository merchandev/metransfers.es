<?php
/**
 * Services Catalog — MeTransfers
 * Catálogo completo de los 6 servicios, con textos, beneficios y datos del formulario.
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the full catalog of available services.
 *
 * @return array
 */
function me_transfers_get_service_catalog() {
	return array(

		// ─── 1. TRASLADOS AL AEROPUERTO ─────────────────────────────────────────────
		'traslados-aeropuerto' => array(
			'title'       => 'MeTransfers Barcelona - Traslado al Aeropuerto desde Barcelona',
			'subtitle'    => 'Te recogemos en Barcelona y te llevamos a El Prat',
			'slug'        => 'traslados-aeropuerto',
			'badge'       => 'Aeropuerto El Prat · 24/7',
			'hero_desc'   => 'Te recogemos en tu hotel u oficina en Barcelona y te llevamos directamente al Aeropuerto El Prat. Seguimiento de vuelo en tiempo real, tarifa fija y hasta 60 min de espera gratuita en llegadas.',
			'desc_long'   => 'Salir desde Barcelona hacia el aeropuerto nunca fue tan sencillo. En MeTransfers calculamos el tiempo de salida desde tu domicilio, hotel u oficina con margen suficiente para que llegues tranquilo, sin carreras y con energía para tu viaje.

Y si llegas a El Prat, un chófer uniformado y bilingüe te recibirá en la zona de llegadas con un cartel personalizado con tu nombre o el de tu empresa. Monitoreamos tu vuelo en tiempo real — sin importar retrasos o adelantos de última hora. Te asistirá con el equipaje y te acompañará directamente a tu vehículo Mercedes estacionado a pie de terminal. El trayecto al hotel, oficina o destino final se convierte en el primer momento de descanso tras el vuelo.',
			'features'    => array(
				array( 'icon' => 'flight', 'title' => 'Seguimiento de Vuelo', 'desc' => 'Monitoreamos tu vuelo en tiempo real. Si hay retrasos, tu conductor espera.' ),
				array( 'icon' => 'receipt_long', 'title' => 'Meet & Greet', 'desc' => 'Cartel personalizado en zona de llegadas. Bienvenida profesional y puntual.' ),
				array( 'icon' => 'schedule', 'title' => '60 min de espera', 'desc' => 'Incluimos hasta 60 minutos de espera gratuita en llegadas internacionales.' ),
				array( 'icon' => 'luggage', 'title' => 'Ayuda con el equipaje', 'desc' => 'Tu chófer te asiste con las maletas desde la terminal hasta el vehículo.' ),
				array( 'icon' => 'directions_car', 'title' => 'Flota Mercedes Premium', 'desc' => 'Viaja en ECONOMIC CLASS, MINI VAN «V» Class, BUSINESS CLASS o MINI VAN ECONOMIC según tus necesidades.' ),
				array( 'icon' => 'lock', 'title' => 'Precio cerrado desde Barcelona', 'desc' => 'Sin tarifas sorpresa. El precio que ves incluye la recogida en Barcelona y el trayecto completo.' ),
			),
			'steps'       => array(
				array( 'n' => '01', 'title' => 'Indica tu punto de recogida en Barcelona', 'desc' => 'Introduce tu dirección en Barcelona, número de vuelo, fecha y hora de salida.' ),
				array( 'n' => '02', 'title' => 'Confirmación en minutos', 'desc' => 'Recibirás confirmación de reserva con los datos completos del servicio y del conductor.' ),
				array( 'n' => '03', 'title' => 'Tu chófer llega a tiempo', 'desc' => 'El día del servicio, tu conductor llega a tu dirección en Barcelona con puntualidad garantizada.' ),
				array( 'n' => '04', 'title' => 'Llegas tranquilo a El Prat', 'desc' => 'Sube, relájate y disfruta del trayecto en total confort. Tu vuelo, a tiempo.' ),
			),
			'form_type'   => 'aeropuerto',
			'cta_text'    => 'Reservar ahora',
		),

		// ─── 2. TRASLADOS AL PUERTO ──────────────────────────────────────────────────
		'traslados-puerto' => array(
			'title'       => 'MeTransfers Barcelona - Traslado al Puerto de Barcelona desde la ciudad',
			'subtitle'    => 'Te recogemos en tu hotel en Barcelona y te llevamos al Puerto',
			'slug'        => 'traslados-puerto',
			'badge'       => 'Puerto de Barcelona · Cruceros y ferries',
			'hero_desc'   => 'Te recogemos en tu hotel, apartamento o cualquier dirección en Barcelona y te llevamos a las terminales de cruceros del Port de Barcelona. Coordinación puntual y asistencia con el equipaje.',
			'desc_long'   => 'Salir hacia el Puerto de Barcelona con todo el equipaje de un crucero puede convertirse en un calvario si no tienes transporte privado. MeTransfers te recoge directamente en tu hotel o alojamiento en Barcelona y te lleva sin prisas hasta la terminal de embarque que te corresponda.

Operamos en todas las terminales activas de cruceros y ferris del Port de Barcelona. Indícanos el barco o la terminal y confirmaremos el punto exacto de recogida. Nos anticipamos al tráfico y a los tiempos de embarque para que llegues con margen suficiente y sin prisas.

Si llegas de un crucero, tu chófer te espera en el punto de salida de la terminal con tu nombre en el cartel. El traslado al hotel, aeropuerto u otro destino en Barcelona es tranquilo, cómodo y con espacio para todo tu equipaje.',
			'features'    => array(
				array( 'icon' => 'directions_boat', 'title' => 'Todas las terminales', 'desc' => 'Operamos en todas las terminales activas del Port de Barcelona.' ),
				array( 'icon' => 'luggage', 'title' => 'Espacio para equipaje extra', 'desc' => 'Maletas de crucero, trolleys grandes y bolsas extra. La MINI VAN «V» Class lo lleva todo.' ),
				array( 'icon' => 'alarm', 'title' => 'Sincronización con atraque', 'desc' => 'Controlamos el horario de llegada del barco para ajustar la recogida.' ),
				array( 'icon' => 'hotel', 'title' => 'Hotel · Puerto · Aeropuerto', 'desc' => 'Cualquier combinación saliendo desde Barcelona: hotel al puerto, aeropuerto al puerto y viceversa.' ),
				array( 'icon' => 'verified', 'title' => 'Sin colas ni taxis', 'desc' => 'Evita filas de taxi y transporte público con maletas. Tu coche privado, siempre listo.' ),
				array( 'icon' => 'forum', 'title' => 'Atención multilingüe', 'desc' => 'Chóferes que hablan español, inglés y más. Perfectos para cruceristas internacionales.' ),
			),
			'steps'       => array(
				array( 'n' => '01', 'title' => 'Indícanos dónde recogerte en Barcelona', 'desc' => 'Tu hotel, apartamento o cualquier dirección en Barcelona. Añade el nombre del barco, terminal y fecha.' ),
				array( 'n' => '02', 'title' => 'Confirmamos el servicio', 'desc' => 'Te enviamos confirmación con todos los detalles: conductor, vehículo y punto de encuentro.' ),
				array( 'n' => '03', 'title' => 'Tu chófer llega a tiempo', 'desc' => 'El conductor llega a tu dirección en Barcelona puntual para llegar con margen al barco.' ),
				array( 'n' => '04', 'title' => 'Traslado directo al Puerto', 'desc' => 'Con todo el equipaje cargado, viaja cómodo desde Barcelona hasta tu terminal de cruceros.' ),
			),
			'form_type'   => 'puerto',
			'cta_text'    => 'Reservar ahora',
		),

		// ─── 3. CHÓFER PRIVADO POR HORAS ────────────────────────────────────────────
		'chofer-por-horas' => array(
			'title'       => 'MeTransfers Barcelona - Chófer Privado por Horas en Barcelona',
			'subtitle'    => 'Tu conductor exclusivo por Barcelona y alrededores',
			'slug'        => 'chofer-por-horas',
			'badge'       => 'Disposición por horas · MÍnimo 3h',
			'hero_desc'   => 'Dispón de un vehículo Mercedes con conductor durante el tiempo que necesites en Barcelona. Ideal para reuniones, compras, visitas o días completos con múltiples paradas por la ciudad.',
			'desc_long'   => 'A veces no necesitas ir de A a B — necesitas un conductor que te espere en Barcelona, te lleve a varios sitios y esté disponible durante horas para todo lo que surja. Para eso está nuestro servicio de chófer privado por horas.

Imagina una mañana de reuniones en el 22@ de Barcelona, una parada a comer en el Eixample y después al aeropuerto. O una tarde de compras en Passeig de Gràcia con el coche esperando. O una noche de evento con cenas y traslados entre locales de la ciudad. Con el servicio por disposición, tú marcas el ritmo.

El conductor permanece a tu disposición exclusiva durante el tiempo contratado en Barcelona. Tú decides las paradas, los cambios de plan y los tiempos. MÍnimo 3 horas, sin límite máximo. Disponible en toda el área metropolitana de Barcelona y rutas de larga distancia.',
			'features'    => array(
				array( 'icon' => 'schedule', 'title' => 'Flexibilidad total', 'desc' => 'Cambios de plan en tiempo real. El conductor se adapta a tu agenda, no al revés.' ),
				array( 'icon' => 'location_on', 'title' => 'Múltiples paradas', 'desc' => 'Reuniones, compras, restaurantes, eventos — todas las paradas que necesites por Barcelona.' ),
				array( 'icon' => 'lock', 'title' => 'Exclusividad garantizada', 'desc' => 'El vehículo y el conductor son exclusivamente tuyos durante todo el tiempo contratado.' ),
				array( 'icon' => 'wifi', 'title' => 'Wi-Fi y agua a bordo', 'desc' => 'Trabaja, descansa o prepara tus reuniones durante los trayectos con total comodidad.' ),
				array( 'icon' => 'map', 'title' => 'Toda el área metropolitana', 'desc' => 'Barcelona ciudad, costa, interior y rutas largas a Madrid, Valencia o Andorra.' ),
				array( 'icon' => 'dark_mode', 'title' => 'Disponible 24/7', 'desc' => 'Madrugadas, días festivos, eventos nocturnos. Siempre disponible cuando lo necesites.' ),
			),
			'steps'       => array(
				array( 'n' => '01', 'title' => 'DÍnos cuántas horas necesitas', 'desc' => 'Indica fecha, hora de inicio, dirección de recogida en Barcelona y descripción general del plan.' ),
				array( 'n' => '02', 'title' => 'Te enviamos presupuesto', 'desc' => 'Precio cerrado por el número de horas. Sin sorpresas al finalizar el servicio.' ),
				array( 'n' => '03', 'title' => 'El conductor llega puntual', 'desc' => 'Tu chófer aparece en el lugar acordado en Barcelona, vehículo preparado y listo para comenzar.' ),
				array( 'n' => '04', 'title' => 'Tú mandas, él conduce', 'desc' => 'Indica destinos, paradas y cambios sobre la marcha. Tú llevas el ritmo del día.' ),
			),
			'form_type'   => 'horas',
			'cta_text'    => 'Reservar ahora',
		),

		// ─── 4. CORPORATIVO Y EVENTOS ────────────────────────────────────────────────
		'corporativo-y-eventos' => array(
			'title'       => 'MeTransfers Barcelona - Transporte Corporativo y Eventos desde Barcelona',
			'subtitle'    => 'LogÍstica impecable para empresas y grandes eventos en Barcelona',
			'slug'        => 'corporativo-y-eventos',
			'badge'       => 'MWC · ISE · Congresos · Empresas',
			'hero_desc'   => 'Coordinamos la movilidad de directivos, delegaciones y equipos desde cualquier punto de Barcelona para congresos, ferias y eventos privados. Flota uniforme, facturación centralizada y máxima discreción.',
			'desc_long'   => 'Cuando la movilidad de tu empresa o evento en Barcelona debe funcionar sin fisuras, MeTransfers aporta la tranquilidad logística que los equipos necesitan. Tenemos experiencia coordinando traslados durante congresos como MWC, ISE y otras grandes ferias de Barcelona.

Ponemos a tu disposición una flota de vehículos Mercedes de última generación — todos del mismo modelo y color — para garantizar una imagen uniforme y profesional desde el primer punto de recogida en la ciudad. Nuestros conductores son discretos, puntuales, bilingües y con experiencia en protocolos corporativos.

Tanto si necesitas coordinar 3 traslados ejecutivos como 50 delegados en rotación durante una feria de 4 días, gestionamos la logística completa desde Barcelona: horarios, puntos de recogida, rutas, esperas y cambios de última hora. Facturación unificada al final del evento.',
			'features'    => array(
				array( 'icon' => 'business', 'title' => 'Gestión logística completa', 'desc' => 'Coordinador dedicado para eventos multi-vehículo desde Barcelona. Un punto de contacto para todo.' ),
				array( 'icon' => 'directions_car', 'title' => 'Flota uniforme', 'desc' => 'Todos los vehículos del mismo modelo y acabado. Imagen de marca coherente y premium.' ),
				array( 'icon' => 'language', 'title' => 'Conductores bilingües', 'desc' => 'Español, inglés y más idiomas. Perfectos para delegaciones internacionales.' ),
				array( 'icon' => 'receipt_long', 'title' => 'Facturación centralizada', 'desc' => 'Una sola factura al final del evento. Compatible con contabilidad de empresa.' ),
				array( 'icon' => 'phone_iphone', 'title' => 'Coordinación en tiempo real', 'desc' => 'Cambios de última hora gestionados al instante vÍa WhatsApp o coordinador dedicado.' ),
				array( 'icon' => 'lock', 'title' => 'Máxima confidencialidad', 'desc' => 'Discreción absoluta con directivos, VIPs y delegaciones de alto nivel.' ),
			),
			'steps'       => array(
				array( 'n' => '01', 'title' => 'Cuéntanos el evento', 'desc' => 'EnvÍanos el tipo de evento, número de personas, fechas y necesidades especÍficas.' ),
				array( 'n' => '02', 'title' => 'Propuesta personalizada', 'desc' => 'Preparamos un plan logÍstico y presupuesto adaptado a tu evento en Barcelona en menos de 24h.' ),
				array( 'n' => '03', 'title' => 'Conductores en Barcelona listos', 'desc' => 'Ajustamos horarios, rutas y asignaciones de vehículos en los puntos de recogida de la ciudad. Todo confirmado con antelación.' ),
				array( 'n' => '04', 'title' => 'Ejecución impecable', 'desc' => 'El día del evento, todo funciona. Factura única y resumen del servicio al finalizar.' ),
			),
			'form_type'   => 'corporativo',
			'cta_text'    => 'Solicitar presupuesto',
		),

		// ─── 5. TOURS PRIVADOS ───────────────────────────────────────────────────────
		'tours-privados' => array(
			'title'       => 'MeTransfers Barcelona - Tours Privados desde Barcelona',
			'subtitle'    => 'Salimos desde tu hotel en Barcelona hacia los mejores destinos',
			'slug'        => 'tours-privados',
			'badge'       => 'Montserrat · Costa Brava · Girona · Andorra',
			'hero_desc'   => 'Explora Cataluña en exclusiva con un chófer guÍa local. Salimos desde tu hotel o apartamento en Barcelona. Desde medio día hasta rutas completas. Sin horarios de grupo, sin esperas, sin límites.',
			'desc_long'   => 'Un tour privado con MeTransfers es una experiencia completamente diferente al turismo convencional. Sin autobuses llenos, sin horarios de grupo y sin paradas que no quieres hacer. Diseñamos cada excursión a medida de tus intereses, tiempo y ritmo, con recogida directa en tu hotel de Barcelona.

Nuestros conductores son embajadores locales que conocen los rincones que los tours masivos nunca muestran. En Montserrat, el momento para bajar a las grutas tranquilas cuando los grupos ya se han ido. En la Costa Brava, la cala escondida que no sale en los folletos. En Girona, el callejón de la época medieval que te transporta siglos atrás.

Desde Barcelona, operamos excursiones de medio día (4 horas), día completo (8 horas) y rutas de varios días. Los destinos más populares incluyen Montserrat, Costa Brava, Girona, Figueres (DalÍ), Tarragona, Sitges y Andorra. También diseñamos rutas temáticas: gastronomía, vinos, arquitectura modernista o paisajes naturales.',
			'features'    => array(
				array( 'icon' => 'map', 'title' => 'Rutas 100% personalizadas', 'desc' => 'Tú decides los destinos, paradas, tiempos y ritmo. Nosotros lo coordinamos todo.' ),
				array( 'icon' => 'explore', 'title' => 'Chófer-guÍa local', 'desc' => 'Conocimiento local profundo: historia, gastronomía, secretos que no están en las guÍas.' ),
				array( 'icon' => 'directions_car', 'title' => 'Sin grupos', 'desc' => 'Solo tú, tu familia o tus invitados. Privacidad y exclusividad absoluta en cada parada.' ),
				array( 'icon' => 'schedule', 'title' => 'Desde 4 hasta 12 horas', 'desc' => 'Medio día, día completo o rutas extendidas. El tiempo que necesites, sin restricciones.' ),
				array( 'icon' => 'wine_bar', 'title' => 'Tours temáticos', 'desc' => 'GastronomÍa, vinos, arquitectura, naturaleza, historia. Diseñamos la ruta perfecta.' ),
				array( 'icon' => 'public', 'title' => 'Multilingüe', 'desc' => 'Servicio disponible en español, inglés y otros idiomas. Ideal para visitantes internacionales.' ),
			),
			'steps'       => array(
				array( 'n' => '01', 'title' => 'Elige tu destino y fecha', 'desc' => 'Cuéntanos qué quieres ver, cuántas personas sois y en qué fecha.' ),
				array( 'n' => '02', 'title' => 'Diseñamos tu ruta', 'desc' => 'Preparamos un itinerario personalizado con sugerencias de paradas y tiempos estimados.' ),
				array( 'n' => '03', 'title' => 'Recogida en tu hotel de Barcelona', 'desc' => 'Tu conductor te recoge en el lobby de tu hotel o alojamiento en Barcelona a la hora acordada.' ),
				array( 'n' => '04', 'title' => 'Explora sin límites', 'desc' => 'Vive Cataluña a tu ritmo. Paradas cuando quieras, retorno a tu hotel en Barcelona a la hora que prefieras.' ),
			),
			'form_type'   => 'tours',
			'cta_text'    => 'Reservar ahora',
		),

		// ─── 6. GRUPOS Y CELEBRACIONES ──────────────────────────────────────────────
		'grupos' => array(
			'title'       => 'MeTransfers Barcelona - Transporte para Grupos desde Barcelona',
			'subtitle'    => 'Movilidad coordinada desde Barcelona para grupos, bodas y celebraciones',
			'slug'        => 'grupos',
			'badge'       => 'Bodas · Despedidas · Grupos Privados',
			'hero_desc'   => 'Coordinamos el transporte de grupos desde 8 hasta 50+ personas con salida desde Barcelona: aeropuerto, hotel, puerto o cualquier dirección. Bodas, despedidas, incentivos y celebraciones en vehículos premium.',
			'desc_long'   => 'Organizar el transporte de un grupo desde Barcelona es un reto logÍstico que MeTransfers convierte en un proceso sin estrés. Ya sea una boda de 80 invitados entre la iglesia, el hotel y la masÍa en Barcelona; una despedida de soltero con 15 personas entre varios locales de la ciudad; o un incentivo de empresa de 40 personas desde el Aeropuerto El Prat al resort — lo coordinamos todo con precisión.

Disponemos de vehículos para grupos de todos los tamaños: desde la MINI VAN «V» Class (7 plazas) hasta múltiples unidades coordinadas para grupos grandes. Todos los coches llegan al mismo tiempo, los conductores están sincronizados y tú tienes un solo punto de contacto para gestionar cualquier incidencia o cambio.

Para bodas, ofrecemos decoración del vehículo, bebidas de bienvenida y coordinación con el wedding planner. Para incentivos de empresa, gestionamos logística de varios días con facturación unificada.',
			'features'    => array(
				array( 'icon' => 'groups', 'title' => 'De 8 a 50+ personas', 'desc' => 'Escalamos el número de vehículos según el tamaño de tu grupo. Sin límite.' ),
				array( 'icon' => 'favorite', 'title' => 'Especialistas en bodas', 'desc' => 'Decoración del coche nupcial, coordinación con fotógrafo y wedding planner.' ),
				array( 'icon' => 'celebration', 'title' => 'Celebraciones y despedidas', 'desc' => 'Despedidas de soltero, cumpleaños, aniversarios. Tu noche perfecta empieza en el coche.' ),
				array( 'icon' => 'sync', 'title' => 'Flota sincronizada', 'desc' => 'Todos los vehículos coordinados desde Barcelona. Llegada simultánea, salida coordinada, sin caos.' ),
				array( 'icon' => 'support_agent', 'title' => 'Un solo coordinador', 'desc' => 'Un punto de contacto directo para gestionar cambios, adiciones y ajustes de último minuto.' ),
				array( 'icon' => 'auto_awesome', 'title' => 'Servicios extras', 'desc' => 'Champán, agua, flores, letreros personalizados. Hacemos del trayecto parte del evento.' ),
			),
			'steps'       => array(
				array( 'n' => '01', 'title' => 'Cuéntanos el evento', 'desc' => 'Número de personas, tipo de evento, fecha y dirección de recogida en Barcelona.' ),
				array( 'n' => '02', 'title' => 'Propuesta de flota y precio', 'desc' => 'Te enviamos el número de vehículos recomendados y presupuesto detallado.' ),
				array( 'n' => '03', 'title' => 'Coordinación previa', 'desc' => 'Confirmamos horarios, rutas de recogida en Barcelona y extras contratados. Coordinador asignado al evento.' ),
				array( 'n' => '04', 'title' => 'Tu evento, en movimiento', 'desc' => 'El día del evento, todos los vehículos sincronizados desde Barcelona. Tú disfrutas, nosotros coordinamos.' ),
			),
			'form_type'   => 'grupos',
			'cta_text'    => 'Solicitar presupuesto',
		),

	);
}

/**
 * Returns the URL for a specific service.
 *
 * @param string $slug The service slug.
 * @return string
 */
function me_transfers_get_service_url( $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page->ID );
	}
	return home_url( '/' . $slug );
}

/**
 * Detects if the current page matches a service in the catalog.
 *
 * @param WP_Post|null $post The post object.
 * @return array|false The service data array, or false.
 */
function me_transfers_get_current_service( $post = null ) {
	if ( ! $post ) {
		global $post;
	}
	if ( ! $post || 'page' !== $post->post_type ) {
		return false;
	}

	$catalog = me_transfers_get_service_catalog();
	if ( isset( $catalog[ $post->post_name ] ) ) {
		return $catalog[ $post->post_name ];
	}

	return false;
}

/**
 * Ensures all service pages exist in WordPress.
 * Called on theme activation or manually.
 */
function me_transfers_sync_service_pages() {
	$catalog       = me_transfers_get_service_catalog();
	$template_slug = 'template-servicio.php';

	foreach ( $catalog as $slug => $service ) {
		$page    = get_page_by_path( $slug );
		$trashed = get_page_by_path( $slug . '__trashed' );

		if ( ! $page && ! $trashed ) {
			$page_id = wp_insert_post( array(
				'post_title'     => $service['title'],
				'post_name'      => $slug,
				'post_content'   => '',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
			) );
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_post_meta( $page_id, '_wp_page_template', $template_slug );
			}
		} elseif ( $page ) {
			// Ensure existing pages use the right template.
			$current_template = get_post_meta( $page->ID, '_wp_page_template', true );
			if ( $current_template !== $template_slug ) {
				update_post_meta( $page->ID, '_wp_page_template', $template_slug );
			}
		}
	}
}
// [MIGRACIÓN MANUAL] Hook desactivado
// add_action( 'after_switch_theme', 'me_transfers_sync_service_pages' );

