<?php
/** Shared layout for the Sants and hotel service pages. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="primary" class="site-main mt-transfer-hub">
    <section class="section container" style="padding: 140px 24px 48px; max-width: 1040px; margin: auto;">
        <h1><?php echo esc_html( mt_translate( $args['title'] ) ); ?></h1>
        <p class="lead"><?php echo esc_html( mt_translate( $args['intro'] ) ); ?></p>
        <p><a class="btn btn-solid" href="#reservar"><?php echo esc_html( mt_translate( 'Consultar mi traslado' ) ); ?></a></p>
        <?php foreach ( $args['sections'] as $heading => $paragraph ) : ?>
            <section style="margin-top: 32px;">
                <h2><?php echo esc_html( mt_translate( $heading ) ); ?></h2>
                <p><?php echo esc_html( mt_translate( $paragraph ) ); ?></p>
            </section>
        <?php endforeach; ?>
        <section id="reservar" style="margin-top: 40px; scroll-margin-top: 100px;">
            <h2><?php echo esc_html( mt_translate( 'Prepara tu reserva' ) ); ?></h2>
            <?php if ( shortcode_exists( 'wptb_booking_form' ) ) : ?>
                <?php echo do_shortcode( '[wptb_booking_form]' ); ?>
            <?php endif; ?>
            <p><a href="<?php echo esc_url( mt_localized_url( 'contacto' ) ); ?>"><?php echo esc_html( mt_translate( 'Contactar para confirmar disponibilidad y presupuesto' ) ); ?></a></p>
        </section>
        <section style="margin-top: 40px;">
            <h2><?php echo esc_html( mt_translate( 'Preguntas frecuentes' ) ); ?></h2>
            <?php foreach ( $args['faq'] as $question => $answer ) : ?>
                <details style="padding: 16px 0; border-bottom: 1px solid #ddd;">
                    <summary><?php echo esc_html( mt_translate( $question ) ); ?></summary>
                    <p><?php echo esc_html( mt_translate( $answer ) ); ?></p>
                </details>
            <?php endforeach; ?>
        </section>
        <nav aria-label="<?php echo esc_attr( mt_translate( 'Servicios relacionados' ) ); ?>" style="margin-top: 32px;">
            <a href="<?php echo esc_url( mt_localized_url( 'traslados-privados' ) ); ?>"><?php echo esc_html( mt_translate( 'Traslados desde Barcelona a toda España' ) ); ?></a>
            · <a href="<?php echo esc_url( mt_localized_url( 'rutas' ) ); ?>"><?php echo esc_html( mt_translate( 'Ver rutas' ) ); ?></a>
        </nav>
    </section>
</main>
<?php get_footer(); ?>
