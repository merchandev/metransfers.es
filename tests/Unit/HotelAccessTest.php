<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\Admin\Capabilities;
use MeTransfers\HotelPortal\Access\HotelAccess;
use MeTransfers\Tests\Support\HotelAccessDatabase;
use PHPUnit\Framework\TestCase;

final class HotelAccessTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['mt_test_current_user_id'] = 0;
		$GLOBALS['mt_test_user_caps']       = array();
		$GLOBALS['mt_test_user_meta']       = array();
		$GLOBALS['mt_test_post_types']      = array(
			10 => 'hotel_partner',
			11 => 'hotel_partner',
			12 => 'hotel_partner',
		);
		$GLOBALS['mt_test_post_statuses']   = array(
			10 => 'publish',
			11 => 'publish',
			12 => 'draft',
		);
		$GLOBALS['mt_test_get_posts']       = static fn( array $args ): array => array( 10, 11 );
		unset( $GLOBALS['mt_test_status_header'] );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['mt_test_get_posts'], $GLOBALS['wpdb'] );
	}

	public function testAssignedUserCanOnlyAccessPublishedAssignedHotel(): void {
		$GLOBALS['mt_test_user_meta'][101]['mt_hotel_ids'] = array( '10', 10, 12, 0, 'invalid' );

		self::assertSame( array( 10 ), HotelAccess::userHotelIds( 101 ) );
		self::assertTrue( HotelAccess::canAccessHotel( 10, 101 ) );
		self::assertFalse( HotelAccess::canAccessHotel( 11, 101 ) );
		self::assertFalse( HotelAccess::canAccessHotel( 12, 101 ) );
	}

	public function testAdministratorCanAccessEveryPublishedHotel(): void {
		$GLOBALS['mt_test_user_caps'][1]['manage_options'] = true;

		self::assertSame( array( 10, 11 ), HotelAccess::userHotelIds( 1 ) );
		self::assertTrue( HotelAccess::canAccessHotel( 11, 1 ) );
	}

	public function testPortalEntryRequiresCapabilityAndAnActiveAssignment(): void {
		$GLOBALS['mt_test_user_caps'][101][ Capabilities::HOTEL_PORTAL_ACCESS ] = true;
		$GLOBALS['mt_test_user_meta'][101]['mt_hotel_ids']                      = array( 10 );

		self::assertTrue( HotelAccess::canEnterPortal( 101 ) );
		self::assertFalse( HotelAccess::canEnterPortal( 102 ) );
	}

	public function testPrimaryHotelMustRemainInsideAuthorizedScope(): void {
		$GLOBALS['mt_test_user_meta'][103]['mt_hotel_ids']        = array( 10, 11 );
		$GLOBALS['mt_test_user_meta'][103]['mt_primary_hotel_id'] = 11;
		self::assertSame( 11, HotelAccess::activeHotelId( 103 ) );

		$GLOBALS['mt_test_user_meta'][103]['mt_primary_hotel_id'] = 12;
		self::assertSame( 10, HotelAccess::activeHotelId( 103 ) );
	}

	public function testRequireHotelRejectsCrossHotelAccess(): void {
		$GLOBALS['mt_test_user_meta'][101]['mt_hotel_ids'] = array( 10 );

		try {
			HotelAccess::requireHotel( 11, 101 );
			self::fail( 'Cross-hotel access must be rejected.' );
		} catch ( \RuntimeException $error ) {
			self::assertSame( 'hotel_access_denied', $error->getMessage() );
			self::assertSame( 403, $GLOBALS['mt_test_status_header'] );
		}
	}

	public function testBookingAccessIsScopedByItsPersistedHotelId(): void {
		$GLOBALS['mt_test_user_meta'][101]['mt_hotel_ids'] = array( 10 );
		$GLOBALS['wpdb']                                   = new HotelAccessDatabase(
			array(
				5001 => 10,
				5002 => 11,
			)
		);

		self::assertTrue( HotelAccess::canAccessBooking( 5001, 101 ) );
		self::assertFalse( HotelAccess::canAccessBooking( 5002, 101 ) );
		self::assertFalse( HotelAccess::canAccessBooking( 9999, 101 ) );
	}

	public function testImplementedHotelCapabilitiesAreGrantedToAdministrators(): void {
		$capabilities = Capabilities::all();

		self::assertContains( Capabilities::HOTEL_PORTAL_ACCESS, $capabilities );
		self::assertContains( Capabilities::HOTEL_VIEW_BOOKINGS, $capabilities );
		self::assertContains( Capabilities::HOTEL_CREATE_BOOKINGS, $capabilities );
		self::assertContains( Capabilities::HOTEL_VIEW_STATS, $capabilities );
		self::assertContains( Capabilities::HOTEL_VIEW_PROFILE, $capabilities );
		self::assertNotContains( Capabilities::HOTEL_CANCEL_BOOKINGS, $capabilities );
		self::assertNotContains( Capabilities::HOTEL_EXPORT_BOOKINGS, $capabilities );
	}
}
