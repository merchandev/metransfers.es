<?php

$test_options = array(
    'wptb_redsys_merchant_code' => '999008881',
    'wptb_redsys_secret_key'    => 'test-secret',
    'wptb_redsys_terminal'      => '2',
    'wptb_redsys_currency'      => '978',
    'wptb_redsys_environment'   => 'test',
);

function get_option( $key, $default = false ) {
    global $test_options;
    return array_key_exists( $key, $test_options ) ? $test_options[ $key ] : $default;
}

function absint( $value ) {
    return abs( (int) $value );
}

function home_url( $path = '' ) {
    return 'https://example.test' . $path;
}

function wp_salt( $scheme = 'auth' ) {
    return 'test-auth-salt-' . $scheme;
}

class WPTB_Redsys_API {
    private $parameters = array();

    public function setParameter( $key, $value ) {
        $this->parameters[ $key ] = (string) $value;
    }

    public function createMerchantParameters() {
        return base64_encode( json_encode( $this->parameters ) );
    }

    public function createMerchantSignature( $key ) {
        return hash_hmac( 'sha256', $this->createMerchantParameters(), $key );
    }

    public function createMerchantSignatureNotif( $key, $parameters ) {
        return hash_hmac( 'sha256', $parameters, $key );
    }

    public function decodeMerchantParameters( $parameters ) {
        return base64_decode( $parameters );
    }
}

define( 'WPTB_PLUGIN_DIR', __DIR__ . '/../app/Legacy/WPTB/' );

require_once __DIR__ . '/../app/Core/Settings.php';
require_once __DIR__ . '/../app/Payments/Redsys/Gateway.php';

$gateway = new \MeTransfers\Payments\Redsys\Gateway();
$form = $gateway->generate_payment_form( 123, 5025, '000000000123', 'Cliente Test' );

if ( 'https://sis-t.redsys.es:25443/sis/realizarPago' !== $form['url'] ) {
    fwrite( STDERR, "FAILED: test environment must use the Redsys test endpoint.\n" );
    exit( 1 );
}

$created_parameters = json_decode( base64_decode( $form['params'] ), true );
if ( '5025' !== $created_parameters['DS_MERCHANT_AMOUNT'] || '999008881' !== $created_parameters['DS_MERCHANT_MERCHANTCODE'] ) {
    fwrite( STDERR, "FAILED: generated merchant parameters are incorrect.\n" );
    exit( 1 );
}

$english_form = $gateway->generate_payment_form( 123, 5025, '000000000123', 'Test Customer', 'en' );
$english_parameters = json_decode( base64_decode( $english_form['params'] ), true );
if ( 0 !== strpos( $english_parameters['DS_MERCHANT_URLOK'], 'https://example.test/en/reservas-metransfers/?payment_result=ok&oid=000000000123&token=' ) ) {
    fwrite( STDERR, "FAILED: translated checkout must return to the matching language URL.\n" );
    exit( 1 );
}

$confirmation_token = \MeTransfers\Payments\Redsys\Gateway::confirmation_token( '000000000123' );
if ( ! \MeTransfers\Payments\Redsys\Gateway::verify_confirmation_token( '000000000123', $confirmation_token )
    || \MeTransfers\Payments\Redsys\Gateway::verify_confirmation_token( '000000000124', $confirmation_token )
    || \MeTransfers\Payments\Redsys\Gateway::verify_confirmation_token( '', $confirmation_token ) ) {
    fwrite( STDERR, "FAILED: confirmation tokens must be bound to one order.\n" );
    exit( 1 );
}

$notification_parameters = base64_encode( json_encode( array(
    'Ds_Order'    => '000000000123',
    'Ds_Response' => '0000',
    'Ds_Amount'   => '5025',
    'Ds_MerchantCode' => '999008881',
    'Ds_Terminal' => '2',
    'Ds_Currency' => '978',
) ) );
$notification_signature = hash_hmac( 'sha256', $notification_parameters, 'test-secret' );
$notification = $gateway->verify_notification( $notification_parameters, $notification_signature, 'HMAC_SHA256_V1' );

if ( empty( $notification['valid'] ) || empty( $notification['authorized'] ) ) {
    fwrite( STDERR, "FAILED: a valid authorized notification was rejected.\n" );
    exit( 1 );
}

$invalid = $gateway->verify_notification( $notification_parameters, 'invalid', 'HMAC_SHA256_V1' );
if ( ! empty( $invalid['valid'] ) ) {
    fwrite( STDERR, "FAILED: an invalid notification signature was accepted.\n" );
    exit( 1 );
}

$denied_parameters = base64_encode( json_encode( array(
    'Ds_Order'    => '000000000123',
    'Ds_Response' => '0101',
    'Ds_Amount'   => '5025',
    'Ds_MerchantCode' => '999008881',
    'Ds_Terminal' => '2',
    'Ds_Currency' => '978',
) ) );
$denied_signature = hash_hmac( 'sha256', $denied_parameters, 'test-secret' );
$denied = $gateway->verify_notification( $denied_parameters, $denied_signature, 'HMAC_SHA256_V1' );
if ( empty( $denied['valid'] ) || ! empty( $denied['authorized'] ) ) {
    fwrite( STDERR, "FAILED: a denied Redsys response was treated as authorized.\n" );
    exit( 1 );
}

$test_options['wptb_redsys_environment'] = 'live';
$live_gateway = new \MeTransfers\Payments\Redsys\Gateway();
$live_form = $live_gateway->generate_payment_form( 124, 100, '000000000124', 'Cliente Test' );
if ( 'https://sis.redsys.es/sis/realizarPago' !== $live_form['url'] ) {
    fwrite( STDERR, "FAILED: live environment must use the Redsys production endpoint.\n" );
    exit( 1 );
}

echo "Redsys gateway tests passed.\n";
