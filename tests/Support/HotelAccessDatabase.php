<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Support;

final class HotelAccessDatabase {
	public string $prefix = 'wp_';
	private array $bookings;

	public function __construct( array $bookings ) {
		$this->bookings = $bookings;
	}

	public function prepare( string $query, ...$args ): string {
		return '__booking__' . (int) $args[0];
	}

	public function get_var( string $query ) {
		$booking_id = (int) substr( $query, strlen( '__booking__' ) );
		return $this->bookings[ $booking_id ] ?? null;
	}
}
