<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\SEO\Redirects;
use PHPUnit\Framework\TestCase;

final class RedirectTargetTest extends TestCase {
	public function testSpanishLegacyAliasResolvesToCanonicalRoute(): void {
		self::assertSame(
			'/rutas/barcelona-salou/?utm=test',
			Redirects::targetForRequest( '/taxis-barcelona-salou/?utm=test', array( 'es', 'en' ) )
		);
	}

	public function testEnglishLegacyAliasKeepsLanguageUntilEligibilityCheck(): void {
		self::assertSame(
			'/en/rutas/barcelona-salou/',
			Redirects::targetForRequest( '/en/salou-traslados/', array( 'es', 'en' ) )
		);
	}

	public function testRetiredLanguageIsCollapsedToSpanishCanonical(): void {
		self::assertSame(
			'/rutas/barcelona-salou/',
			Redirects::targetForRequest( '/fr/salou-taxis/', array( 'es', 'en' ) )
		);
	}

	public function testRetiredLanguagePreservesEquivalentSpanishPath(): void {
		self::assertSame(
			'/blog/?ref=old',
			Redirects::targetForRequest( '/pt/blog/?ref=old', array( 'es', 'en' ) )
		);
	}

	public function testUnknownSpanishUrlIsNotRedirected(): void {
		self::assertNull( Redirects::targetForRequest( '/esta-url-no-debe-existir-2026/', array( 'es', 'en' ) ) );
	}
}
