<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\I18n\Language;
use MeTransfers\I18n\Router;
use MeTransfers\I18n\Seo;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/Support/RouterWordPress.php';

final class I18nRoutingTest extends TestCase {
	private const LANGUAGES = array( 'es', 'en' );

	public function testOnlyPublicUnprotectedPostsCanBeHydrated(): void {
		foreach ( array( 'draft', 'private', 'trash', 'pending', 'future' ) as $status ) {
			self::assertFalse(
				Router::isPublicPost(
					(object) array(
						'post_status'   => $status,
						'post_password' => '',
					)
				),
				$status
			);
		}
		self::assertFalse( Router::isPublicPost( null ) );
		self::assertFalse(
			Router::isPublicPost(
				(object) array(
					'post_status'   => 'publish',
					'post_password' => '0',
				)
			)
		);
		self::assertFalse(
			Router::isPublicPost(
				(object) array(
					'post_status'   => 'publish',
					'post_password' => 'secret',
				)
			)
		);
		self::assertFalse(
			Router::isPublicPost(
				(object) array(
					'post_status'       => 'publish',
					'post_password'     => '',
					'publicly_viewable' => false,
				)
			)
		);
		self::assertTrue(
			Router::isPublicPost(
				(object) array(
					'post_status'   => 'publish',
					'post_password' => '',
				)
			)
		);
	}

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

	public function testRetiredLanguagesAreNotActiveRoutes(): void {
		self::assertNull( Router::matchRequest( '/fr/rutas/barcelona-salou/', self::LANGUAGES ) );
		self::assertSame( 'es', Language::detectFromUri( '/fr/rutas/barcelona-salou/', self::LANGUAGES ) );
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

	public function testRegionalHreflangUsesSpanishAndUsEnglish(): void {
		$alternates = Seo::alternatesForRequest( '/en/traslados-privados/', self::LANGUAGES );

		self::assertSame( 'https://example.test/traslados-privados/', $alternates['es-ES'] );
		self::assertSame( 'https://example.test/en/traslados-privados/', $alternates['en-US'] );
		self::assertSame( $alternates['es-ES'], $alternates['x-default'] );
	}
}
