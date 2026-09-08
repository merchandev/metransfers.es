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
                <h1 class="hero-title-seo"><?php echo mt_translate('Traslados Privados Exclusivos en Barcelona y Alrededores'); ?></h1>
                <p class="hero-lead-seo">
                    <?php echo mt_translate('Disfruta de un servicio de transporte premium para tus desplazamientos en Barcelona. Conectamos hoteles, aeropuerto, puerto y eventos con vehículos de alta gama y conductores profesionales, garantizando comodidad y tranquilidad en cada trayecto.'); ?>
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
                        <p style="text-align:center;padding:20px;color:var(--muted);"><?php echo mt_translate('Activa el plugin de reservas (WPTB).'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contenido SEO -->
    <section class="section container gs-reveal" style="padding: 80px 16px;">
        <div class="entry-content" style="max-width: 900px; margin: 0 auto; line-height: 1.8; font-size: 1.1rem; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            
            <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;"><?php echo mt_translate('La experiencia definitiva en traslados privados'); ?></h2>
            <p>
                <?php echo mt_translate('En MeTransfers, entendemos que tu tiempo y comodidad son fundamentales. Nuestros traslados privados están diseñados para ofrecer una experiencia superior, superando las expectativas del transporte tradicional. Ya sea que viajes por negocios, asistas a un evento especial o simplemente busques la forma más cómoda de desplazarte por Barcelona, estamos a tu disposición.'); ?>
            </p>
            <p>
                <?php echo mt_translate('Nuestros servicios abarcan desde recogidas en el Aeropuerto de Barcelona-El Prat y traslados a la terminal de cruceros del puerto, hasta excursiones privadas de un día completo por Cataluña y disposiciones por horas. Tú decides el destino y el horario, nosotros nos encargamos del resto.'); ?>
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
                    <?php echo mt_translate('Garantizamos que tu conductor estará esperándote a la hora acordada, monitoreando en todo momento el estado de tu vuelo o barco para ajustarnos a cualquier eventualidad sin costo adicional para ti.'); ?>
                </p>
            </div>

        </div>
    </section>

    <!-- ══════════════════════════ OPINIONES ══════════════════════════ -->
    <section class="sp bg-warm gs-reveal" id="opiniones" style="background:#f8fafc; padding: 80px 16px;">
      <div class="wrap" style="max-width: 1200px; margin: 0 auto;">
        <p class="tag tc" style="text-align: center; color: #0066CC; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;"><?php echo mt_translate('Experiencias de nuestros clientes'); ?></p>
        <h2 class="tc" style="text-align: center; margin-bottom: 40px; font-size: 2.2rem; color: #0D1B2A;"><?php echo mt_translate('La excelencia reconocida en cada trayecto'); ?></h2>
        
        <div class="rev__grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;"><?php echo mt_translate('★★★★★'); ?></div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;"><?php echo mt_translate('"Un servicio de primera categoría. Utilizo MeTransfers para todos mis viajes corporativos a Barcelona. Discreción, vehículos inmaculados y puntualidad suiza. Muy recomendables."'); ?></p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;"><?php echo mt_translate('Carlos M.'); ?></span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;"><?php echo mt_translate('Alemania · Febrero 2024'); ?></span>
          </div>
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;"><?php echo mt_translate('★★★★★'); ?></div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;"><?php echo mt_translate('"Contratamos un traslado privado para ir a nuestro hotel desde el aeropuerto y todo fue perfecto. El chófer nos esperaba con un cartel y nos ayudó con las maletas amablemente."'); ?></p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;"><?php echo mt_translate('Lucía F.'); ?></span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;"><?php echo mt_translate('España · Mayo 2024'); ?></span>
          </div>
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;"><?php echo mt_translate('★★★★★'); ?></div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;"><?php echo mt_translate('"Increíble atención al detalle. Pedimos una silla para bebé y estaba instalada correctamente. El vehículo era súper espacioso. Una manera inmejorable de empezar las vacaciones."'); ?></p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;"><?php echo mt_translate('Thomas y Sarah'); ?></span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;"><?php echo mt_translate('Reino Unido · Julio 2024'); ?></span>
          </div>
        </div>
      </div>
    </section>
    
    <!-- CTA FINAL -->
    <section class="cta" id="cta-final">
      <div class="wrap">
        <div class="cta__inner">
          <div>
            <p class="tag"><?php echo mt_translate('Eleva el nivel de tu transporte'); ?></p>
            <h2><?php echo mt_translate('Reserva ahora tu traslado privado premium'); ?></h2>
            <p class="cta__lead"><?php echo mt_translate('Disfruta de la comodidad y fiabilidad de un chófer privado. Rellena el formulario y obtén un presupuesto cerrado al instante.'); ?></p>
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
