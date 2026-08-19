<?php
namespace MeTransfers\Payments\Redsys;

use MeTransfers\Core\Settings;

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
        return !empty($this->merchant_code) && !empty($this->secret_key);
    }
    
    public function generate_payment_form($booking_id, $amount_cents, $order_id, $customer_name) {
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
        $url_ok = home_url( '/reservas-metransfers/?payment_result=ok&oid=' . $order_id );
        $url_ko = home_url( '/reservas-metransfers/?payment_result=ko&oid=' . $order_id );

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
