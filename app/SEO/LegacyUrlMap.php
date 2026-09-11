<?php
namespace MeTransfers\SEO;

final class LegacyUrlMap {

	/**
	 * Mapeo de URLs antiguas hacia las nuevas URLs (principalmente rutas).
	 * No incluyen prefijos de idioma, ya que el sistema Language-Aware
	 * los manejará dinámicamente.
	 *
	 * Formato: 'slug-antiguo' => 'slug-nuevo'
	 */
	private static $redirects = array(
		'destinos'                                   => 'rutas',
		'transporte-en-barcelona-para-grupos-grandes-y-equipaje-extra-la-solucion-mercedes-clase-v' => 'grupos',
		'taxis-privado-barcelona'                    => 'traslados-privados',
		'traslados-aeropuerto'                       => 'transfer-aeropuerto-barcelona',

		// Hub/pages antiguas de destino con reemplazo exacto en /rutas/.
		'destinos/salou'                             => 'rutas/barcelona-salou',
		'destinos/lloret-de-mar'                     => 'rutas/barcelona-lloret-de-mar',
		'destinos/reus'                              => 'rutas/barcelona-reus',
		'destinos/girona'                            => 'rutas/barcelona-girona',
		'destinos/andorra'                           => 'rutas/barcelona-andorra',
		'destinos/sitges'                            => 'rutas/barcelona-sitges',
		'destinos/tarragona'                         => 'rutas/barcelona-tarragona',
		'destinos/montserrat'                        => 'rutas/barcelona-montserrat',
		'destinos/cadaques'                          => 'rutas/barcelona-cadaques',
		'destinos/tossa-de-mar'                      => 'rutas/barcelona-tossa-de-mar',
		'destinos/cambrils'                          => 'rutas/barcelona-cambrils',
		'destinos/calella'                           => 'rutas/barcelona-calella',
		'destinos/roses'                             => 'rutas/barcelona-roses',
		'destinos/baqueira-beret'                    => 'rutas/barcelona-baqueira-beret',
		'destinos/blanes'                            => 'rutas/barcelona-blanes',
		'destinos/platja-daro'                       => 'rutas/barcelona-platja-daro',
		'destinos/vilanova'                          => 'rutas/barcelona-vilanova',
		'destinos/calafell'                          => 'rutas/barcelona-calafell',
		'destinos/la-pineda'                         => 'rutas/barcelona-la-pineda',
		'destinos/la-molina'                         => 'rutas/barcelona-la-molina',

		// Aliases históricos de rutas con reemplazo exacto.
		'taxis-barcelona-tossa-de-mar'               => 'rutas/barcelona-tossa-de-mar',
		'traslados-barcelona-tossa-de-mar'           => 'rutas/barcelona-tossa-de-mar',
		'traslados-barcelona-andorra'                => 'rutas/barcelona-andorra',
		'taxis-barcelona-andorra'                    => 'rutas/barcelona-andorra',
		'traslados-barcelona-cadaques'               => 'rutas/barcelona-cadaques',
		'tienda-barcelona-tours-transfers/transfers/traslado-a-andorra' => 'rutas/barcelona-andorra',
		'tienda-barcelona-tours-transfers/transfers/transfer-privado-portaventura' => 'taxis-barcelona-port-aventura',
		'tienda-barcelona-tours-transfers/transfers/transfer-privado-a-portaventura' => 'taxis-barcelona-port-aventura',
		'tienda-barcelona-tours-transfers/transfers/transfer-privado-salou' => 'rutas/barcelona-salou',
		'tienda-barcelona-tours-transfers/transfers/transfer-privado-girona' => 'rutas/barcelona-girona',
		'tienda-barcelona-tours-transfers/transfers' => 'rutas',
		'tienda-barcelona-tours-transfers'           => '',
		'taxis-barcelona-salou'                      => 'rutas/barcelona-salou',
		'salou-taxis'                                => 'rutas/barcelona-salou',
		'salou-traslados'                            => 'rutas/barcelona-salou',
		'taxis-barcelona-cadaques'                   => 'rutas/barcelona-cadaques',
		'andorra-taxis'                              => 'rutas/barcelona-andorra',
		'andorra-traslados'                          => 'rutas/barcelona-andorra',
		'lloret-de-mar-taxis'                        => 'rutas/barcelona-lloret-de-mar',
		'lloret-de-mar-traslados'                    => 'rutas/barcelona-lloret-de-mar',
		'sitges-taxis'                               => 'rutas/barcelona-sitges',
		'sitges-traslados'                           => 'rutas/barcelona-sitges',
		'taxis-barcelona-sitges'                     => 'rutas/barcelona-sitges',
		'tarragona-taxis'                            => 'rutas/barcelona-tarragona',
		'tarragona-traslados'                        => 'rutas/barcelona-tarragona',
		'girona-taxis'                               => 'rutas/barcelona-girona',
		'girona-traslados'                           => 'rutas/barcelona-girona',
		'taxis-barcelona-girona'                     => 'rutas/barcelona-girona',
		'reus-taxis'                                 => 'rutas/barcelona-reus',
		'reus-traslados'                             => 'rutas/barcelona-reus',
		'montserrat-taxis'                           => 'rutas/barcelona-montserrat',
		'montserrat-traslados'                       => 'rutas/barcelona-montserrat',

		// Duplicados genéricos de la antigua arquitectura.
		'barcelona-taxis'                            => 'traslados-privados',
		'barcelona-traslados'                        => 'traslados-privados',

		// Costa Brava, Perpignan, Granollers, Mataró, Badalona y Hospitalet
		// se mantienen hasta disponer de una ruta final equivalente aprobada.

		// 404s identificados a corregir (transaccionales / fijos)
		'transfer-puerto-barcelona'                  => 'traslados-puerto',
		'empresas'                                   => 'corporativo-y-eventos',
	);

	/**
	 * Comprueba si un slug antiguo tiene redirección configurada.
	 */
	public static function hasRedirect( string $slug ): bool {
		$slug = trim( $slug, '/' );
		return isset( self::$redirects[ $slug ] );
	}

	/**
	 * Devuelve el nuevo path (sin idioma) para un slug antiguo.
	 * Si no existe, devuelve null.
	 */
	public static function getTarget( string $slug ): ?string {
		$slug = trim( $slug, '/' );
		return self::$redirects[ $slug ] ?? null;
	}

	/**
	 * Devuelve todo el mapa.
	 */
	public static function getMap(): array {
		return self::$redirects;
	}
}
