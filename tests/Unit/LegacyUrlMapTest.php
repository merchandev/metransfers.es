<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\SEO\LegacyUrlMap;
use PHPUnit\Framework\TestCase;

final class LegacyUrlMapTest extends TestCase {
	/**
	 * @dataProvider exactLegacyRedirects
	 */
	public function testExactLegacyAliasesMapToFinalRoutes( string $legacy, string $target ): void {
		self::assertTrue( LegacyUrlMap::hasRedirect( $legacy ) );
		self::assertSame( $target, LegacyUrlMap::getTarget( $legacy ) );
	}

	public static function exactLegacyRedirects(): array {
		return array(
			'reus taxis' => array( 'reus-taxis', 'rutas/barcelona-reus' ),
			'reus transfers' => array( 'reus-traslados', 'rutas/barcelona-reus' ),
			'montserrat taxis' => array( 'montserrat-taxis', 'rutas/barcelona-montserrat' ),
			'montserrat transfers' => array( 'montserrat-traslados', 'rutas/barcelona-montserrat' ),
			'salou destination' => array( 'destinos/salou', 'rutas/barcelona-salou' ),
			'lloret destination' => array( 'destinos/lloret-de-mar', 'rutas/barcelona-lloret-de-mar' ),
			'cadaques destination' => array( 'destinos/cadaques', 'rutas/barcelona-cadaques' ),
			'barcelona taxis generic' => array( 'barcelona-taxis', 'traslados-privados' ),
			'barcelona transfers generic' => array( 'barcelona-traslados', 'traslados-privados' ),
		);
	}

	/**
	 * Ambiguous locations must not be force-redirected to an unrelated route.
	 */
	public function testAmbiguousLegacyPagesRemainUnmappedUntilEquivalentRouteExists(): void {
		foreach ( array( 'badalona-taxis', 'hospitalet-taxis', 'granollers-taxis', 'perpignan-taxis', 'costa-brava-taxis' ) as $slug ) {
			self::assertFalse( LegacyUrlMap::hasRedirect( $slug ), $slug );
			self::assertNull( LegacyUrlMap::getTarget( $slug ), $slug );
		}
	}
}
