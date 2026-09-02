<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\HotelPortal\Services\HotelContext;
use PHPUnit\Framework\TestCase;

final class HotelContextTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['mt_test_user_meta']     = array(
			103 => array(
				'mt_hotel_ids'        => array( 10, 11 ),
				'mt_primary_hotel_id' => 10,
			),
		);
		$GLOBALS['mt_test_post_types']    = array(
			10 => 'hotel_partner',
			11 => 'hotel_partner',
			12 => 'hotel_partner',
		);
		$GLOBALS['mt_test_post_statuses'] = array(
			10 => 'publish',
			11 => 'publish',
			12 => 'publish',
		);
	}

	public function testRequestedHotelMustBelongToUser(): void {
		self::assertSame( 11, HotelContext::currentHotelId( 11, 103 ) );
		self::assertSame( 10, HotelContext::currentHotelId( 12, 103 ) );
	}

	public function testPersistedHotelIsAlwaysRevalidated(): void {
		$GLOBALS['mt_test_user_meta'][103]['mt_active_hotel_id'] = 11;
		self::assertSame( 11, HotelContext::currentHotelId( 0, 103 ) );

		$GLOBALS['mt_test_user_meta'][103]['mt_active_hotel_id'] = 12;
		self::assertSame( 10, HotelContext::currentHotelId( 0, 103 ) );
	}

	public function testHotelSelectionCannotPersistCrossHotelId(): void {
		self::assertTrue( HotelContext::selectHotel( 11, 103 ) );
		self::assertSame( 11, $GLOBALS['mt_test_user_meta'][103]['mt_active_hotel_id'] );
		self::assertFalse( HotelContext::selectHotel( 12, 103 ) );
		self::assertSame( 11, $GLOBALS['mt_test_user_meta'][103]['mt_active_hotel_id'] );
	}

	public function testSelectingTheAlreadyActiveHotelIsSuccessful(): void {
		$GLOBALS['mt_test_user_meta'][103]['mt_active_hotel_id'] = 11;

		self::assertTrue( HotelContext::selectHotel( 11, 103 ) );
		self::assertSame( 11, $GLOBALS['mt_test_user_meta'][103]['mt_active_hotel_id'] );
	}
}
