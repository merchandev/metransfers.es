<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BookingImporterSecurityTest extends TestCase {
	public function testImporterIsHotelScopedAndDoesNotTriggerPayments(): void {
		$root     = dirname( __DIR__, 2 );
		$importer = file_get_contents( $root . '/app/HotelPortal/Services/BookingImporter.php' );

		self::assertStringContainsString( 'HotelAccess::requireHotel( $hotel_id )', $importer );
		self::assertStringContainsString( "'hotel_id'", $importer );
		self::assertStringContainsString( 'absint( $hotel_id )', $importer );
		self::assertStringContainsString( "'created_by_user_id'", $importer );
		self::assertStringContainsString( 'get_current_user_id()', $importer );
		self::assertStringContainsString( "'source'", $importer );
		self::assertStringContainsString( "'Importación Excel'", $importer );
		self::assertStringNotContainsString( "\$get( 'token_hotel' )", $importer );
		self::assertStringNotContainsString( 'Gateway', $importer );
		self::assertStringNotContainsString( 'NotificationService', $importer );
		self::assertStringContainsString( 'wp_verify_nonce', $importer );
	}
}
