<?php

namespace MeTransfers\Pricing;

final class Money {
	private int $cents;

	public function __construct( int $cents ) {
		if ( $cents < 0 ) {
			throw new \InvalidArgumentException( 'Negative money.' );
		}
		$this->cents = $cents;
	}

	public static function fromDecimal( $amount ): self {
		if ( is_float( $amount ) ) {
			$amount = rtrim( rtrim( sprintf( '%.8F', $amount ), '0' ), '.' );
		} elseif ( is_int( $amount ) ) {
			$amount = (string) $amount;
		} elseif ( is_string( $amount ) ) {
			$amount = trim( str_replace( ',', '.', $amount ) );
		} else {
			throw new \InvalidArgumentException( 'Invalid money.' );
		}

		if ( 1 !== preg_match( '/\A(\d+)(?:\.(\d+))?\z/', $amount, $matches ) ) {
			throw new \InvalidArgumentException( 'Invalid money.' );
		}

		$whole    = (int) $matches[1];
		$fraction = isset( $matches[2] ) ? $matches[2] : '';
		$rounded  = str_pad( substr( $fraction, 0, 3 ), 3, '0' );
		$cents    = ( $whole * 100 ) + (int) substr( $rounded, 0, 2 );
		if ( (int) $rounded[2] >= 5 ) {
			++$cents;
		}

		return new self( $cents );
	}

	public static function fromBooking( $booking ): self {
		if ( is_object( $booking ) && isset( $booking->price_cents ) && '' !== $booking->price_cents ) {
			return new self( (int) $booking->price_cents );
		}
		if ( is_array( $booking ) && array_key_exists( 'price_cents', $booking ) && null !== $booking['price_cents'] && '' !== $booking['price_cents'] ) {
			return new self( (int) $booking['price_cents'] );
		}

		$legacy = is_object( $booking ) && isset( $booking->price )
			? $booking->price
			: ( is_array( $booking ) && isset( $booking['price'] ) ? $booking['price'] : 0 );
		return self::fromDecimal( is_scalar( $legacy ) ? $legacy : 0 );
	}

	public function cents(): int {
		return $this->cents;
	}

	public function decimal(): string {
		return intdiv( $this->cents, 100 ) . '.' . str_pad( (string) ( $this->cents % 100 ), 2, '0', STR_PAD_LEFT );
	}

	public function decimalFloat(): float {
		return (float) $this->decimal();
	}
}
