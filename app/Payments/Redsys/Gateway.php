<?php
namespace MeTransfers\Payments\Redsys;

class Gateway {
    private $merchant_code;
    private $secret_key;
    private $terminal;

    public function __construct() {
        $this->merchant_code = defined('MT_REDSYS_MERCHANT_CODE') ? MT_REDSYS_MERCHANT_CODE : get_option('wptb_redsys_merchant_code');
        $this->secret_key = defined('MT_REDSYS_SECRET_KEY') ? MT_REDSYS_SECRET_KEY : get_option('wptb_redsys_secret_key');
        $this->terminal = defined('MT_REDSYS_TERMINAL') ? MT_REDSYS_TERMINAL : '1';
    }

    public function is_configured() {
        return !empty($this->merchant_code) && !empty($this->secret_key);
    }
    
    public function generate_payment_form($booking_id, $amount_cents, $order_id, $customer_name) {
        if (!$this->is_configured()) {
            throw new \Exception('Redsys is not properly configured.');
        }

        // Load legacy API for now, but wrapped in modern class
        if ( ! class_exists( 'WPTB_Redsys_API' ) ) {
            require_once WPTB_PLUGIN_DIR . 'includes/class-wptb-redsys.php';
        }

        $redsys = new \WPTB_Redsys_API();
        
        $url_notification = home_url( '/?wptb_redsys_ipn=1' );
        $url_ok = home_url( '/reservas-metransfers/?payment_result=ok&oid=' . $order_id );
        $url_ko = home_url( '/reservas-metransfers/?payment_result=ko&oid=' . $order_id );

        $redsys->setParameter("DS_MERCHANT_AMOUNT", $amount_cents);
        $redsys->setParameter("DS_MERCHANT_ORDER", $order_id);
        $redsys->setParameter("DS_MERCHANT_MERCHANTCODE", $this->merchant_code);
        $redsys->setParameter("DS_MERCHANT_CURRENCY", '978');
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
            'url' => 'https://sis.redsys.es/sis/realizarPago',
            'params' => $params,
            'signature' => $signature,
            'version' => 'HMAC_SHA256_V1'
        ];
    }
}