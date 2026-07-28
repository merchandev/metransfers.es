<?php
/**
 * Tours Management Logic
 *
 * @package Me_Transfers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the catalog of available tours.
 *
 * @return array
 */
function me_transfers_get_tour_catalog() {
	return array(
		'tour-en-barcelona' => array(
			'title'      => 'MeTransfers Barcelona - Tour en Barcelona',
			'slug'       => 'tour-en-barcelona',
			'price'      => 'Desde 400€',
			'duration'   => '6-8 horas',
			'group_size' => 'Hasta 7 personas',
			'img'        => 'https://images.unsplash.com/photo-1583422409516-2895a77efded?q=80&w=1200&auto=format&fit=crop',
			'desc'       => 'Descubre Barcelona con un recorrido por sus monumentos icónicos, como la Sagrada Familia, el Barrio Gótico y el Paseo de Gracia. Disfruta de la arquitectura de GaudÍ y la vibrante cultura catalana en un tour inolvidable.',
			'full_desc'  => 'Barcelona es una de las ciudades más fascinantes de Europa, y nuestro tour privado te permite descubrirla a tu ritmo, sin aglomeraciones y con la comodidad de un vehÍculo Mercedes de alta gama con chófer profesional.

Comenzamos el recorrido con la recogida puerta a puerta en tu hotel, apartamento o cualquier punto de Barcelona. Nuestro conductor, completamente bilingüe, te acompañará durante toda la jornada y te dará contexto sobre cada lugar que visitéis.

La primera parada es la majestuosa Sagrada Familia, la obra maestra inacabada de Antoni GaudÍ que atrae a millones de visitantes cada año. Desde el exterior podrás apreciar la grandeza de sus fachadas, mientras tu chófer te explica la historia de su construcción que comenzó en 1882.

Continuamos hacia el Parque Güell, otro de los tesoros de GaudÍ, donde los mosaicos de colores y las formas orgánicas crean un paisaje de fantasÍa con vistas panorámicas espectaculares de toda la ciudad y el Mediterráneo. Es un lugar perfecto para fotografÍas inolvidables.

El recorrido sigue por el corazón histórico de Barcelona: el Barrio Gótico, con sus calles estrechas medievales, la Catedral de Barcelona y las plazas escondidas que guardan siglos de historia. Pasearemos por Las Ramblas, el bulevar más famoso de España, y el Mercado de la BoquerÍa con sus colores y aromas.

Finalizamos con una subida a Montjuïc, la colina que domina la ciudad, donde disfrutarás de las mejores vistas panorámicas de Barcelona, el puerto y el mar. Es el cierre perfecto para un dÍa completo de descubrimiento.

Este tour es completamente personalizable: si prefieres visitar el Camp Nou, el barrio de la Barceloneta o hacer una parada para comer en un restaurante local, solo tienes que decÍrnoslo.',
			'itinerary'  => array(
				'Recogida en su hotel o punto acordado en Barcelona',
				'Visita exterior a la Sagrada Familia con explicación del chófer',
				'Recorrido por el Parque Güell y sus terrazas panorámicas',
				'Paseo por el Barrio Gótico, la Catedral y Las Ramblas',
				'Parada en el Mercado de la BoquerÍa (opcional)',
				'Subida panorámica a Montjuïc con vistas de la ciudad',
				'Regreso al punto de origen o lugar que prefiera',
			),
			'includes'   => array(
				'Chófer privado profesional bilingüe (español/inglés)',
				'VehÍculo Mercedes de alta gama (MINI VAN «V» Class o ECONOMIC CLASS)',
				'Recogida y devolución puerta a puerta',
				'Agua frÍa y WiFi a bordo',
				'Flexibilidad total de horario y paradas',
				'Seguro de responsabilidad civil completo',
			),
			'highlights' => array( 'Sagrada Familia', 'Parque Güell', 'Barrio Gótico', 'Montjuïc' ),
		),

		'tour-a-montserrat' => array(
			'title'      => 'MeTransfers Barcelona - Tour a Montserrat',
			'slug'       => 'tour-a-montserrat',
			'price'      => 'Desde 450€',
			'duration'   => '5-7 horas',
			'group_size' => 'Hasta 7 personas',
			'img'        => get_template_directory_uri() . '/assets/img/V2.png',
			'desc'       => 'Explora la majestuosa montaña de Montserrat y su monasterio benedictino, hogar de la Virgen de Montserrat. Disfruta de vistas panorámicas, senderos naturales y la espiritualidad de este emblemático lugar de Cataluña.',
			'full_desc'  => 'Montserrat es mucho más que una montaña: es el corazón espiritual de Cataluña y una de las formaciones rocosas más impresionantes de Europa. Nuestro tour privado te lleva desde Barcelona hasta la cima en un trayecto de apenas una hora, atravesando paisajes que cambian de la ciudad al campo y finalmente a las espectaculares agujas de roca que dan nombre a la "montaña serrada".

La experiencia comienza con la recogida en tu alojamiento de Barcelona. Durante el trayecto, tu chófer te pondrá en contexto sobre la importancia histórica y cultural de Montserrat para el pueblo catalán, un lugar de peregrinaje desde hace más de mil años.

Al llegar, visitarás la BasÍlica del Monasterio de Montserrat, un impresionante conjunto arquitectónico fundado en el siglo XI que alberga a la famosa Virgen de Montserrat, conocida cariñosamente como "La Moreneta". Esta talla románica del siglo XII es la patrona de Cataluña y cada año recibe a millones de devotos y visitantes.

Si la visita coincide con el horario, podrás escuchar a la EscolanÍa de Montserrat, uno de los coros de niños más antiguos de Europa, cuyas voces llenan la basÍlica con una acústica sobrecogedora.

El tour incluye tiempo libre para subir en el funicular de Sant Joan hasta el punto más alto accesible de la montaña, desde donde las vistas son absolutamente extraordinarias: en dÍas claros puedes ver hasta Mallorca. También puedes optar por el funicular de Santa Cova, que baja hasta la cueva donde según la leyenda se encontró la imagen de la Virgen.

Antes de regresar, hacemos una parada para degustar los famosos licores artesanales que elaboran los monjes del monasterio, asÍ como quesos y productos locales de la región. Es una experiencia gastronómica que complementa perfectamente la visita cultural.

El regreso a Barcelona se realiza a tu ritmo, con la posibilidad de hacer paradas adicionales en pueblos del interior como Manresa o bodegas del Penedès.',
			'itinerary'  => array(
				'Recogida en su hotel en Barcelona',
				'Trayecto escénico hasta Montserrat (~1 hora)',
				'Visita a la BasÍlica y la Virgen de la Moreneta',
				'Coro de la EscolanÍa (según horario disponible)',
				'Subida en funicular de Sant Joan (vistas panorámicas)',
				'Degustación de licores y productos locales',
				'Regreso a Barcelona con paradas opcionales',
			),
			'includes'   => array(
				'Chófer privado profesional bilingüe',
				'VehÍculo Mercedes de alta gama',
				'Recogida y devolución puerta a puerta',
				'Agua frÍa y WiFi a bordo',
				'Degustación de productos locales',
				'Seguro de responsabilidad civil completo',
			),
			'highlights' => array( 'AbadÍa de Montserrat', 'La Moreneta', 'Funicular de Sant Joan', 'Degustación de licores' ),
		),

		'tour-costa-brava' => array(
			'title'      => 'MeTransfers Barcelona - Tour Costa Brava',
			'slug'       => 'tour-costa-brava',
			'price'      => 'Desde 600€',
			'duration'   => '8-10 horas',
			'group_size' => 'Hasta 7 personas',
			'img'        => get_template_directory_uri() . '/assets/img/V1.png',
			'desc'       => 'Sumérgete en las aguas cristalinas y paisajes únicos de la Costa Brava. Recorre encantadores pueblos pesqueros, calas escondidas y disfruta de la mejor gastronomÍa mediterránea en un entorno paradisÍaco.',
			'full_desc'  => 'La Costa Brava es sin duda uno de los destinos más espectaculares del Mediterráneo occidental. Con nuestro tour privado, recorrerás los pueblos más bonitos de esta costa salvaje y escarpada, lejos de las rutas turÍsticas masificadas, con la libertad de parar donde y cuando quieras.

Salimos de Barcelona por la mañana y en aproximadamente una hora y media llegamos a Tossa de Mar, nuestro primer destino. Este pueblo medieval amurallado, que cautivó al mismÍsimo Marc Chagall, tiene una de las playas urbanas más bonitas de la Costa Brava, coronada por las ruinas de la Vila Vella, la única ciudad medieval fortificada que se conserva en el litoral catalán.

Continuamos por la sinuosa carretera de la costa, con vistas que quitan el aliento en cada curva, hasta llegar a Calella de Palafrugell, un antiguo pueblo de pescadores que conserva toda su autenticidad. Sus casitas blancas con puertas azules, las barcas varadas en la playa y los arcos del paseo marÍtimo crean una postal mediterránea perfecta. AquÍ podemos hacer una parada para un almuerzo de mariscos frescos en uno de los restaurantes locales con terraza frente al mar.

El recorrido incluye una caminata por un tramo de los Caminos de Ronda, los antiguos senderos costeros que recorrÍan los vigilantes del contrabando. Hoy son rutas de senderismo espectaculares que conectan calas escondidas, acantilados y miradores naturales con aguas turquesas dignas del Caribe.

Dependiendo de la temporada y tus preferencias, podemos incluir una visita a Pals o Peratallada, dos pueblos medievales del interior que han sido declarados conjuntos histórico-artÍsticos, con calles empedradas, murallas y torres que parecen detenidas en el tiempo.

El regreso a Barcelona se realiza al atardecer, el momento perfecto para contemplar la puesta de sol sobre los campos y viñedos del Empordà desde la comodidad de nuestro vehÍculo Mercedes. Un dÍa que combina playa, cultura, gastronomÍa y naturaleza en su máxima expresión.',
			'itinerary'  => array(
				'Recogida en su hotel en Barcelona por la mañana',
				'Trayecto escénico hasta Tossa de Mar (~1.5 horas)',
				'Visita a la Vila Vella y playas de Tossa de Mar',
				'Ruta costera hasta Calella de Palafrugell',
				'Almuerzo de mariscos con vistas al mar (opcional)',
				'Caminata por los Caminos de Ronda y calas escondidas',
				'Visita a pueblos medievales del interior (Pals o Peratallada)',
				'Regreso a Barcelona al atardecer',
			),
			'includes'   => array(
				'Chófer privado profesional bilingüe',
				'VehÍculo Mercedes de alta gama',
				'Recogida y devolución puerta a puerta',
				'Agua frÍa y WiFi a bordo',
				'Paradas ilimitadas para fotos y exploración',
				'Seguro de responsabilidad civil completo',
			),
			'highlights' => array( 'Tossa de Mar', 'Calella de Palafrugell', 'Caminos de Ronda', 'GastronomÍa local' ),
		),

		'tour-a-girona' => array(
			'title'      => 'MeTransfers Barcelona - Tour a Girona',
			'slug'       => 'tour-a-girona',
			'price'      => 'Desde 500€',
			'duration'   => '6-8 horas',
			'group_size' => 'Hasta 7 personas',
			'img'        => 'https://metransfers.es/wp-content/uploads/2026/04/catedral-de-girona_principal.jpg',
			'desc'       => 'Pasea por la histórica ciudad de Girona, con su impresionante casco antiguo, el barrio judÍo y los coloridos puentes sobre el rÍo Onyar. Un destino lleno de historia, cultura y escenarios de pelÍcula.',
			'full_desc'  => 'Girona es una de las ciudades con más encanto de toda España, y nuestro tour privado te permite descubrir cada rincón de esta joya medieval a solo una hora de Barcelona. Con más de dos mil años de historia, Girona ofrece una combinación única de patrimonio romano, medieval, judÍo y modernista que la convierte en una visita imprescindible.

El tour comienza con la recogida en tu alojamiento de Barcelona. El trayecto hasta Girona dura aproximadamente una hora por autopista, tiempo que tu chófer aprovechará para contarte la historia de la ciudad y recomendarte los mejores lugares.

Al llegar, comenzamos por la imponente Catedral de Girona, que posee la nave gótica más ancha del mundo con sus 23 metros. La escalinata barroca que conduce hasta su entrada principal es uno de los escenarios más reconocidos de "Juego de Tronos", donde se rodaron escenas de Desembarco del Rey en las temporadas 5 y 6.

Descendemos hacia el Call, el barrio judÍo medieval mejor conservado de toda Europa. Sus callejuelas laberÍnticas, algunas de apenas un metro de ancho, te transportan directamente al siglo XV. AquÍ se encuentra el Museo de Historia de los JudÍos, donde podrás conocer la importante comunidad sefardÍ que habitó Girona durante siglos.

Uno de los momentos más fotogénicos del tour es el paseo por las Cases de l\'Onyar, las coloridas casas colgadas sobre el rÍo que se han convertido en la imagen icónica de la ciudad. Los puentes que cruzan el rÍo, incluido el Pont de les Peixateries Velles diseñado por Gustave Eiffel, ofrecen las mejores perspectivas para fotografÍas.

Completamos el recorrido con un paseo por la Muralla medieval, un camino elevado que rodea el casco antiguo y ofrece vistas panorámicas extraordinarias de los tejados, las torres y las montañas que rodean la ciudad. Es el lugar perfecto para entender la dimensión y belleza de Girona desde las alturas.

Para el almuerzo, podemos hacer una parada en alguno de los restaurantes del casco antiguo donde podrás probar la cocina gerundense, influenciada por la cercanÍa de los Pirineos y el Mediterráneo. Girona es una ciudad con una escena gastronómica de primer nivel, sede de varios restaurantes con estrellas Michelin.',
			'itinerary'  => array(
				'Recogida en su hotel en Barcelona',
				'Trayecto hasta Girona (~1 hora por autopista)',
				'Visita a la Catedral de Girona (escenario de Juego de Tronos)',
				'Recorrido por el Call, el barrio judÍo medieval',
				'Paseo por las Cases de l\'Onyar y puentes del rÍo',
				'Almuerzo en restaurante del casco antiguo (opcional)',
				'Caminata por la Muralla medieval y vistas panorámicas',
				'Regreso a Barcelona',
			),
			'includes'   => array(
				'Chófer privado profesional bilingüe',
				'VehÍculo Mercedes de alta gama',
				'Recogida y devolución puerta a puerta',
				'Agua frÍa y WiFi a bordo',
				'Flexibilidad total de horario y paradas',
				'Seguro de responsabilidad civil completo',
			),
			'highlights' => array( 'Casco Antiguo', 'Barrio JudÍo', 'Catedral de Girona', 'Paseo de la Muralla' ),
		),
	);
}

