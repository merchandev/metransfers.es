<?php
namespace MeTransfers\SEO;

final class LegacyUrlMap {
	/**
	 * Aliases que no siguen el patrón geográfico normal.
	 * Paths sin barra inicial/final ni prefijo de idioma.
	 *
	 * @var array<string,string>
	 */
	private static $redirects = array(
		'destinos'                                   => 'rutas',
		'transporte-en-barcelona-para-grupos-grandes-y-equipaje-extra-la-solucion-mercedes-clase-v' => 'grupos',
		'taxis-privado-barcelona'                    => 'traslados-privados',
		'traslados-aeropuerto'                       => 'transfer-aeropuerto-barcelona',
		'aeropuerto-barcelona'                       => 'transfer-aeropuerto-barcelona',
		'transfer-puerto-barcelona'                  => 'traslados-puerto',
		'puerto-barcelona'                           => 'traslados-puerto',
		'conductor-privado'                          => 'chofer-por-horas',
		'traslados-corporativos'                     => 'corporativo-y-eventos',
		'empresas'                                   => 'corporativo-y-eventos',
		'faq'                                        => 'preguntas-frecuentes',
		'privacidad'                                 => 'politica-de-privacidad',
		'bodas-eventos'                              => 'grupos',
		'noticias'                                   => 'blog',
		'barcelona-taxis'                            => 'traslados-privados',
		'barcelona-traslados'                        => 'traslados-privados',
		// Landings duplicadas que no siguen ningún patrón geográfico detectado
		// por getTarget(): competían por las mismas keywords que su ruta
		// canónica en /rutas/ sin declarar redirección ni canonical cruzado.
		'transfer-privado-a-madrid'                  => 'rutas/barcelona-madrid',
		'transfer-privado-a-tarragona'               => 'rutas/barcelona-tarragona',
		'ebro-delta'                                 => 'rutas/barcelona-delta-del-ebro',
		'tienda-barcelona-tours-transfers/transfers/traslado-a-andorra' => 'rutas/barcelona-andorra',
		'tienda-barcelona-tours-transfers/transfers/transfer-privado-portaventura' => 'rutas/barcelona-portaventura',
		'tienda-barcelona-tours-transfers/transfers/transfer-privado-a-portaventura' => 'rutas/barcelona-portaventura',
		'tienda-barcelona-tours-transfers/transfers/transfer-privado-salou' => 'rutas/barcelona-salou',
		'tienda-barcelona-tours-transfers/transfers/transfer-privado-girona' => 'rutas/barcelona-girona',
		'tienda-barcelona-tours-transfers/transfers' => 'rutas',
		'tienda-barcelona-tours-transfers'           => '',
	);

