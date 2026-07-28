<?php
/**
 * Template Name: SEO - Taxis Barcelona a Girona
 * 
 * Plantilla de SEO Optimizaciones para la keyword "Taxis Barcelona a Girona".
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
                <div class="hero-badge-seo">Elegancia, rapidez y confort</div>
                <h1 class="hero-title-seo">Taxis y traslados desde Barcelona a Girona</h1>
                <p class="hero-lead-seo">
                    Traslados premium entre Barcelona y la ciudad de Girona o su aeropuerto. Ya viajes por turismo, negocios o para tomar un vuelo, disfruta de un coche privado, puntual y a tarifa fija.
                </p>
                <div class="hero__checks">
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Trayectos directos por autopista</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Conexión entre aeropuertos (El Prat / GRO)</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Facturación para empresas</span>
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
            
            <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">De Barcelona al Barri Vell de Girona</h2>
            <p>
                Girona es una ciudad con un encanto histórico inigualable, famosa por su vibrante barrio judÍo, sus puentes sobre el rÍo Onyar y por haber sido escenario de series famosas como Juego de Tronos. 
            </p>
            <p>
                Si tienes previsto visitarla o alojarte allÍ, reserva tu <strong>taxi de Barcelona a Girona</strong> con MeTransfers. El viaje dura poco más de una hora, es totalmente directo y te dejaremos a los pies de tu hotel o alojamiento, algo clave dado que gran parte del centro histórico es peatonal o de difÍcil acceso si no se conoce la zona.
            </p>
            
            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">Conexiones de aeropuerto a aeropuerto</h3>
            <p>
                Muchos viajeros aterrizan en el <strong>Aeropuerto de Girona-Costa Brava (GRO)</strong> para dirigirse a Barcelona, o viceversa, aterrizan en Barcelona y necesitan llegar rápidamente al aeropuerto de Girona para un vuelo de conexión. 
            </p>
            <p>
                Ofrecemos traslados urgentes y programados entre ambos aeropuertos o entre la ciudad y las terminales aéreas. Nuestros chóferes te esperarán en el punto de encuentro de la terminal, te ayudarán con el equipaje y se asegurarán de que llegues a tiempo.
            </p>

            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">Turismo corporativo y de negocios</h3>
            <p>
                Girona es también un importante centro de negocios. Si viajas por motivos profesionales, ponemos a tu disposición berlinas de clase ejecutiva (como el Mercedes-Benz Clase E) con interiores sobrios y elegantes. Emitimos facturas oficiales y garantizamos la máxima discreción para ti o para los directivos y clientes de tu empresa.
            </p>

            <div style="background: #eef2ff; border-left: 4px solid #0066CC; padding: 20px; border-radius: 0 8px 8px 0; margin-top: 2rem;">
                <h4 style="margin-top:0; color:#0066CC;">CONSEJO: Viajes de un día (Tour privado)</h4>
                <p style="margin-bottom:0;">
                    Girona se puede ver perfectamente en una excursión de un día. Consúltanos por nuestros tours privados en los que te llevamos a Girona por la mañana y regresamos contigo a Barcelona por la tarde.
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
            <a href="https://wa.me/34662024136?text=Hola,%20quisiera%20informaci%C3%B3n%20sobre%20traslados%20desde%20Barcelona%20a%20Girona" class="btn btn-ghost-inv" target="_blank" rel="noopener">Consultar por WhatsApp</a>
          </div>
        </div>
      </div>
    </section>
</main>

<?php get_footer(); ?>

