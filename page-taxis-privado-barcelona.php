<?php
/**
 * Template Name: SEO - Taxis Privado Barcelona
 * 
 * Plantilla de SEO Optimizaciones para la keyword "Taxis privado barcelona".
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
                <div class="hero-badge-seo">Taxis y traslados | Precio fijo, sin sorpresas</div>
                <h1 class="hero-title-seo">Traslados privados desde Barcelona — Precio fijo, sin sorpresas</h1>
                <p class="hero-lead-seo">
                    Te recogemos en tu hotel, apartamento u oficina en Barcelona y te llevamos directamente a tu destino: aeropuerto El Prat, Puerto de Cruceros, otra ciudad o cualquier dirección. Chófer privado, tarifa cerrada y atención 24 h.
                </p>
                <div class="hero__checks">
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Presupuesto a medida</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Cancelación gratuita hasta 24 h antes</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Atención 24/7</span>
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
            
            <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">Traslados privados desde Barcelona al aeropuerto, puerto y más</h2>
            <p>
                Barcelona es el punto de partida para miles de viajeros cada día. Tanto si necesitas llegar al Aeropuerto Josep Tarradellas Barcelona-El Prat, al Puerto de Cruceros, a otra ciudad de España o a un destino en el extranjero, en MeTransfers nos encargamos de que llegues a tiempo, cómodo y sin estrés.
            </p>
            <p>
                Nuestro conductor te recogerá en la dirección que indiques — tu hotel, apartamento, oficina o cualquier punto de Barcelona — y te llevará directamente a tu destino. El vehículo estará reservado en exclusiva para ti, tu familia o tu grupo; no compartirás el coche con otras personas ni harás paradas no acordadas.
            </p>
            <p>
                También organizamos el trayecto de vuelta: si llegas a Barcelona desde otro destino, tu chófer te esperará con un cartel personalizado en la zona de llegadas y te acompañará hasta el vehículo, ayudándote con el equipaje. Indícanos siempre el número de vuelo o barco para que podamos monitorizar el horario y ajustar la recogida en tiempo real.
            </p>

            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">Elige el vehículo ideal para tu salida desde Barcelona</h3>
            <p>
                Disponemos de berlinas ejecutivas para 1–3 pasajeros, MINI VAN «V» Class para grupos de hasta 7 personas y vehículos BUSINESS CLASS para servicios VIP y corporativos. Si viajas con mucho equipaje, material deportivo o equipamiento especial, indÍcanoslo antes de confirmar para asignarte el vehículo más adecuado.
            </p>

            <div style="background: #eef2ff; border-left: 4px solid #0066CC; padding: 20px; border-radius: 0 8px 8px 0; margin-top: 2rem;">
                <h4 style="margin-top:0; color:#0066CC;">CONSEJO: Reserva con antelación para garantizar tu plaza</h4>
                <p style="margin-bottom:0;">
                    Los servicios de traslado privado desde Barcelona tienen alta demanda, especialmente en temporada alta y fines de semana. Reservando con antelación aseguras disponibilidad, precio cerrado y sin sorpresas el día del viaje.
                </p>
            </div>

        </div>
    </section>

    <!-- ══════════════════════════ OPINIONES ══════════════════════════ -->
    <section class="sp bg-warm gs-reveal" id="opiniones" style="background:#f8fafc; padding: 80px 16px;">
      <div class="wrap" style="max-width: 1200px; margin: 0 auto;">
        <p class="tag tc" style="text-align: center; color: #0066CC; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">Opiniones de viajeros</p>
        <h2 class="tc" style="text-align: center; margin-bottom: 40px; font-size: 2.2rem; color: #0D1B2A;">La confianza se gana en cada recogida</h2>
        
        <div class="rev__grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;">★★★★★</div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;">"El chófer fue muy puntual y nos ayudó con todo el equipaje. El vehículo estaba impecable y el viaje desde el aeropuerto al hotel fue muy cómodo tras un vuelo largo. Sin duda repetiremos."</p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;">James S.</span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;">Reino Unido · Agosto 2024</span>
          </div>
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;">★★★★★</div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;">"Reservamos un traslado al puerto de cruceros para nuestra familia. Espacio de sobra en la MINI VAN «V» Class y una atención al cliente perfecta por WhatsApp para confirmar los detalles."</p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;">MarÍa R.</span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;">España · Septiembre 2024</span>
          </div>
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;">★★★★★</div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;">"Hicimos el tour a Montserrat y fue espectacular. El conductor conocÍa perfectamente la ruta, nos dio consejos útiles y adaptó los tiempos a nuestro ritmo. Un servicio de 10."</p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;">Anna J.</span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;">Estados Unidos · Octubre 2024</span>
          </div>
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
            <a href="https://wa.me/34662024136?text=Hola,%20quisiera%20informaci%C3%B3n%20sobre%20sus%20servicios%20de%20taxi%20privado%20en%20Barcelona" class="btn btn-ghost-inv" target="_blank" rel="noopener">Consultar por WhatsApp</a>
          </div>
        </div>
      </div>
    </section>
</main>

<?php get_footer(); ?>

