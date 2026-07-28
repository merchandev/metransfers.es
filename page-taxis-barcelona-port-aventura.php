<?php
/**
 * Template Name: SEO - Taxis Barcelona a Port Aventura
 * 
 * Plantilla de SEO Optimizaciones para la keyword "Taxis Barcelona a Port Aventura".
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
                <div class="hero-badge-seo">Directo al parque | Precio fijo garantizado</div>
                <h1 class="hero-title-seo">Taxis y traslados desde Barcelona a Port Aventura</h1>
                <p class="hero-lead-seo">
                    Reserva tu traslado privado desde Barcelona hasta el parque de atracciones Port Aventura. Viaje directo, cómodo y sin complicaciones para disfrutar de tu dÍa al máximo con toda la familia.
                </p>
                <div class="hero__checks">
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> VehÍculos espaciosos (hasta 8 pax)</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Sillas infantiles bajo petición</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Recogida en hotel o aeropuerto</span>
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
            
            <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">Tu traslado directo a Port Aventura</h2>
            <p>
                OlvÍdate de trenes con horarios inflexibles o autobuses llenos de gente. Te recogemos en la puerta de tu hotel en Barcelona, o directamente en el Aeropuerto de El Prat, y te llevamos directamente a la entrada de Port Aventura o a tu hotel dentro del parque.
            </p>
            <p>
                Con nuestro servicio de <strong>traslado privado a Port Aventura</strong>, el viaje dura aproximadamente 1 hora y 15 minutos, dependiendo del tráfico. Llegarás relajado y listo para subir a las atracciones más emocionantes de Europa.
            </p>
            
            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">Viaja cómodamente con toda la familia</h3>
            <p>
                Sabemos que viajar a un parque de atracciones en familia implica llevar maletas, mochilas y carritos de bebé. Por eso, contamos con una flota versátil de minivans y furgonetas Mercedes-Benz que ofrecen espacio de sobra para tu grupo y todo el equipaje.
            </p>
            <p>
                Además, si viajas con niños pequeños, proporcionamos <strong>sillas infantiles homologadas</strong> sin coste extra, para garantizar la máxima seguridad en todo el trayecto (solo debes indicarlo en las notas al hacer la reserva).
            </p>

            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">Reserva de ida y vuelta para mayor tranquilidad</h3>
            <p>
                Después de un largo dÍa caminando y disfrutando en Port Aventura, Ferrari Land o Caribe Aquatic Park, lo último que deseas es preocuparte por cómo volver a Barcelona. Reserva tu traslado de ida y vuelta con nosotros y un conductor privado te estará esperando a la hora acordada a la salida del parque, o en tu hotel, para llevarte de regreso a tu destino.
            </p>

            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 0 8px 8px 0; margin-top: 2rem;">
                <h4 style="margin-top:0; color:#856404;">INFORMACIÓN ÚTIL: Horarios de recogida</h4>
                <p style="margin-bottom:0; color: #856404;">
                    Si reservas tu regreso el mismo dÍa, te recomendamos programar la recogida entre 30 y 45 minutos después del cierre del parque para evitar aglomeraciones en la salida, dándote tiempo para salir tranquilamente de las instalaciones.
                </p>
            </div>

        </div>
    </section>

    <!-- ══════════════════════════ OPINIONES ══════════════════════════ -->
    <section class="sp bg-warm gs-reveal" id="opiniones" style="background:#f8fafc; padding: 80px 16px;">
      <div class="wrap" style="max-width: 1200px; margin: 0 auto;">
        <p class="tag tc" style="text-align: center; color: #0066CC; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">Opiniones de clientes</p>
        <h2 class="tc" style="text-align: center; margin-bottom: 40px; font-size: 2.2rem; color: #0D1B2A;">Nuestros clientes nos recomiendan</h2>
        
        <div class="rev__grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;">★★★★★</div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;">"Contratamos el servicio desde nuestro hotel en Barcelona hasta el Hotel Colorado Creek en Port Aventura. La furgoneta era nuevÍsima, muy amplia y el chófer muy amable con los niños."</p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;">Laura G.</span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;">España · Julio 2024</span>
          </div>
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;">★★★★★</div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;">"Nos recogieron en el aeropuerto y fuimos directos al parque. Pedimos dos sillas para niños y las tenÍan preparadas. Fue súper cómodo evitar el tren cargados con maletas y carritos."</p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;">Carlos M.</span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;">México · Agosto 2024</span>
          </div>
          <div class="rev__card" style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="rev__stars" style="color: #FBBF24; font-size: 1.2rem; margin-bottom: 15px;">★★★★★</div>
            <p class="rev__quote" style="font-style: italic; color: #475569; margin-bottom: 20px;">"El trayecto de vuelta después de un dÍa agotador en Port Aventura fue un lujo. El coche nos estaba esperando exactamente a la hora y lugar acordados. Servicio impecable de 10."</p>
            <span class="rev__author" style="display: block; font-weight: bold; color: #1E293B;">Thomas K.</span>
            <span class="rev__meta" style="font-size: 0.9rem; color: #94A3B8;">Alemania · Septiembre 2024</span>
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
            <p class="cta__lead">Indica el origen, el destino, la fecha y la hora. Te mostraremos las opciones disponibles para que reserves el vehÍculo que mejor se adapta a tu trayecto.</p>
          </div>
          <div class="cta__btns">
            <a href="#reservar" class="btn btn-solid" style="background:#fff;color:var(--deep);">Presupuestar y reservar</a>
            <a href="https://wa.me/34662024136?text=Hola,%20quisiera%20informaci%C3%B3n%20sobre%20traslados%20desde%20Barcelona%20a%20Port%20Aventura" class="btn btn-ghost-inv" target="_blank" rel="noopener">Consultar por WhatsApp</a>
          </div>
        </div>
      </div>
    </section>
</main>

<?php get_footer(); ?>
