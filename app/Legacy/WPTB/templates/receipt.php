<?php
/**
 * Standalone, server-authoritative and printable booking receipt.
 */
$receipt_title = $strings['receipt_title'];
$receipt_home = home_url( 'es' === $locale ? '/' : '/' . $locale . '/' );
$receipt_css = get_template_directory_uri() . '/assets/css/receipt.css';
$receipt_js = get_template_directory_uri() . '/assets/js/receipt.js';
?><!doctype html>
<html lang="<?php echo esc_attr( $locale ); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?php echo esc_html( $receipt_title ); ?> | MeTransfers</title>
    <link rel="stylesheet" href="<?php echo esc_url( $receipt_css ); ?>">
    <script src="<?php echo esc_url( $receipt_js ); ?>" defer></script>
</head>
<body>
<?php if ( ! $receipt ) : ?>
    <main class="mt-receipt mt-receipt--unavailable">
        <p class="mt-receipt__brand">MeTransfers</p>
        <h1><?php echo esc_html( $strings['receipt_unavailable'] ); ?></h1>
        <a class="mt-receipt__button" href="<?php echo esc_url( $receipt_home ); ?>"><?php echo esc_html( $strings['back_home'] ); ?></a>
    </main>
<?php else : ?>
    <main class="mt-receipt">
        <header class="mt-receipt__header">
            <p class="mt-receipt__brand">MeTransfers</p>
            <h1><?php echo esc_html( $receipt_title ); ?></h1>
            <p><?php echo esc_html( $strings['reference'] ); ?>: <strong>#<?php echo esc_html( $receipt['reference'] ); ?></strong></p>
        </header>

        <section class="mt-receipt__section" aria-label="<?php echo esc_attr( $strings['booking_details'] ); ?>">
            <dl class="mt-receipt__details">
                <div><dt><?php echo esc_html( $strings['full_name'] ); ?></dt><dd><?php echo esc_html( $receipt['customer_name'] ); ?></dd></div>
                <div><dt><?php echo esc_html( $strings['date'] ); ?> / <?php echo esc_html( $strings['time'] ); ?></dt><dd><?php echo esc_html( $receipt['booking_date'] . ' ' . $receipt['booking_time'] ); ?></dd></div>
                <div><dt><?php echo esc_html( $strings['trip_type'] ); ?></dt><dd><?php echo esc_html( $strings[ $receipt['trip_type'] ] ); ?></dd></div>
                <div><dt><?php echo esc_html( $strings['vehicle'] ); ?></dt><dd><?php echo esc_html( $receipt['vehicle_name'] ); ?></dd></div>
                <div><dt><?php echo esc_html( $strings['origin'] ); ?></dt><dd><?php echo esc_html( $receipt['origin'] ); ?></dd></div>
                <div><dt><?php echo esc_html( $strings['destination'] ); ?></dt><dd><?php echo esc_html( $receipt['destination'] ); ?></dd></div>
                <?php if ( 'round_trip' === $receipt['trip_type'] ) : ?>
                    <div><dt><?php echo esc_html( $strings['return_date'] ); ?> / <?php echo esc_html( $strings['return_time'] ); ?></dt><dd><?php echo esc_html( trim( $receipt['return_date'] . ' ' . $receipt['return_time'] ) ); ?></dd></div>
                    <div><dt><?php echo esc_html( $strings['return_pickup'] ); ?></dt><dd><?php echo esc_html( $receipt['return_origin'] ); ?></dd></div>
                    <div><dt><?php echo esc_html( $strings['return_destination'] ); ?></dt><dd><?php echo esc_html( $receipt['return_destination'] ); ?></dd></div>
                <?php endif; ?>
                <div><dt><?php echo esc_html( $strings['passengers'] ); ?></dt><dd><?php echo esc_html( (string) $receipt['passengers'] ); ?></dd></div>
                <div><dt><?php echo esc_html( $strings['luggage'] ); ?></dt><dd><?php echo esc_html( $receipt['suitcases'] . ' + ' . $receipt['carry_ons'] ); ?></dd></div>
                <?php if ( $receipt['distance_km'] > 0 ) : ?>
                    <div><dt><?php echo esc_html( $strings['distance'] ); ?></dt><dd><?php echo esc_html( number_format_i18n( $receipt['distance_km'], 1 ) ); ?> km</dd></div>
                <?php endif; ?>
                <div class="mt-receipt__total"><dt><?php echo esc_html( $strings['total_paid'] ); ?></dt><dd><?php echo esc_html( $receipt['currency'] . ' ' . $receipt['price'] ); ?></dd></div>
            </dl>
        </section>

        <p class="mt-receipt__confirmed"><?php echo esc_html( $strings['payment_received'] ); ?></p>
        <div class="mt-receipt__actions">
            <button type="button" class="mt-receipt__button" data-print-receipt><?php echo esc_html( $strings['print_receipt'] ); ?></button>
            <a class="mt-receipt__button mt-receipt__button--secondary" href="<?php echo esc_url( $receipt_home ); ?>"><?php echo esc_html( $strings['back_home'] ); ?></a>
        </div>
    </main>
<?php endif; ?>
</body>
</html>
