<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\HotelPortal\Services\HotelBookingAttribution;
use PHPUnit\Framework\TestCase;

final class HotelQrBookingAttributionTest extends TestCase {
	public function testNewQrBookingIsAttributedAfterMigrationsHaveAlreadyRun(): void {
		$GLOBALS['mt_test_completed_migrations'] = array( '001_hotel_portal_schema', '002_hotel_portal_backfills' );

		$attribution = HotelBookingAttribution::forQrBooking( 48, 'HOTEL-XYZ' );

		self::assertSame( 48, $attribution['hotel_id'] );
		self::assertSame( 'Hotel QR', $attribution['source'] );
		self::assertNull( $attribution['created_by_user_id'] );
		self::assertSame( 'HOTEL-XYZ', $attribution['hotel_token'] );
	}
}
