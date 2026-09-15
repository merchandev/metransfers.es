<?php
namespace MeTransfers\HotelPortal\Services;

use MeTransfers\Pricing\Money;

/**
 * Single source of truth for the fixed-price hotel fleet.
 *
 * The hotel admin and the public QR flow must resolve vehicles through the
 * same rules. A vehicle is offerable when it is active, can carry the group
 * and the hotel has a positive fixed price for it.
 */
final class HotelFixedPricing {

	/**
	 * Return every active vehicle in the same order used by the fleet admin.
	 *
	 * Do not depend on vehicle-type tables here: legacy installations can have
	 * valid active vehicles before the type migration has completed.
	 *
	 * @return array<int,object>
	 */
	public static function activeVehicles(): array {
		global $wpdb;

		$vehicles_table = $wpdb->prefix . 'wptb_vehicles';
		$vehicles       = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE is_active = 1 ORDER BY display_order ASC, name ASC', $vehicles_table ) );

		if ( ! is_array( $vehicles ) ) {
			error_log( 'MeTransfers HotelFixedPricing: unable to read active vehicles. ' . (string) $wpdb->last_error );
			return array();
		}

		return $vehicles;
	}

	/** Find one active vehicle. */
	public static function activeVehicle( int $vehicle_id ) {
		if ( $vehicle_id <= 0 ) {
			return null;
		}

		foreach ( self::activeVehicles() as $vehicle ) {
			if ( (int) $vehicle->id === $vehicle_id ) {
				return $vehicle;
			}
		}

		return null;
	}

	/** Fixed price after the hotel's discount, or null when not offerable. */
	public static function priceForVehicle( int $hotel_id, int $vehicle_id ): ?Money {
		if ( $hotel_id <= 0 || $vehicle_id <= 0 ) {
			return null;
		}

		$vehicle = self::activeVehicle( $vehicle_id );
		return $vehicle ? self::priceForActiveVehicle( $hotel_id, $vehicle ) : null;
	}

	/**
	 * Vehicles that the current hotel can actually sell to this group.
	 *
	 * There is intentionally no sedan/van pre-filter. The hotel's configured
	 * per-vehicle price and passenger capacity determine availability, so an
	 * active V-Class with a hotel price cannot disappear because of a UI radio.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function availableVehicles( int $hotel_id, int $passengers = 1 ): array {
		$passengers = max( 1, $passengers );
		$available  = array();

		foreach ( self::activeVehicles() as $vehicle ) {
			$capacity = max( 1, (int) $vehicle->capacity );
			if ( $capacity < $passengers ) {
				continue;
			}

			$price = self::priceForActiveVehicle( $hotel_id, $vehicle );
			if ( ! $price ) {
				continue;
			}

			$available[] = array(
				'id'          => (int) $vehicle->id,
				'name'        => (string) $vehicle->name,
				'description' => isset( $vehicle->description ) ? wp_strip_all_tags( (string) $vehicle->description ) : '',
				'capacity'    => $capacity,
				'price'       => $price->decimal(),
				'price_cents' => $price->cents(),
			);
		}

		return $available;
	}

	/** Resolve one already-validated active vehicle without repeating the fleet query. */
	private static function priceForActiveVehicle( int $hotel_id, object $vehicle ): ?Money {
		if ( $hotel_id <= 0 || empty( $vehicle->id ) ) {
			return null;
		}

		$vehicle_id   = (int) $vehicle->id;
		$specific_key = '_hqp_price_vehicle_' . $vehicle_id;
		$has_specific = metadata_exists( 'post', $hotel_id, $specific_key );
		$raw_price    = get_post_meta( $hotel_id, $specific_key, true );

		// Compatibility with hotels created before per-vehicle prices existed.
		// An explicitly stored blank/zero per-vehicle price still means disabled.
		if ( ! $has_specific ) {
			$legacy_key = self::legacyPriceMetaKey( $vehicle );
			$raw_price  = get_post_meta( $hotel_id, $legacy_key, true );
		}

		if ( ! is_scalar( $raw_price ) || '' === trim( (string) $raw_price ) ) {
			return null;
		}

		try {
			$money = Money::fromDecimal( (string) $raw_price );
		} catch ( \InvalidArgumentException $exception ) {
			error_log( sprintf( 'MeTransfers HotelFixedPricing: invalid price for hotel %d / vehicle %d.', $hotel_id, $vehicle_id ) );
			return null;
		}

		if ( $money->cents() <= 0 ) {
			return null;
		}

		$discount = (int) get_post_meta( $hotel_id, '_hqp_discount_percent', true );
		if ( $discount > 0 && $discount <= 100 ) {
			$discounted = intdiv( ( $money->cents() * ( 100 - $discount ) ) + 50, 100 );
			$money      = new Money( max( 0, $discounted ) );
		}

		return $money->cents() > 0 ? $money : null;
	}

	/** Legacy hotel pricing used only when a per-vehicle meta key never existed. */
	private static function legacyPriceMetaKey( object $vehicle ): string {
		// Capacity is the only invariant available on every historical schema.
		return (int) $vehicle->capacity > 4 ? '_hqp_price_van' : '_hqp_price_sedan';
	}
}