	/**
	 * Destinos históricos que deben consolidarse bajo rutas canónicas de Barcelona.
	 * El valor es el slug canónico del CPT ruta, sin el prefijo /rutas/.
	 *
	 * @var array<string,string>
	 */
	private static $destination_routes = array(
		'salou'                   => 'barcelona-salou',
		'lloret-de-mar'           => 'barcelona-lloret-de-mar',
		'sitges'                  => 'barcelona-sitges',
		'tarragona'               => 'barcelona-tarragona',
		'reus'                    => 'barcelona-reus',
		'girona'                  => 'barcelona-girona',
		'andorra'                 => 'barcelona-andorra',
		'montserrat'              => 'barcelona-montserrat',
		'cadaques'                => 'barcelona-cadaques',
		'tossa-de-mar'            => 'barcelona-tossa-de-mar',
		'cambrils'                => 'barcelona-cambrils',
		'calella'                 => 'barcelona-calella',
		'roses'                   => 'barcelona-roses',
		'baqueira-beret'          => 'barcelona-baqueira-beret',
		'blanes'                  => 'barcelona-blanes',
		'platja-daro'             => 'barcelona-platja-daro',
		'vilanova'                => 'barcelona-vilanova',
		'calafell'                => 'barcelona-calafell',
		'la-pineda'               => 'barcelona-la-pineda',
		'la-molina'               => 'barcelona-la-molina',
		'portaventura'            => 'barcelona-portaventura',
		'port-aventura'           => 'barcelona-portaventura',
		'costa-brava'             => 'barcelona-costa-brava',
		'perpignan'               => 'barcelona-perpignan',
		'granollers'              => 'barcelona-granollers',
		'mataro'                  => 'barcelona-mataro',
		'badalona'                => 'barcelona-badalona',
		'hospitalet'              => 'barcelona-hospitalet',
		'pineda-de-mar'           => 'barcelona-pineda-de-mar',
		'sevilla'                 => 'barcelona-sevilla',
		'vigo'                    => 'barcelona-vigo',
		'benidorm'                => 'barcelona-benidorm',
		'bilbao'                  => 'barcelona-bilbao',
		'san-sebastian'           => 'barcelona-san-sebastian',
		'malgrat'                 => 'barcelona-malgrat',
		'granada'                 => 'barcelona-granada',
		'valencia'                => 'barcelona-valencia',
		'santiago-de-compostela'  => 'barcelona-santiago-de-compostela',
		'lourdes'                 => 'barcelona-lourdes',
		'vall-de-nuria'           => 'barcelona-vall-de-nuria',
		'almeria'                 => 'barcelona-almeria',
		'figueres'                => 'barcelona-figueres',
		'camping-el-delfin-verde' => 'barcelona-camping-el-delfin-verde',
		'marbella'                => 'barcelona-marbella',
		'santa-susanna'           => 'barcelona-santa-susanna',
		'begur'                   => 'barcelona-begur',
		'bagur'                   => 'barcelona-begur',
		'calella-de-palafrugell'  => 'barcelona-calella-de-palafrugell',
		'cap-de-creus'            => 'barcelona-cap-de-creus',
		'la-escala'               => 'barcelona-la-escala',
		'palamos'                 => 'barcelona-palamos',
		'madrid'                  => 'barcelona-madrid',
		'vielha'                  => 'barcelona-vielha',
		'peniscola'               => 'barcelona-peniscola',
		'delta-del-ebro'          => 'barcelona-delta-del-ebro',
		'taull'                   => 'barcelona-taull',
		'besalu'                  => 'barcelona-besalu',
		'morella'                 => 'barcelona-morella',
		'altea'                   => 'barcelona-altea',
		'valderrobres'            => 'barcelona-valderrobres',
		'alquezar'                => 'barcelona-alquezar',
		'colliure'                => 'barcelona-collioure',
		'carcasona'               => 'barcelona-carcassonne',
		'carcassonne'             => 'barcelona-carcassonne',
	);

	public static function hasRedirect( string $slug ): bool {
		return null !== self::getTarget( $slug );
	}

	public static function getTarget( string $slug ): ?string {
		$slug = trim( strtolower( $slug ), '/' );
		if ( array_key_exists( $slug, self::$redirects ) ) {
			return self::$redirects[ $slug ];
		}

		if ( 0 === strpos( $slug, 'destinos/' ) ) {
			$destination = substr( $slug, strlen( 'destinos/' ) );
			return self::routeTargetForDestination( $destination );
		}

		if ( preg_match( '/^(?:taxis|traslados)-barcelona-(.+)$/', $slug, $matches ) ) {
			return self::routeTargetForDestination( $matches[1] );
		}

		if ( preg_match( '/^(.+)-(?:taxis|traslados)$/', $slug, $matches ) ) {
			return self::routeTargetForDestination( $matches[1] );
		}

		return null;
	}

	public static function getMap(): array {
		$map = self::$redirects;
		foreach ( self::$destination_routes as $destination => $route ) {
			$map[ 'destinos/' . $destination ] = 'rutas/' . $route;
		}
		return $map;
	}

	public static function destinationRoutes(): array {
		return self::$destination_routes;
	}

	private static function routeTargetForDestination( string $destination ): ?string {
		$destination = trim( strtolower( $destination ), '/' );
		if ( isset( self::$destination_routes[ $destination ] ) ) {
			return 'rutas/' . self::$destination_routes[ $destination ];
		}
		return null;
	}
}
