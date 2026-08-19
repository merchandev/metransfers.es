<?php
namespace MeTransfers\Booking;

final class ReceiptController {
    public function register() {
        add_action( 'template_redirect', array( $this, 'dispatch' ), 0 );
    }

    public function dispatch() {
        if ( ! isset( $_GET['mt_receipt'] ) ) {
            return;
        }
        $receipt_flag = wp_unslash( $_GET['mt_receipt'] );
        if ( ! is_scalar( $receipt_flag ) || '1' !== (string) $receipt_flag ) {
            return;
        }

        $raw_order_id = isset( $_GET['oid'] ) ? wp_unslash( $_GET['oid'] ) : '';
        $token = isset( $_GET['token'] ) ? wp_unslash( $_GET['token'] ) : '';
        $receipt = ( new ReceiptService() )->find( $raw_order_id, $token );
        $locale = $receipt ? $receipt['locale'] : I18n::language();
        $strings = I18n::strings( $locale );

        status_header( $receipt ? 200 : 404 );
        nocache_headers();
        if ( ! headers_sent() ) {
            header( 'Content-Type: text/html; charset=UTF-8' );
            header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
            header( 'Referrer-Policy: no-referrer', true );
            header( 'X-Content-Type-Options: nosniff', true );
            header( "Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self' data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'", true );
        }

        require MT_WPTB_DIR . 'templates/receipt.php';
        exit;
    }
}
