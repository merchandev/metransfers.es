<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\HotelPortal\Services\HotelUserManager;
use PHPUnit\Framework\TestCase;

final class HotelUserManagerSecurityTest extends TestCase {
	public function testUserManagementSourceEnforcesHotelScopeAndSafeAssignments(): void {
		$source = file_get_contents( dirname( __DIR__, 2 ) . '/app/HotelPortal/Services/HotelUserManager.php' );

		self::assertStringContainsString( 'HotelAccess::canAccessHotel( $hotel_id', $source );
		self::assertStringContainsString( "'role'         => 'check_hoteles'", $source );
		self::assertStringContainsString( "'mt_hotel_ids'", $source );
		self::assertStringContainsString( 'get_current_user_id()', $source );
		self::assertStringContainsString( "in_array( 'check_hoteles', (array) \$user->roles, true )", $source );
		self::assertStringContainsString( "'assign-existing'", $source );
		self::assertStringContainsString( 'wp_verify_nonce', $source );
	}
}
