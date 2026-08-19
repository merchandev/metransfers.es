<?php
namespace MeTransfers\Payments\Redsys;

use MeTransfers\Core\Settings;
use MeTransfers\Core\ReleaseGate;

class Gateway {
    private $merchant_code;
    private $secret_key;
    private $terminal;
    private $currency;
    private $environment;

    public function __construct() {
        $this->merchant_code = (string) Settings::get( 'redsys_merchant_code', '' );
        $this->secret_key = (string) Settings::get( 'redsys_secret', '' );
        $this->terminal = (string) Settings::get( 'redsys_terminal', '1' );
        $this->currency = (string) Settings::get( 'redsys_currency', '978' );
        $this->environment = strtolower( (string) Settings::get( 'redsys_environment', 'test' ) );
    }

    public function is_configured() {
        $credentials = ! empty( $this->merchant_code ) && ! empty( $this->secret_key );
        if ( ! $credentials ) {
            return false;
        }
        if ( in_array( $this->environment, array( 'live', 'production' ), true ) ) {
            return ReleaseGate::redsysLiveReady();
        }
        return true;
    }
    
    public function generate_payment_form($booking_id, $amount_cents, $order_id, $customer_name, $language = 'es') {
        if (!$this->is_configured()) {
            throw new \Exception('Redsys is not properly configured.');
        }

        $amount_cents = absint( $amount_cents );
        $order_id = preg_replace( '/[^0-9A-Za-z]/', '', (string) $order_id );
        if ( $amount_cents <= 0 || '' === $order_id ) {
            throw new \InvalidArgumentException( 'Invalid Redsys amount or order identifier.' );
        }

        $redsys = $this->new_api();
        
        $url_notification = home_url( '/?wptb_redsys_ipn=1' );
        $url_ok = self::confirmation_url( $order_id, 'ok', $language );
        $url_ko = self::confirmation_url( $order_id, 'ko', $language );

        $redsys->setParameter("DS_MERCHANT_AMOUNT", $amount_cents);
        $redsys->setParameter("DS_MERCHANT_ORDER", $order_id);
        $redsys->setParameter("DS_MERCHANT_MERCHANTCODE", $this->merchant_code);
        $redsys->setParameter("DS_MERCHANT_CURRENCY", $this->currency);
        $redsys->setParameter("DS_MERCHANT_TRANSACTIONTYPE", '0');
        $redsys->setParameter("DS_MERCHANT_TERMINAL", $this->terminal);
        $redsys->setParameter("DS_MERCHANT_MERCHANTURL", $url_notification);
        $redsys->setParameter("DS_MERCHANT_URLOK", $url_ok);
        $redsys->setParameter("DS_MERCHANT_URLKO", $url_ko);
        $redsys->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION", "Reserva #" . $booking_id);
        $redsys->setParameter("DS_MERCHANT_TITULAR", substr($customer_name, 0, 60));

        $params = $redsys->createMerchantParameters();
        $signature = $redsys->createMerchantSignature($this->secret_key);

        return [
            'url' => $this->payment_url(),
            'params' => $params,
            'signature' => $signature,
            'version' => 'HMAC_SHA256_V1'
        ];
    }

