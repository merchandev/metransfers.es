<?php
/**
 * Template Name: Reservaciones
 * 
 * Plantilla para la página dedicada de reservas (para campañas de Google Ads).
 */

get_header(); ?>

<main class="site-main" style="padding-top: 100px; background-color: var(--bg-secondary);">
    <!-- Hero / Título de la página -->
    <header class="page-header" style="background-color: var(--bg-dark); color: white; padding: 60px 0; text-align: center;">
        <div class="wrap">
            <h1 class="page-title" style="margin: 0; font-size: clamp(2rem, 5vw, 3rem);"><?php echo mt_translate( 'Reservas Online', 'Online Reservations' ); ?></h1>
            <p style="margin-top: 15px; font-size: 1.1rem; color: rgba(255,255,255,0.8); max-width: 600px; margin-left: auto; margin-right: auto;">
                <?php echo mt_translate( 'Calcula tu traslado y reserva en menos de 2 minutos.', 'Calculate your transfer and book in less than 2 minutes.' ); ?>
            </p>
        </div>
    </header>

    <!-- Sección del Formulario de Reservas -->
    <section class="sp" style="padding: 60px 0 100px 0;">
        <div class="wrap" style="max-width: 800px; margin: 0 auto;">
            
            <div class="hero__panel" style="margin: 0;">
                <h2 style="text-align: center; margin-bottom: 30px; font-size: 24px;"><?php echo mt_translate('Calcula tu traslado', 'Calculate your transfer'); ?></h2>
                
                <?php if ( shortcode_exists( 'wptb_booking_form' ) ) : ?>
                    <div class="booking-form-wrapper">
                        <?php echo do_shortcode( '[wptb_booking_form]' ); ?>
                    </div>
                <?php else : ?>
                    <p style="text-align:center;padding:20px;color:var(--muted);">
                        <?php echo mt_translate( 'Activa el plugin de reservas para ver el formulario.', 'Activate the booking plugin to view the form.' ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Beneficios de reservar con nosotros (Trust signals) -->
            <div class="hero__checks" style="justify-content: center; margin-top: 40px; color: var(--ink);">
                <span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php echo mt_translate( 'Confirmación inmediata', 'Immediate confirmation' ); ?>
                </span>
                <span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php echo mt_translate( 'Cancelación gratuita', 'Free cancellation' ); ?>
                </span>
                <span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php echo mt_translate( 'Conductores VIP', 'VIP Chauffeurs' ); ?>
                </span>
            </div>

        </div>
    </section>
</main>

<style>
/* Ajustes específicos para que el panel se vea bien como elemento central de la página */
.page-template-page-reservaciones .hero__panel {
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.05);
}
@media (max-width: 768px) {
    .page-template-page-reservaciones .hero__panel {
        padding: 24px 20px;
    }
}
</style>

<?php get_footer(); ?>
