<?php
/**
 * Template Name: SEO - Traslados Privados
 *
 * Plantilla de SEO Optimizaciones para la keyword "Traslados privados".
 */

get_header(); ?>

<style>
.seo-hero {
    position: relative;
    background: linear-gradient(135deg, #0B1F35 0%, #0d3b6e 60%, #112a4a 100%);
    padding: 120px 24px 64px;
    min-height: 85vh;
    display: flex;
    align-items: center;
}
.seo-hero-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 56px;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}
@media (max-width: 1024px) {
    .seo-hero-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
}
.hero__panel {
    background: #fff;
    border-radius: 20px;
    padding: 36px 32px;
    box-shadow: 0 24px 60px rgba(0,0,0,.22);
    position: relative;
}
.hero__panel::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: #0066CC;
}
.hero__panel h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0B1F35;
    margin-bottom: 20px;
    text-align: center;
}
.hero-badge-seo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.1);
    padding: 8px 16px;
    border-radius: 50px;
    color: #FFB547;
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 1rem;
    letter-spacing: 0.5px;
}
.hero-title-seo {
    color: #fff;
    font-size: clamp(2.2rem, 4vw, 3.5rem);
    line-height: 1.1;
    margin-bottom: 1.5rem;
    font-weight: 800;
}
.hero-lead-seo {
    color: rgba(255,255,255,0.85);
    font-size: 1.15rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}
.hero__checks {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 20px;
    margin-top: 24px;
    font-size: 0.95rem;
    font-weight: 600;
    color: rgba(255,255,255,0.8);
}
.hero__checks span {
    display: flex;
    align-items: center;
    gap: 8px;
}
.hero__checks svg {
    color: #FFB547;
}
.cta-section h2.section-title {
    color: #fff !important;
}
.cta-section p {
    color: rgba(255,255,255,0.7) !important;
}
</style>