    public function verify_notification( $merchant_parameters, $received_signature, $signature_version ) {
        if ( ! $this->is_configured() ) {
            throw new \RuntimeException( 'Redsys is not properly configured.' );
        }

        if ( 'HMAC_SHA256_V1' !== (string) $signature_version ) {
            return array( 'valid' => false, 'authorized' => false );
        }

        $redsys = $this->new_api();
        $calculated_signature = $redsys->createMerchantSignatureNotif( $this->secret_key, $merchant_parameters );
        if ( ! is_string( $received_signature ) || ! hash_equals( $calculated_signature, $received_signature ) ) {
            return array( 'valid' => false, 'authorized' => false );
        }

        $decoded_json = $redsys->decodeMerchantParameters( $merchant_parameters );
        $parameters = json_decode( $decoded_json, true );
        if ( ! is_array( $parameters ) ) {
            return array( 'valid' => false, 'authorized' => false );
        }

        $normalized = array_change_key_case( $parameters, CASE_LOWER );
        $merchant_code = isset( $normalized['ds_merchantcode'] ) ? (string) $normalized['ds_merchantcode'] : '';
        $terminal = isset( $normalized['ds_terminal'] ) ? (string) $normalized['ds_terminal'] : '';
        $currency = isset( $normalized['ds_currency'] ) ? (string) $normalized['ds_currency'] : '';
        if ( ! hash_equals( $this->merchant_code, $merchant_code )
            || '' === $terminal || (int) $this->terminal !== (int) $terminal
            || '' === $currency || (int) $this->currency !== (int) $currency ) {
            return array( 'valid' => false, 'authorized' => false );
        }

        $response_raw = isset( $normalized['ds_response'] ) ? (string) $normalized['ds_response'] : '';
        $response = ctype_digit( $response_raw ) ? (int) $response_raw : 9999;
        $order_id = isset( $normalized['ds_order'] ) ? (string) $normalized['ds_order'] : '';

        return array(
            'valid'      => '' !== $order_id,
            'authorized' => '' !== $order_id && $response >= 0 && $response <= 99,
            'order_id'   => $order_id,
            'response'   => $response,
            'parameters' => $parameters,
        );
    }

    public static function confirmation_url( $order_id, $result = 'ok', $language = 'es' ) {
        $order_id = preg_replace( '/[^0-9A-Za-z]/', '', (string) $order_id );
        if ( '' === $order_id ) {
            throw new \InvalidArgumentException( 'Invalid confirmation order identifier.' );
        }

        $result = 'ko' === $result ? 'ko' : 'ok';
        $language = strtolower( preg_replace( '/[^a-z-]/i', '', (string) $language ) );
        if ( ! preg_match( '/^[a-z]{2}$/', $language ) ) {
            $language = 'es';
        }

        $language_prefix = 'es' !== $language ? $language . '/' : '';
        $return_path = '/' . $language_prefix . 'reservas-metransfers/';
        return home_url(
            $return_path
            . '?payment_result=' . $result
            . '&oid=' . $order_id
            . '&token=' . self::confirmation_token( $order_id )
        );
    }

    public static function confirmation_token( $order_id ) {
        $order_id = preg_replace( '/[^0-9A-Za-z]/', '', (string) $order_id );
        if ( '' === $order_id ) {
            throw new \InvalidArgumentException( 'Invalid confirmation order identifier.' );
        }
        return hash_hmac( 'sha256', $order_id, wp_salt( 'auth' ) );
    }

    public static function verify_confirmation_token( $order_id, $token ) {
        $order_id = preg_replace( '/[^0-9A-Za-z]/', '', (string) $order_id );
        $token = (string) $token;
        return '' !== $order_id
            && 64 === strlen( $token )
            && hash_equals( self::confirmation_token( $order_id ), $token );
    }

    /**
     * Validate both successful and failed browser returns before rendering them.
     * This does not mutate payment state; the database/IPN remains authoritative.
     */
    public static function validate_confirmation_request( $result, $raw_order_id, $token ) {
        if ( ! is_scalar( $result ) || ! is_scalar( $raw_order_id ) || ! is_scalar( $token ) ) {
            return array(
                'valid'    => false,
                'result'   => '',
                'order_id' => '',
            );
        }

        $result = strtolower( (string) $result );
        $raw_order_id = (string) $raw_order_id;
        $order_id = preg_replace( '/[^0-9A-Za-z]/', '', $raw_order_id );

        if ( ! in_array( $result, array( 'ok', 'ko' ), true )
            || '' === $order_id
            || $raw_order_id !== $order_id
            || ! self::verify_confirmation_token( $order_id, $token ) ) {
            return array(
                'valid'    => false,
                'result'   => '',
                'order_id' => '',
            );
        }

        return array(
            'valid'    => true,
            'result'   => $result,
            'order_id' => $order_id,
        );
    }

    private function new_api() {
        if ( ! class_exists( 'WPTB_Redsys_API' ) ) {
            require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-redsys.php';
        }

        return new \WPTB_Redsys_API();
    }

    private function payment_url() {
        if ( in_array( $this->environment, array( 'live', 'production' ), true ) ) {
            return 'https://sis.redsys.es/sis/realizarPago';
        }

        return 'https://sis-t.redsys.es:25443/sis/realizarPago';
    }
}
