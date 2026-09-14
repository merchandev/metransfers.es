<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HotelQrRouteDistanceTest extends TestCase {
	private function book( string $scenario ): array {
		$fixture = dirname( __DIR__ ) . '/fixtures/hotel-fixed-booking.php';
		$output  = array();
		$code    = 0;
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $fixture ) . ' ' . escapeshellarg( $scenario ), $output, $code );
		self::assertSame( 0, $code );
		return json_decode( implode( "\n", $output ), true, 512, JSON_THROW_ON_ERROR );
	}

	public function testMapsOutagePreservesAuthorizedRouteAndFixedFare(): void {
		$result = $this->book( 'outage' );
		self::assertTrue( $result['success'] );
		$booking = $result['saved'][0];
		self::assertSame( 100, $booking['hotel_id'] );
		self::assertSame( 'Hotel QR', $booking['source'] );
		self::assertSame( 'Carrer de Bergara, 4, Barcelona', $booking['origin'] );
		self::assertStringContainsString( '08820 El Prat', $booking['destination'] );
		self::assertSame( 0, $booking['distance_km'] );
		self::assertSame( 9000, $booking['price_cents'] );
		self::assertSame( '9000', $result['data']['ds_merchant_parameters'] );
	}

	public function testAvailableMetricsDoNotChangeFixedFare(): void {
		$result = $this->book( 'metrics' );
		self::assertTrue( $result['success'] );
		self::assertSame( 18.5, $result['saved'][0]['distance_km'] );
		self::assertSame( 30, $result['saved'][0]['duration_minutes'] );
		self::assertSame( 9000, $result['saved'][0]['price_cents'] );
	}

	public function testInboundRouteEndsAtAuthorizedHotel(): void {
		$result = $this->book( 'inbound' );
		self::assertTrue( $result['success'] );
		self::assertSame( 'Carrer de Bergara, 4, Barcelona', $result['saved'][0]['destination'] );
		self::assertStringContainsString( '08820 El Prat', $result['saved'][0]['origin'] );
	}

	public function testInvalidRoutePriceOrCapacityCannotCreateBooking(): void {
		foreach ( array( 'invalid_route', 'price_changed', 'capacity' ) as $scenario ) {
			$result = $this->book( $scenario );
			self::assertFalse( $result['success'], $scenario );
			self::assertSame( array(), $result['saved'], $scenario );
		}
	}
}
