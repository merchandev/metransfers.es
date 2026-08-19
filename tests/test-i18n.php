<?php

$test_language = 'es';

function mt_lang() {
    global $test_language;
    return $test_language;
}

function home_url( $path = '' ) {
    return 'https://example.test' . $path;
}

require_once __DIR__ . '/../app/Booking/I18n.php';

function assert_i18n( $condition, $message ) {
    if ( ! $condition ) {
        fwrite( STDERR, "FAILED: $message\n" );
        exit( 1 );
    }
}

assert_i18n( 'Origen' === \MeTransfers\Booking\I18n::text( 'origin' ), 'Spanish must remain the default language.' );

$test_language = 'en';
assert_i18n( 'Origin' === \MeTransfers\Booking\I18n::text( 'origin' ), 'English booking strings must not require an external API.' );
assert_i18n( 'Search destinations...' === \MeTransfers\Booking\I18n::text( 'search_destination' ), 'Premium transfer search strings must be localized.' );
assert_i18n(
    'https://example.test/en/pago/' === \MeTransfers\Booking\I18n::url( '/pago/' ),
    'Booking URLs must preserve the active language prefix.'
);

$test_language = 'es';
assert_i18n(
    'https://example.test/pago/' === \MeTransfers\Booking\I18n::url( '/pago/' ),
    'Spanish URLs must retain the canonical unprefixed path.'
);

$test_language = 'zh';
assert_i18n( 'zh-CN' === \MeTransfers\Booking\I18n::maps_language(), 'Google Maps must receive its supported Chinese locale.' );

echo "Booking i18n tests passed.\n";
