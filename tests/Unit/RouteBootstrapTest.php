<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\SEO\RouteBootstrap;
use PHPUnit\Framework\TestCase;

final class RouteBootstrapTest extends TestCase {
	public function testHistoricalDemandRoutesAreIncludedInBootstrapCatalog(): void {
		$catalog = RouteBootstrap::catalog();

		self::assertSame( 'PortAventura', $catalog['barcelona-portaventura'] );
		self::assertSame( 'Costa Brava', $catalog['barcelona-costa-brava'] );
		self::assertSame( 'Vielha', $catalog['barcelona-vielha'] );
		self::assertSame( 'Peñíscola', $catalog['barcelona-peniscola'] );
		self::assertSame( 'Delta del Ebro', $catalog['barcelona-delta-del-ebro'] );
		self::assertSame( 'Badalona', $catalog['barcelona-badalona'] );
		self::assertSame( 'Perpignan', $catalog['barcelona-perpignan'] );
		self::assertSame( 'Madrid', $catalog['barcelona-madrid'] );
	}
}
