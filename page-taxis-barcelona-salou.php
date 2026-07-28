<?php
/**
 * Template Name: SEO - Taxis Barcelona a Salou
 * 
 * Plantilla de SEO Optimizaciones para la keyword "Taxis Barcelona a Salou".
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
                <div class="hero-badge-seo">De puerta a puerta | Confort garantizado</div>
                <h1 class="hero-title-seo">Taxis y traslados desde Barcelona a Salou</h1>
                <p class="hero-lead-seo">
                    Inicia tus vacaciones en la Costa Dorada sin estrés. Reserva tu traslado privado desde el Aeropuerto de Barcelona o la ciudad hasta tu hotel o apartamento en Salou. Viaja en vehículos premium con un chófer profesional a tu disposición.
                </p>
                <div class="hero__checks">
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Precio fijo sin cargos ocultos</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Vehículos modernos y limpios</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Seguimiento de vuelos en tiempo real</span>
                </div>
            </div>

            <!-- PANEL RESERVA -->
            <div id="reservar">
                <div class="hero__panel">
                    <h2>Calcula tu traslado online</h2>
                    <?php if ( shortcode_exists( 'wptb_booking_form' ) ) : ?>
                        <?php echo do_shortcode( '[wptb_booking_form]' ); ?>
                    <?php else : ?>
                        <p style="text-align:center;padding:20px;color:var(--muted);">Activa el plugin de reservas (WPTB).</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contenido SEO -->
    <section class="section container gs-reveal" style="padding: 80px 16px;">
        <div class="entry-content" style="max-width: 900px; margin: 0 auto; line-height: 1.8; font-size: 1.1rem; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            
            <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">Traslados privados a Salou sin esperas</h2>
            <p>
                Llegar a la Costa Dorada desde Barcelona nunca fue tan sencillo. Evita las largas colas para el autobús y las incómodas combinaciones de trenes con equipaje. Nuestro servicio de <strong>traslados a Salou</strong> está pensado para que tu descanso comience desde el momento en que aterrizas.
            </p>
            <p>
                Nuestros conductores monitorizan los vuelos de llegada al Aeropuerto de El Prat, por lo que incluso si tu vuelo se retrasa, te estaremos esperando en la terminal de llegadas con un cartel con tu nombre. Desde allÍ, el trayecto hasta Salou dura poco más de una hora por autopista (AP-7).
            </p>
            
            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">La mejor opción para familias y grupos</h3>
            <p>
                Salou es un destino eminentemente familiar y vacacional. Si viajas con amigos, familia o grupos grandes, disponemos de espaciosas furgonetas y minivans Mercedes-Benz con capacidad para hasta 8 pasajeros y todo su equipaje. 
            </p>
            <p>
                Además, proporcionamos bajo solicitud las sillas de retención infantil adecuadas para que los más pequeños viajen de forma segura y cumpliendo todas las normativas de tráfico.
            </p>

            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">También traslados desde y hacia Port Aventura</h3>
            <p>
                Si tu objetivo en Salou es visitar el complejo de Port Aventura World, nosotros nos encargamos de dejarte directamente en tu hotel temático dentro del parque, o en tu apartamento en el paseo de Jaume I.
            </p>

            <div style="background: #eef2ff; border-left: 4px solid #0066CC; padding: 20px; border-radius: 0 8px 8px 0; margin-top: 2rem;">
                <h4 style="margin-top:0; color:#0066CC;">CONSEJO: Reserva tu trayecto de ida y vuelta</h4>
                <p style="margin-bottom:0;">
                    Para disfrutar de unas vacaciones sin sobresaltos, te aconsejamos reservar conjuntamente el trayecto de ida y el de vuelta al aeropuerto. Nuestro conductor te recogerá en la puerta de tu hotel en Salou a la hora óptima para que llegues a tiempo a tu vuelo en Barcelona.
                </p>
            </div>

        </div>
    </section>

    <!-- CTA FINAL -->
    <section class="cta" id="cta-final">
      <div class="wrap">
        <div class="cta__inner">
          <div>
            <p class="tag">Reserva tu próximo traslado</p>
            <h2>Tu viaje comienza con una recogida bien organizada</h2>
            <p class="cta__lead">Indica el origen, el destino, la fecha y la hora. Te mostraremos las opciones disponibles para que reserves el vehículo que mejor se adapta a tu trayecto.</p>
          </div>
          <div class="cta__btns">
            <a href="#reservar" class="btn btn-solid" style="background:#fff;color:var(--deep);">Presupuestar y reservar</a>
            <a href="https://wa.me/34662024136?text=Hola,%20quisiera%20informaci%C3%B3n%20sobre%20traslados%20desde%20Barcelona%20a%20Salou" class="btn btn-ghost-inv" target="_blank" rel="noopener">Consultar por WhatsApp</a>
          </div>
        </div>
      </div>
    </section>
</main>

<?php get_footer(); ?>

