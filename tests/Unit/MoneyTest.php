<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Unit;

use InvalidArgumentException;
use MeTransfers\Pricing\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase {
	#[DataProvider( 'decimalCases' )]
	public function testDecimalAmountsUseIntegerCents( int|float|string $amount, int $expected ): void {
		self::assertSame( $expected, Money::fromDecimal( $amount )->cents() );
	}

	public static function decimalCases(): array {
		return array(
			'integer'       => array( 19, 1900 ),
			'decimal text'  => array( '19.95', 1995 ),
			'comma decimal' => array( '19,95', 1995 ),
			'round up'      => array( '19.995', 2000 ),
			'round down'    => array( '19.994', 1999 ),
			'float edge'    => array( 0.1 + 0.2, 30 ),
		);
	}

	public function testBookingCentsAreAuthoritative(): void {
		$booking = (object) array(
			'price_cents' => 12345,
			'price'       => '999.99',
		);

		self::assertSame( 12345, Money::fromBooking( $booking )->cents() );
		self::assertSame( '123.45', Money::fromBooking( $booking )->decimal() );
	}

	public function testNegativeMoneyIsRejected(): void {
		$this->expectException( InvalidArgumentException::class );
		new Money( -1 );
	}
}
