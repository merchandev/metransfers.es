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
			? sprintf( 'Book your private transfer from %s to %s. Confirm your pickup address, luggage and travel details with MeTransfers before departure.', $origin, $destination )
			: sprintf( 'Reserva tu traslado privado de %s a %s. Confirma el punto de recogida, el equipaje y los detalles del viaje con MeTransfers antes de salir.', $origin, $destination );
	}

	public static function pageText( $post, string $field, string $language = 'es' ): string {
		if ( ! $post || 'page' !== $post->post_type ) {
			return '';
		}

		$es = array(
			'traslados-privados' => array(
				'title'       => 'Traslados Privados desde Barcelona | España | MeTransfers',
				'description' => 'Reserva traslados privados desde Barcelona a cualquier destino de España. Recogida en aeropuerto, puerto, Sants, hotel o dirección con chófer profesional.',
			),
			'transfer-aeropuerto-barcelona' => array(
				'title'       => 'Transfer Aeropuerto Barcelona | BCN Privado | MeTransfers',
				'description' => 'Transfer privado desde o hacia el Aeropuerto de Barcelona BCN. Recogida en T1/T2, hotel, puerto, Sants o cualquier dirección. Servicio puerta a puerta.',
			),
			'traslados-puerto' => array(
				'title'       => 'Transfer Puerto de Barcelona | Cruceros | MeTransfers',
				'description' => 'Traslado privado al Puerto de Barcelona y terminales de cruceros. Recogida en hotel, aeropuerto, Sants o cualquier dirección con espacio para equipaje.',
			),
			'traslado-estacion-sants-barcelona' => array(
				'title'       => 'Transfer Estación Sants Barcelona | MeTransfers',
				'description' => 'Traslado privado desde Barcelona Sants a hotel, aeropuerto, puerto o destinos fuera de la ciudad. Confirma recogida, pasajeros, equipaje y horario.',
			),
			'traslados-hoteles-barcelona' => array(
				'title'       => 'Traslados Privados para Hoteles en Barcelona | MeTransfers',
				'description' => 'Traslados privados desde y hacia hoteles de Barcelona: aeropuerto, puerto, Sants y otros destinos. Servicio con chófer y recogida coordinada.',
			),
			'chofer-por-horas' => array(
				'title'       => 'Chófer Privado por Horas en Barcelona | MeTransfers',
				'description' => 'Reserva coche con chófer por horas en Barcelona para reuniones, eventos, compras o itinerarios con varias paradas. Servicio privado bajo reserva.',
			),
			'corporativo-y-eventos' => array(
				'title'       => 'Transporte Corporativo en Barcelona | MeTransfers',
				'description' => 'Transfers corporativos en Barcelona para empresas, congresos, eventos y delegaciones. Coordinación de vehículos, aeropuerto, hoteles y reuniones.',
			),
			'grupos' => array(
				'title'       => 'Transfer para Grupos en Barcelona | Minivan | MeTransfers',
				'description' => 'Traslados privados para grupos en Barcelona con recogida en aeropuerto, puerto, Sants, hoteles o direcciones. Vehículos según pasajeros y equipaje.',
			),
			'tours-privados' => array(
				'title'       => 'Tours Privados desde Barcelona con Chófer | MeTransfers',
				'description' => 'Tours privados desde Barcelona con recogida en hotel o dirección. Consulta rutas a Montserrat, Girona, Costa Brava y otros destinos con chófer.',
			),
			'preguntas-frecuentes' => array(
				'title'       => 'FAQ de Traslados Privados en Barcelona | MeTransfers',
				'description' => 'Resuelve dudas sobre reservas, aeropuerto BCN, puerto, Sants, hoteles, equipaje, grupos, pagos, cancelaciones y traslados privados desde Barcelona.',
			),
		);

		$en = array(
			'traslados-privados' => array(
				'title'       => 'Private Transfers from Barcelona | Spain | MeTransfers',
				'description' => 'Book private transfers from Barcelona to destinations across Spain, with pickup at the airport, cruise port, Sants station, hotel or an agreed address.',
			),
			'transfer-aeropuerto-barcelona' => array(
				'title'       => 'Barcelona Airport Transfer | Private BCN | MeTransfers',
				'description' => 'Private transfer to or from Barcelona Airport BCN. Pickup at T1/T2, hotels, cruise port, Sants station or an agreed address.',
			),
			'traslados-puerto' => array(
				'title'       => 'Barcelona Cruise Port Transfer | MeTransfers',
				'description' => 'Private Barcelona cruise port transfers to or from hotels, BCN Airport, Sants station and other destinations, with luggage-friendly vehicle options.',
			),
			'traslado-estacion-sants-barcelona' => array(
				'title'       => 'Barcelona Sants Station Transfer | MeTransfers',
				'description' => 'Private transfer from Barcelona Sants station to your hotel, BCN Airport, cruise port or another destination. Confirm pickup, luggage and timing.',
			),
			'traslados-hoteles-barcelona' => array(
				'title'       => 'Barcelona Hotel Transfers | MeTransfers',
				'description' => 'Private transfers to and from Barcelona hotels, BCN Airport, cruise port, Sants station and other destinations with coordinated pickup.',
			),
			'chofer-por-horas' => array(
				'title'       => 'Private Chauffeur by the Hour in Barcelona | MeTransfers',
				'description' => 'Book a private chauffeur by the hour in Barcelona for meetings, events, shopping or multi-stop itineraries. Service available by reservation.',
			),
			'corporativo-y-eventos' => array(
				'title'       => 'Corporate Transfers Barcelona | MeTransfers',
				'description' => 'Corporate transfers in Barcelona for companies, congresses, events and delegations, including coordinated airport, hotel and meeting transport.',
			),
			'grupos' => array(
				'title'       => 'Group Transfers Barcelona | Minivan | MeTransfers',
				'description' => 'Private group transfers in Barcelona with pickup at the airport, cruise port, Sants station, hotels or agreed addresses. Vehicles matched to luggage.',
			),
			'tours-privados' => array(
				'title'       => 'Private Tours from Barcelona with Chauffeur | MeTransfers',
				'description' => 'Private tours from Barcelona with hotel or address pickup. Ask about Montserrat, Girona, Costa Brava and other destinations with a chauffeur.',
			),
			'preguntas-frecuentes' => array(
				'title'       => 'Private Transfer FAQs Barcelona | MeTransfers',
				'description' => 'Answers about booking, BCN Airport, cruise port, Sants station, hotels, luggage, group transfers, payments and cancellations in Barcelona.',
			),
		);

		if ( 'en' === $language ) {
			$map = $en;
		} elseif ( 'es' === $language ) {
			$map = $es;
		} else {
			return '';
		}
		return $map[ $post->post_name ][ $field ] ?? '';
	}

	public static function title( $title ) {
		if ( is_post_type_archive( 'ruta' ) ) {
			return 'en' === \MeTransfers\I18n\Language::get() ? 'Private transfer routes from Barcelona | MeTransfers' : 'Rutas de transfer desde Barcelona | MeTransfers';
		}
		$post = get_queried_object();
		if ( $post ) {
			$page = self::pageText( $post, 'title', \MeTransfers\I18n\Language::get() );
			if ( '' !== $page ) {
				return $page;
			}
		}
		if ( is_singular( 'ruta' ) && $post && ! get_post_meta( $post->ID, '_yoast_wpseo_title', true ) ) {
			$generated = self::routeText( $post, 'title', \MeTransfers\I18n\Language::get() );
			return '' !== $generated ? $generated : $title;
		}
		return $title;
	}

	public static function description( $description ) {
		if ( is_post_type_archive( 'ruta' ) ) {
			return 'en' === \MeTransfers\I18n\Language::get() ? 'Explore private transfer routes from Barcelona. Choose your destination and contact MeTransfers to confirm your pickup and travel details.' : 'Consulta las rutas de traslado privado desde Barcelona. Elige tu destino y contacta con MeTransfers para confirmar la recogida y los detalles de tu viaje.';
		}
		$post = get_queried_object();
		if ( $post ) {
			$page = self::pageText( $post, 'description', \MeTransfers\I18n\Language::get() );
			if ( '' !== $page ) {
				return $page;
			}
		}
		if ( is_singular( 'ruta' ) && $post && ! get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ) ) {
			$generated = self::routeText( $post, 'description', \MeTransfers\I18n\Language::get() );
			return '' !== $generated ? $generated : $description;
		}
		return $description;
	}
}
