<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HotelDashboardSecurityTest extends TestCase {
	public function testDashboardQueriesAreAlwaysScopedByHotelId(): void {
		$source = file_get_contents( dirname( __DIR__, 2 ) . '/app/HotelPortal/Services/HotelDashboard.php' );

		self::assertStringContainsString( 'HotelAccess::requireHotel( $hotel_id )', $source );
		self::assertGreaterThanOrEqual( 2, substr_count( $source, 'WHERE hotel_id = %d' ) );
		self::assertStringContainsString( 'LIMIT 8', $source );
		self::assertStringNotContainsString( 'hotel_token =', $source );
	}
}