<main id="primary" class="site-main" style="background-color: var(--bg-secondary);">

    <!-- Hero Section con Formulario de Reservas -->
    <section class="seo-hero">
        <div class="seo-hero-grid">
            <!-- TEXTO -->
            <div>
                <div class="hero-badge-seo"><?php echo mt_translate('Viajes Premium | Discreción y Puntualidad'); ?></div>
                <h1 class="hero-title-seo"><?php echo mt_translate('Traslados privados desde Barcelona a toda España'); ?></h1>
                <p class="hero-lead-seo">
                    <?php echo mt_translate('Organiza tu viaje privado desde Barcelona a destinos de toda España. Indica la recogida en el aeropuerto, puerto, estación u hotel, el destino y el equipaje para confirmar la disponibilidad y el presupuesto de tu traslado de larga distancia.'); ?>
                </p>
                <div class="hero__checks">
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo mt_translate('Flota Premium y Ejecutiva'); ?></span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo mt_translate('Chóferes Profesionales Multilingües'); ?></span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo mt_translate('Tarifas Cerradas sin Cargos Ocultos'); ?></span>
                </div>
            </div>

            <!-- PANEL RESERVA -->
            <div id="reservar">
                <div class="hero__panel">
                    <h2><?php echo mt_translate('Calcula tu traslado online'); ?></h2>
                    <?php if ( shortcode_exists( 'wptb_booking_form' ) ) : ?>
                        <?php echo do_shortcode( '[wptb_booking_form]' ); ?>
                    <?php else : ?>
                        <p style="text-align:center;padding:20px;color:var(--muted);"><?php echo mt_translate('Contacta con nuestro equipo para consultar disponibilidad y presupuesto.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido SEO -->
    <section class="section container gs-reveal" style="padding: 80px 16px;">
        <div class="entry-content" style="max-width: 900px; margin: 0 auto; line-height: 1.8; font-size: 1.1rem; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">

            <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;"><?php echo mt_translate('Un traslado de larga distancia a tu medida'); ?></h2>
            <p>
                <?php echo mt_translate('En MeTransfers, entendemos que tu tiempo y comodidad son fundamentales. Nuestros traslados privados están diseñados para ofrecer una experiencia superior, superando las expectativas del transporte tradicional. Ya sea que viajes por negocios, asistas a un evento especial o simplemente busques la forma más cómoda de desplazarte por Barcelona, estamos a tu disposición.'); ?>
            </p>
            <p>
                <?php echo mt_translate('Puedes solicitar un viaje desde Barcelona a Madrid, Valencia, Zaragoza o cualquier otro destino de España. Facilita las direcciones completas y las fechas para que el equipo confirme el recorrido, las paradas y las condiciones. Las rutas de larga distancia se presupuestan según cada solicitud.'); ?>
            </p>
            <p>
                <?php echo mt_translate('Todos nuestros vehículos están meticulosamente mantenidos para ofrecer el máximo confort y seguridad. Además, nuestros chóferes son seleccionados por su profesionalidad, discreción y profundo conocimiento de la ciudad y sus alrededores, asegurando que llegues a tu destino a tiempo y sin contratiempos.'); ?>
            </p>

            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;"><?php echo mt_translate('Servicios adaptados a tus necesidades'); ?></h3>
            <p>
                <?php echo mt_translate('Ofrecemos una flota versátil que incluye berlinas elegantes para desplazamientos ejecutivos, espaciosas MINI VAN «V» Class para grupos pequeños y familias, y opciones de BUSINESS CLASS para aquellos que exigen el más alto nivel de lujo y representación. Todos los servicios incluyen encuentro personalizado y asistencia con el equipaje.'); ?>
            </p>

            <div style="background: #eef2ff; border-left: 4px solid #0066CC; padding: 20px; border-radius: 0 8px 8px 0; margin-top: 2rem;">
                <h4 style="margin-top:0; color:#0066CC;"><?php echo mt_translate('NUESTRO COMPROMISO: Puntualidad y Excelencia'); ?></h4>
                <p style="margin-bottom:0;">
                    <?php echo mt_translate('Confirma con el equipo el punto de encuentro, los datos de llegada y las condiciones de espera antes del viaje. Si necesitas paradas, sillas infantiles o llevar equipaje especial, indícalo al solicitar el presupuesto.'); ?>
                </p>
            </div>

        </div>
    </section>

    <section class="section container" aria-labelledby="long-distance-faq" style="padding: 48px 16px;">
        <h2 id="long-distance-faq"><?php echo esc_html(mt_translate('Preguntas frecuentes sobre viajes desde Barcelona')); ?></h2>
        <details><summary><?php echo esc_html(mt_translate('¿Puedo solicitar un traslado a cualquier destino de España?')); ?></summary><p><?php echo esc_html(mt_translate('Sí. Envía el origen, el destino y la fecha. El equipo comprobará la disponibilidad y te indicará las condiciones y el presupuesto antes de confirmar el servicio.')); ?></p></details>
        <details><summary><?php echo esc_html(mt_translate('¿Se pueden incluir paradas y el viaje de vuelta?')); ?></summary><p><?php echo esc_html(mt_translate('Solicita las paradas y el regreso al preparar el presupuesto. Indica sus direcciones y horarios: no se incluyen automáticamente en el trayecto de ida.')); ?></p></details>
        <details><summary><?php echo esc_html(mt_translate('¿Qué información debo facilitar sobre pasajeros y equipaje?')); ?></summary><p><?php echo esc_html(mt_translate('Comunica el número de viajeros, las maletas, los carritos y cualquier material voluminoso para confirmar un vehículo con capacidad adecuada. Consulta también las sillas infantiles que necesites.')); ?></p></details>
        <p><a href="<?php echo esc_url(mt_localized_url('rutas')); ?>"><?php echo esc_html(mt_translate('Explorar rutas desde Barcelona')); ?></a> · <a href="<?php echo esc_url(mt_localized_url('contacto')); ?>"><?php echo esc_html(mt_translate('Solicitar un presupuesto personalizado')); ?></a></p>
        <p><a href="https://www.getyourguide.com/es-es/metransfers-s12737/" target="_blank" rel="noopener noreferrer"><?php echo esc_html(mt_translate('Consultar el perfil de MeTransfers en GetYourGuide')); ?></a></p>
    </section>
    <!-- CTA FINAL -->
    <section class="cta" id="cta-final">
      <div class="wrap">
        <div class="cta__inner">
          <div>
            <p class="tag"><?php echo mt_translate('Eleva el nivel de tu transporte'); ?></p>
            <h2><?php echo mt_translate('Reserva ahora tu traslado privado premium'); ?></h2>
            <p class="cta__lead"><?php echo mt_translate('Disfruta de la comodidad y fiabilidad de un chófer privado. Rellena el formulario o consulta con el equipo el presupuesto para tu recorrido.'); ?></p>
          </div>
          <div class="cta__btns">
            <a href="#reservar" class="btn btn-solid" style="background:#fff;color:var(--deep);"><?php echo mt_translate('Calcular precio y reservar'); ?></a>
            <a href="https://wa.me/34662024136?text=Hola,%20quisiera%20informaci%C3%B3n%20sobre%20sus%20traslados%20privados%20en%20Barcelona" class="btn btn-ghost-inv" target="_blank" rel="noopener"><?php echo mt_translate('Consultar por WhatsApp'); ?></a>
          </div>
        </div>
      </div>
    </section>
</main>

<?php get_footer(); ?>
