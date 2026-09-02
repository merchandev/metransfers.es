<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use MeTransfers\Core\DataMigrations;
use MeTransfers\Tests\Support\HotelPortalMigrationDatabase;
use PHPUnit\Framework\TestCase;

final class HotelPortalMigrationTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['mt_test_post_meta'] = array(
			10 => array( '_hqp_token' => 'token-a' ),
			11 => array( '_hqp_token' => 'token-b' ),
		);
		$GLOBALS['mt_test_users']     = array();
		$GLOBALS['mt_test_user_meta'] = array();
		$GLOBALS['mt_test_get_posts'] = static function ( array $args ): array {
			if ( isset( $args['author'] ) ) {
				return 101 === (int) $args['author'] ? array( 10, '10', 11 ) : array();
			}
			return array( 10, 11 );
		};
	}

	protected function tearDown(): void {
		unset( $GLOBALS['mt_test_get_posts'], $GLOBALS['wpdb'] );
	}

	public function testBookingBackfillPreservesHistoryAndExistingRelations(): void {
		$database        = new HotelPortalMigrationDatabase(
			array(
				array(
					'id'          => 1,
					'hotel_token' => 'token-a',
					'hotel_id'    => null,
					'source'      => 'Metransfers',
				),
				array(
					'id'          => 2,
					'hotel_token' => 'token-a',
					'hotel_id'    => null,
					'source'      => 'BTT',
				),
				array(
					'id'          => 3,
					'hotel_token' => 'token-b',
					'hotel_id'    => null,
					'source'      => '',
				),
				array(
					'id'          => 4,
					'hotel_token' => 'token-a',
					'hotel_id'    => 99,
					'source'      => 'Hotel Portal',
				),
			)
		);
		$GLOBALS['wpdb'] = $database;

		DataMigrations::backfillHotelBookingRelations();

		self::assertSame( 10, $database->rows[0]['hotel_id'] );
		self::assertSame( 'Hotel QR', $database->rows[0]['source'] );
		self::assertSame( 'token-a', $database->rows[0]['hotel_token'] );
		self::assertSame( 10, $database->rows[1]['hotel_id'] );
		self::assertSame( 'BTT', $database->rows[1]['source'] );
		self::assertSame( 11, $database->rows[2]['hotel_id'] );
		self::assertSame( 'Hotel QR', $database->rows[2]['source'] );
		self::assertSame( 99, $database->rows[3]['hotel_id'] );
		self::assertSame( 'Hotel Portal', $database->rows[3]['source'] );
	}

	public function testUserAssignmentBackfillOnlyFillsEmptyCanonicalMeta(): void {

		$GLOBALS['mt_test_users']                          = array( 101, 102 );
		$GLOBALS['mt_test_user_meta'][102]['mt_hotel_ids'] = array( 42 );

		DataMigrations::backfillHotelUserAssignments();

		self::assertSame( array( 10, 11 ), $GLOBALS['mt_test_user_meta'][101]['mt_hotel_ids'] );
		self::assertSame( array( 42 ), $GLOBALS['mt_test_user_meta'][102]['mt_hotel_ids'] );
	}

	public function testKnownAccountAndRelationshipRepairMigrationsRemainRegistered(): void {

		$root           = dirname( __DIR__, 2 );
			$migrations = file_get_contents( $root . '/app/Core/Migrations.php' );
		$data           = file_get_contents( $root . '/app/Core/DataMigrations.php' );

		self::assertStringContainsString( '20260902_001_hotel_booking_relation_repair', $migrations );
		self::assertStringContainsString( '20260902_002_known_hotel_user_assignments', $migrations );
		self::assertStringContainsString( 'check@metransfers.es', $data );
		self::assertStringContainsString( 'alenti@metransfers.es', $data );
		self::assertStringContainsString( 'urquinaonaplaza@metransfers.es', $data );
			self::assertStringContainsString( 'HOTEL_ACCESS_ALL', $data );
	}
}
