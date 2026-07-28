<?php
/**
 * Template Name: SEO - Taxis Barcelona a Costa Brava
 * 
 * Plantilla de SEO Optimizaciones para la keyword "Taxis Barcelona a Costa Brava".
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
                <div class="hero-badge-seo">Playas, calas y pueblos | Viaje exclusivo</div>
                <h1 class="hero-title-seo">Taxis y traslados desde Barcelona a la Costa Brava</h1>
                <p class="hero-lead-seo">
                    Disfruta del paisaje y comienza tu escapada de lujo. Reserva tu taxi o traslado privado desde Barcelona o El Prat hacia cualquier destino de la Costa Brava (Lloret de Mar, Tossa, Cadaqués, Begur). 
                </p>
                <div class="hero__checks">
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Choferes experimentados</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> VehÍculos clase VIP disponibles</span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Puntualidad y discreción</span>
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
            
            <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">Viaja a los pueblos más bonitos de la Costa Brava</h2>
            <p>
                La Costa Brava ofrece algunos de los paisajes marÍtimos más espectaculares de Cataluña. Sin embargo, acceder a muchos de sus pintorescos pueblos y calas escondidas mediante transporte público puede ser complicado y consumir mucho tiempo de tus vacaciones. 
            </p>
            <p>
                La solución ideal es reservar un <strong>traslado privado desde Barcelona a la Costa Brava</strong>. Te recogemos en el Aeropuerto de Barcelona (El Prat), la estación de Sants o tu propio hotel en la ciudad, y te llevamos puerta a puerta hasta tu alojamiento costero.
            </p>
            
            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">Destinos populares en la Costa Brava</h3>
            <p>
                Ofrecemos servicios de transfer a todas las poblaciones principales, incluyendo:
            </p>
            <ul style="margin-bottom: 1.5rem; color: #334155; line-height: 1.8;">
                <li><strong>Lloret de Mar y Blanes:</strong> Los destinos más rápidos y populares, ideales para turismo de playa.</li>
                <li><strong>Tossa de Mar:</strong> Famosa por su impresionante castillo medieval junto al mar.</li>
                <li><strong>Platja d'Aro y Palamós:</strong> Excelentes opciones gastronómicas y de tiendas de lujo.</li>
                <li><strong>Begur, Pals y L'Escala:</strong> Encanto histórico, ruinas de Empúries y calas espectaculares de aguas cristalinas.</li>
                <li><strong>Cadaqués y Roses:</strong> El encanto del Cap de Creus y la tierra que inspiró a Salvador DalÍ.</li>
            </ul>

            <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">La comodidad de un coche privado</h3>
            <p>
                Todos nuestros vehÍculos están equipados con aire acondicionado, asientos confortables y amplio espacio para el equipaje, algo esencial si viajas para unas vacaciones largas en la playa o si llevas material deportivo (como palos de golf o equipo de buceo).
            </p>

            <div style="background: #eef2ff; border-left: 4px solid #0066CC; padding: 20px; border-radius: 0 8px 8px 0; margin-top: 2rem;">
                <h4 style="margin-top:0; color:#0066CC;">INFORMACIÓN: Chófer por horas o excursiones de un dÍa</h4>
                <p style="margin-bottom:0;">
                    Si prefieres no alojarte allÍ pero deseas explorar la Costa Brava durante un dÍa, puedes contratar nuestro servicio de "Chófer por horas". Tu conductor personal te llevará a los pueblos que elijas, esperará por ti y te traerá de vuelta a Barcelona por la tarde.
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
            <p class="cta__lead">Indica el origen, el destino, la fecha y la hora. Te mostraremos las opciones disponibles para que reserves el vehÍculo que mejor se adapta a tu trayecto.</p>
          </div>
          <div class="cta__btns">
            <a href="#reservar" class="btn btn-solid" style="background:#fff;color:var(--deep);">Presupuestar y reservar</a>
            <a href="https://wa.me/34662024136?text=Hola,%20quisiera%20informaci%C3%B3n%20sobre%20traslados%20desde%20Barcelona%20a%20la%20Costa%20Brava" class="btn btn-ghost-inv" target="_blank" rel="noopener">Consultar por WhatsApp</a>
          </div>
        </div>
      </div>
    </section>
</main>

<?php get_footer(); ?>
