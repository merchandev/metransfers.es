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
		self::assertStringContainsString( "\$map['token_hotel']", $importer );
		self::assertStringContainsString( 'HotelAccess::userHotelIds()', $importer );
		self::assertStringContainsString( '$wpdb->update', $importer );
		self::assertStringContainsString( 'existingId', $importer );
		self::assertStringContainsString( 'vehicleId', $importer );
		self::assertStringContainsString( "\$get( 'precio' )", $importer );
		self::assertStringContainsString( "\$get( 'distancia_km' )", $importer );
		self::assertStringNotContainsString( 'Gateway', $importer );
		self::assertStringNotContainsString( 'NotificationService', $importer );
		self::assertStringContainsString( 'wp_verify_nonce', $importer );
	}
}
