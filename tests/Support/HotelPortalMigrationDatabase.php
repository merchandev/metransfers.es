<?php

declare(strict_types=1);

namespace MeTransfers\Tests\Support;

final class HotelPortalMigrationDatabase {
	public string $prefix = 'wp_';
	public array $rows;

	public function __construct( array $rows ) {
		$this->rows = $rows;
	}

	public function prepare( string $query, ...$args ): string {
		return '__hotel_backfill__' . json_encode( $args );
	}

	public function query( string $query ) {
		if ( 0 !== strpos( $query, '__hotel_backfill__' ) ) {
			return false;
		}

		$args     = json_decode( substr( $query, strlen( '__hotel_backfill__' ) ), true );
		$hotel_id = (int) $args[1];
		$token    = (string) $args[2];
		$updated  = 0;
		foreach ( $this->rows as &$row ) {
			if ( null !== $row['hotel_id'] || $token !== $row['hotel_token'] ) {
				continue;
			}
			$row['hotel_id'] = $hotel_id;
			if ( null === $row['source'] || '' === $row['source'] || 'Metransfers' === $row['source'] ) {
				$row['source'] = 'Hotel QR';
			}
			++$updated;
		}
		unset( $row );

		return $updated;
	}
}
