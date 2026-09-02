<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HotelQrRouteDistanceTest extends TestCase {
	public function testQrBookingCannotSilentlyPersistAZeroDistance(): void {
		$source = file_get_contents( dirname( __DIR__, 2 ) . '/app/Legacy/Hotel/public/class-hqp-public.php' );

		self::assertIsString( $source );
		self::assertStringContainsString( "'code'    => 'route_distance_unavailable'", $source );
		self::assertStringContainsString( 'if ( $distance_km <= 0 )', $source );
		self::assertStringNotContainsString( '$distance_km = 0;', $source );
	}
}
