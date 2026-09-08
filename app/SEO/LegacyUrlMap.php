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
		'traslados-privados'                         => 'taxis-privado-barcelona',
		'traslados-aeropuerto'                       => 'transfer-aeropuerto-barcelona',
		'taxis-barcelona-costa-brava'                => 'destinos/costa-brava',
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

		'costa-brava-taxis'                          => 'rutas/barcelona-costa-brava',
		'costa-brava-traslados'                      => 'rutas/barcelona-costa-brava',

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
