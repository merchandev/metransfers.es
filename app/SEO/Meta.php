<?php
namespace MeTransfers\SEO;

final class Meta {
	public function register() {
		add_filter( 'wpseo_title', array( __CLASS__, 'title' ), 110 );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'description' ), 110 );
	}

	public static function routeText( $post, string $field, string $language = 'es' ): string {
		$origin      = (string) get_post_meta( $post->ID, '_mt_ruta_origen', true );
		$destination = (string) get_post_meta( $post->ID, '_mt_ruta_destino', true );
		if ( '' === $origin || '' === $destination ) {
			return '';
		}
		$origin = str_replace( 'Barcelona centro', 'Barcelona', $origin );
		if ( 'title' === $field ) {
			return sprintf( 'Transfer %s - %s | MeTransfers', $origin, $destination );
		}
		return 'en' === $language
			? sprintf( 'Book your private transfer from %s to %s with door-to-door pickup and a professional driver. Confirm your travel details with MeTransfers.', $origin, $destination )
			: sprintf( 'Reserva tu traslado privado de %s a %s con recogida puerta a puerta y conductor profesional. Confirma los detalles del viaje con MeTransfers.', $origin, $destination );
	}

	public static function title( $title ) {
		$language = \MeTransfers\I18n\Language::get();
		if ( is_post_type_archive( 'ruta' ) ) {
			return 'en' === $language ? 'Private Transfer Routes from Barcelona | MeTransfers' : 'Rutas de transfer desde Barcelona | MeTransfers';
		}
		$post = get_queried_object();
		if ( is_singular( 'ruta' ) && $post ) {
			$generated = self::routeText( $post, 'title', $language );
			return '' !== $generated ? $generated : $title;
		}

		$slug = self::requestSlug();
		$map  = self::pageMeta( $language );
		if ( isset( $map[ $slug ]['title'] ) ) {
			return $map[ $slug ]['title'];
		}
		return $title;
	}

	public static function description( $description ) {
		$language = \MeTransfers\I18n\Language::get();
		if ( is_post_type_archive( 'ruta' ) ) {
			return 'en' === $language
				? 'Explore private transfer routes from Barcelona. Choose your destination and contact MeTransfers to confirm pickup, passengers and luggage.'
				: 'Consulta rutas de traslado privado desde Barcelona. Elige tu destino y confirma con MeTransfers la recogida, pasajeros y equipaje.';
		}
		$post = get_queried_object();
		if ( is_singular( 'ruta' ) && $post ) {
			$generated = self::routeText( $post, 'description', $language );
			return '' !== $generated ? $generated : $description;
		}

		$slug = self::requestSlug();
		$map  = self::pageMeta( $language );
		if ( isset( $map[ $slug ]['description'] ) ) {
			return $map[ $slug ]['description'];
		}
		return $description;
	}

	private static function requestSlug(): string {
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		return trim( \MeTransfers\I18n\Language::pathWithoutLanguage( $request ), '/' );
	}

	private static function pageMeta( string $language ): array {
		if ( 'en' === $language ) {
			return array(
				''                                 => array(
					'title'       => 'Barcelona Airport Transfers & Private Drivers | MeTransfers',
					'description' => 'Book private transfers in Barcelona for the airport, cruise port, hotels, train stations and long-distance trips with professional drivers.',
				),
				'traslados-privados'               => array(
					'title'       => 'Private Transfers from Barcelona | MeTransfers',
					'description' => 'Book private door-to-door transfers from Barcelona to destinations across Spain and selected international routes with professional drivers.',
				),
				'transfer-aeropuerto-barcelona'    => array(
					'title'       => 'Barcelona Airport Transfer | MeTransfers',
					'description' => 'Private Barcelona Airport transfers with flight monitoring, meet-and-greet service and direct pickup or drop-off at your hotel or address.',
				),
				'traslados-puerto'                 => array(
					'title'       => 'Barcelona Cruise Port Transfer | MeTransfers',
					'description' => 'Private transfers to and from Barcelona Cruise Port with door-to-door pickup, luggage assistance and vehicles for couples, families and groups.',
				),
				'chofer-por-horas'                 => array(
					'title'       => 'Hourly Private Driver in Barcelona | MeTransfers',
					'description' => 'Hire a private driver by the hour in Barcelona for business meetings, shopping, events, flexible itineraries and personalized transportation.',
				),
				'corporativo-y-eventos'            => array(
					'title'       => 'Corporate Transportation Barcelona | MeTransfers',
					'description' => 'Corporate transportation in Barcelona for executives, delegations, congresses and events with coordinated vehicles and professional drivers.',
				),
				'grupos'                           => array(
					'title'       => 'Group Transfers in Barcelona | MeTransfers',
					'description' => 'Private transportation for groups in Barcelona with coordinated minivans and vehicles for airports, hotels, events, tours and long-distance trips.',
				),
				'preguntas-frecuentes'             => array(
					'title'       => 'Private Transfer FAQ | MeTransfers Barcelona',
					'description' => 'Answers about Barcelona private transfers, pickup points, flight delays, luggage, child seats, cancellations, payment and booking conditions.',
				),
				'traslado-estacion-sants-barcelona' => array(
					'title'       => 'Barcelona Sants Station Transfer | MeTransfers',
					'description' => 'Private transfers to and from Barcelona Sants Station with direct hotel, airport, cruise port or address pickup and professional drivers.',
				),
				'traslados-hoteles-barcelona'      => array(
					'title'       => 'Barcelona Hotel Transfers | MeTransfers',
					'description' => 'Private transfers between Barcelona hotels, the airport, cruise port, train stations and destinations across Catalonia and Spain.',
				),
				'flota'                            => array(
					'title'       => 'Private Transfer Fleet Barcelona | MeTransfers',
					'description' => 'Explore MeTransfers vehicles for private transportation in Barcelona, including executive cars and minivans for passengers and luggage.',
				),
			);
		}

		return array(
			''                                 => array(
				'title'       => 'Transfer Aeropuerto Barcelona y Traslados Privados | MeTransfers',
				'description' => 'Reserva traslados privados en Barcelona para aeropuerto, puerto, hoteles, estaciones y viajes de larga distancia con chófer profesional.',
			),
			'traslados-privados'               => array(
				'title'       => 'Traslados Privados desde Barcelona | MeTransfers',
				'description' => 'Reserva traslados privados puerta a puerta desde Barcelona a destinos de España y rutas internacionales seleccionadas con chófer profesional.',
			),
			'transfer-aeropuerto-barcelona'    => array(
				'title'       => 'Transfer Aeropuerto Barcelona | MeTransfers',
				'description' => 'Traslado privado al Aeropuerto de Barcelona con seguimiento de vuelo, recogida personalizada y servicio directo desde hotel, domicilio u oficina.',
			),
			'traslados-puerto'                 => array(
				'title'       => 'Traslado Puerto de Barcelona | MeTransfers',
				'description' => 'Traslados privados al Puerto de Barcelona con recogida puerta a puerta, ayuda con equipaje y vehículos para parejas, familias y grupos.',
			),
			'chofer-por-horas'                 => array(
				'title'       => 'Chófer Privado por Horas en Barcelona | MeTransfers',
				'description' => 'Contrata un chófer privado por horas en Barcelona para reuniones, compras, eventos, itinerarios flexibles y transporte personalizado.',
			),
			'corporativo-y-eventos'            => array(
				'title'       => 'Transporte Corporativo en Barcelona | MeTransfers',
				'description' => 'Transporte corporativo en Barcelona para directivos, delegaciones, congresos y eventos con vehículos coordinados y conductores profesionales.',
			),
			'grupos'                           => array(
				'title'       => 'Traslados para Grupos en Barcelona | MeTransfers',
				'description' => 'Transporte privado para grupos en Barcelona con minivans y vehículos coordinados para aeropuerto, hoteles, eventos, tours y larga distancia.',
			),
			'preguntas-frecuentes'             => array(
				'title'       => 'Preguntas Frecuentes | MeTransfers Barcelona',
				'description' => 'Resuelve dudas sobre traslados privados en Barcelona: recogidas, retrasos de vuelo, equipaje, sillas infantiles, cancelaciones y reservas.',
			),
			'traslado-estacion-sants-barcelona' => array(
				'title'       => 'Traslado Estación Sants Barcelona | MeTransfers',
				'description' => 'Traslados privados desde y hacia Barcelona Sants con recogida directa en hotel, aeropuerto, puerto, estación o cualquier dirección.',
			),
			'traslados-hoteles-barcelona'      => array(
				'title'       => 'Traslados a Hoteles en Barcelona | MeTransfers',
				'description' => 'Traslados privados entre hoteles de Barcelona, aeropuerto, puerto, estaciones y destinos de Cataluña y España con chófer profesional.',
			),
			'flota'                            => array(
				'title'       => 'Flota para Traslados Privados | MeTransfers Barcelona',
				'description' => 'Conoce la flota de MeTransfers para transporte privado en Barcelona: vehículos ejecutivos y minivans con capacidad para pasajeros y equipaje.',
			),
		);
	}
}
