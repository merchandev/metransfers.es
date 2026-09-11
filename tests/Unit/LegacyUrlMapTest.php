<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\SEO\LegacyUrlMap;
use PHPUnit\Framework\TestCase;

final class LegacyUrlMapTest extends TestCase {
	/**
	 * @dataProvider exactLegacyRedirects
	 */
	public function testLegacyAliasesMapToFinalRoutes( string $legacy, string $target ): void {
		self::assertTrue( LegacyUrlMap::hasRedirect( $legacy ) );
		self::assertSame( $target, LegacyUrlMap::getTarget( $legacy ) );
	}

	public static function exactLegacyRedirects(): array {
		return array(
			'reus taxis'                 => array( 'reus-taxis', 'rutas/barcelona-reus' ),
			'montserrat transfers'       => array( 'montserrat-traslados', 'rutas/barcelona-montserrat' ),
			'salou destination'          => array( 'destinos/salou', 'rutas/barcelona-salou' ),
			'cadaques historical taxi'   => array( 'taxis-barcelona-cadaques', 'rutas/barcelona-cadaques' ),
			'girona historical taxi'     => array( 'taxis-barcelona-girona', 'rutas/barcelona-girona' ),
			'port aventura historical'   => array( 'taxis-barcelona-port-aventura', 'rutas/barcelona-portaventura' ),
			'badalona generated page'    => array( 'badalona-taxis', 'rutas/barcelona-badalona' ),
			'costa brava generated page' => array( 'costa-brava-traslados', 'rutas/barcelona-costa-brava' ),
			'vielha historical taxi'     => array( 'taxis-barcelona-vielha', 'rutas/barcelona-vielha' ),
			'peniscola historical taxi'  => array( 'taxis-barcelona-peniscola', 'rutas/barcelona-peniscola' ),
			'delta historical transfer'  => array( 'traslados-barcelona-delta-del-ebro', 'rutas/barcelona-delta-del-ebro' ),
			'airport alias'              => array( 'aeropuerto-barcelona', 'transfer-aeropuerto-barcelona' ),
			'port alias'                 => array( 'puerto-barcelona', 'traslados-puerto' ),
			'corporate alias'            => array( 'traslados-corporativos', 'corporativo-y-eventos' ),
			'faq alias'                  => array( 'faq', 'preguntas-frecuentes' ),
		);
	}

	public function testUnknownUrlsRemainUnmapped(): void {
		self::assertFalse( LegacyUrlMap::hasRedirect( 'taxis-barcelona-url-que-nunca-existio' ) );
		self::assertNull( LegacyUrlMap::getTarget( 'taxis-barcelona-url-que-nunca-existio' ) );
	}
}
