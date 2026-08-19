<?php

function assert_money( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

require_once __DIR__ . '/../app/Pricing/Money.php';

use MeTransfers\Pricing\Money;

assert_money( 1001 === Money::fromDecimal( '10.01' )->cents(), 'Decimal strings must convert to exact cents.' );
assert_money( 10 === Money::fromDecimal( '0,10' )->cents(), 'Decimal commas must be normalized.' );
assert_money( 2000 === Money::fromDecimal( '19.995' )->cents(), 'Sub-cent values must round half up once.' );
assert_money( '123.45' === ( new Money( 12345 ) )->decimal(), 'Cents must format without float division.' );

$negative_rejected = false;
try {
    new Money( -1 );
} catch ( InvalidArgumentException $exception ) {
    $negative_rejected = true;
}
assert_money( $negative_rejected, 'Negative money must be rejected.' );

$negative_booking_rejected = false;
try {
    Money::fromBooking( array( 'price_cents' => -1, 'price' => '25.00' ) );
} catch ( InvalidArgumentException $exception ) {
    $negative_booking_rejected = true;
}
assert_money( $negative_booking_rejected, 'Negative stored cents must not be coerced to a free booking.' );

$new_booking = (object) array( 'price' => '999.99', 'price_cents' => 12345 );
$legacy_booking = (object) array( 'price' => '123.45', 'price_cents' => null );
assert_money( 12345 === Money::fromBooking( $new_booking )->cents(), 'price_cents must win over the compatibility decimal.' );
assert_money( 12345 === Money::fromBooking( $legacy_booking )->cents(), 'Legacy rows must remain readable during migration.' );

$root = dirname( __DIR__ );
$schema = file_get_contents( $root . '/app/Core/Schema.php' );
$data_migrations = file_get_contents( $root . '/app/Core/DataMigrations.php' );
assert_money(
    false !== strpos( $schema, 'price_cents bigint(20) unsigned DEFAULT NULL' )
        && false !== strpos( $data_migrations, 'WHERE price_cents IS NULL' )
        && false !== strpos( $data_migrations, 'ROUND(price * 100)' ),
    'The additive schema and idempotent DECIMAL backfill must be migration-managed.'
);

$public = file_get_contents( $root . '/app/Legacy/WPTB/includes/class-wptb-public.php' );
$hotel = file_get_contents( $root . '/app/Legacy/Hotel/public/class-hqp-public.php' );
$analytics = file_get_contents( $root . '/app/Analytics/PurchaseOutbox.php' );
assert_money( false === strpos( $public, "round( (float) \$booking->price * 100 )" ), 'Redsys web edges must not convert booking floats.' );
assert_money( false === strpos( $hotel, 'round( $price * 100 )' ), 'Hotel Redsys must receive stored integer cents.' );
assert_money(
    substr_count( $public, "'price_cents'" ) >= 4 && false !== strpos( $hotel, "'price_cents'" ),
    'Web, WooCommerce and Hotel booking writes must persist both money representations.'
);
assert_money(
    false !== strpos( $public, 'Money::fromBooking( $booking )->cents()' ),
    'Payment form and IPN verification must prefer integer cents.'
);
assert_money(
    false !== strpos( $analytics, 'Money::fromBooking' )
        && false !== strpos( $analytics, 'decimalFloat()' ),
    'Analytics must convert cents to decimal only at its external edge.'
);

echo "Money cents compatibility tests passed.\n";
