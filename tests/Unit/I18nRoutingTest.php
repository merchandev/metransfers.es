<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\I18n\Language;
use MeTransfers\I18n\Router;
use MeTransfers\I18n\Seo;
use PHPUnit\Framework\TestCase;

final class I18nRoutingTest extends TestCase {
	private const LANGUAGES = array( 'es', 'en', 'zh' );

	public function testSpanishRemainsUnprefixed(): void {
		self::assertSame( 'es', Language::detectFromUri( '/es/pago/', self::LANGUAGES ) );
		self::assertNull( Router::matchRequest( '/es/pago/', self::LANGUAGES ) );
	}

	public function testNestedTranslatedRoutePreservesCanonicalSlug(): void {
		self::assertSame(
			array(
				'language' => 'en',
				'page'     => 'rutas/barcelona-salou',
			),
			Router::matchRequest( '/en/rutas/barcelona-salou/?utm=test', self::LANGUAGES )
		);
	}

	public function testUnknownVirtualRoutesDoNotReceiveFallbackTemplates(): void {
		self::assertNull( Router::fixedTemplate( 'unknown-route' ) );
		self::assertSame( 'archive-ruta.php', Router::fixedTemplate( 'rutas' ) );
	}

	public function testTranslatedCanonicalDropsQueryParameters(): void {
		self::assertSame(
			'https://example.test/en/rutas/barcelona-salou/',
			Seo::canonicalForRequest( '', '/en/rutas/barcelona-salou/?utm=test', 'en' )
		);
	}

	public function testChineseHreflangUsesZhHans(): void {
		$alternates = Seo::alternatesForRequest( '/en/rutas/', self::LANGUAGES );

		self::assertSame( 'https://example.test/zh/rutas/', $alternates['zh-Hans'] );
		self::assertSame( $alternates['es'], $alternates['x-default'] );
	}
}