/**
 * Returns the URL for a specific tour.
 *
 * @param string $slug The tour slug.
 * @return string
 */
function me_transfers_get_tour_url( $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return get_permalink( $page->ID );
	}
	return home_url( '/' . $slug );
}

/**
 * Checks if the current post is one of our dynamic tours.
 *
 * @param WP_Post|null $post The post object.
 * @return array|false The tour data array if it is a tour, false otherwise.
 */
function me_transfers_get_current_tour( $post = null ) {
	if ( ! $post ) {
		global $post;
	}
	if ( ! $post || 'page' !== $post->post_type ) {
		return false;
	}

	$catalog = me_transfers_get_tour_catalog();
	if ( isset( $catalog[ $post->post_name ] ) ) {
		return $catalog[ $post->post_name ];
	}

	return false;
}

/**
 * Ensures all tour pages exist in the WordPress database.
 * If they don't, it creates them.
 */
function me_transfers_sync_tour_pages() {
	$catalog = me_transfers_get_tour_catalog();

	foreach ( $catalog as $slug => $tour ) {
		$page    = get_page_by_path( $slug );
		$trashed = get_page_by_path( $slug . '__trashed' );
		if ( ! $page && ! $trashed ) {
			wp_insert_post( array(
				'post_title'     => $tour['title'],
				'post_name'      => $slug,
				'post_content'   => '',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
			) );
		}
	}
}
add_action( 'after_switch_theme', 'me_transfers_sync_tour_pages' );
add_action( 'admin_init', 'me_transfers_sync_tour_pages' );
