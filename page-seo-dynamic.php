<?php
/**
 * Template Name: SEO - Aterrizaje Dinámico (Taxis / Traslados)
 * 
 * Plantilla dinámica para múltiples landing pages generadas automáticamente.
 */

get_header(); 

// Extraemos los datos del post actual
global $post;
$page_title = $post->post_title;
$slug = $post->post_name;

// Variables por defecto
$destino = '';
$tipo_servicio = 'Taxis';
$tipo_verbo = 'Taxis';
$keyword = 'Traslado privado';

// Determinar el destino usando la lógica del título
// Patrón 1: "MeTransfers Barcelona - Taxis Barcelona a [Destino]"
// Patrón 2: "MeTransfers Barcelona - Traslado privado a [Destino] desde Barcelona"
if ( strpos($page_title, 'Taxis Barcelona a') !== false ) {
    $destino = trim( str_replace('MeTransfers Barcelona - Taxis Barcelona a ', '', $page_title) );
    $tipo_servicio = 'Taxis';
    $tipo_verbo = 'Taxis';
    $keyword = 'Taxis y traslados';
} elseif ( strpos($page_title, 'Traslado privado a') !== false ) {
    $destino = str_replace( 'MeTransfers Barcelona - Traslado privado a ', '', $page_title );
    $destino = trim( str_replace( ' desde Barcelona', '', $destino ) );
    $tipo_servicio = 'Traslados privados';
    $tipo_verbo = 'Traslados';
    $keyword = 'Traslado privado';
}

// Fallback por si acaso
if ( empty($destino) ) {
    $destino = get_post_meta( $post->ID, '_seo_destino', true );
}
if ( empty($destino) ) {
    $destino = 'tu destino';
}

$title_hero = sprintf('%s desde Barcelona a %s', $keyword, $destino);
$lead_hero = sprintf('Inicia tus vacaciones o viaje de negocios sin estrés. Reserva tu %s desde el Aeropuerto de Barcelona o el centro de la ciudad hasta %s. Viaja en vehículos premium con un chófer profesional a tu disposición.', strtolower($tipo_servicio), $destino);

?>

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
                <div class="hero-badge-seo"><?php echo esc_html( mt_translate( 'De puerta a puerta | Confort garantizado' ) ); ?></div>
                <h1 class="hero-title-seo"><?php echo esc_html( mt_translate( $title_hero ) ); ?></h1>
                <p class="hero-lead-seo">
                    <?php echo esc_html( mt_translate( $lead_hero ) ); ?>
                </p>
                <div class="hero__checks">
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo esc_html( mt_translate( 'Precio fijo sin cargos ocultos' ) ); ?></span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo esc_html( mt_translate( 'Vehículos modernos y limpios' ) ); ?></span>
                  <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo esc_html( mt_translate( 'Seguimiento de vuelos en tiempo real' ) ); ?></span>
                </div>
            </div>

            <!-- PANEL RESERVA -->
            <div id="reservar">
                <div class="hero__panel">
                    <h2><?php echo esc_html( mt_translate( 'Calcula tu traslado online' ) ); ?></h2>
                    <?php if ( shortcode_exists( 'wptb_booking_form' ) ) : ?>
                        <?php echo do_shortcode( '[wptb_booking_form]' ); ?>
                    <?php else : ?>
                        <p style="text-align:center;padding:20px;color:var(--muted);"><?php echo esc_html( mt_translate( 'Activa el plugin de reservas (WPTB).' ) ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    

    <!-- Contenido SEO -->
    <section class="section container gs-reveal" style="padding: 80px 16px;">
        <div class="entry-content" style="max-width: 900px; margin: 0 auto; line-height: 1.8; font-size: 1.1rem; background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            
            <?php 
            $page_content = trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) );
            if ( $page_content ) :
                the_content();
            else : 
            ?>
                <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;"><?php echo esc_html( mt_translate( $tipo_servicio  )); ?> a <?php echo esc_html( mt_translate( $destino  )); ?> sin esperas</h2>
                <p>
                    Llegar a <?php echo esc_html( mt_translate( $destino  )); ?> desde Barcelona nunca fue tan sencillo. Evita las largas colas para el autobús y las incómodas combinaciones de trenes con equipaje. Nuestro servicio de <strong><?php echo esc_html( mt_translate( strtolower($tipo_servicio) ) ); ?> a <?php echo esc_html( mt_translate( $destino  )); ?></strong> está pensado para que tu descanso comience desde el momento en que aterrizas.
                </p>
                <p>
                    Nuestros conductores monitorizan los vuelos de llegada al Aeropuerto de El Prat, por lo que incluso si tu vuelo se retrasa, te estaremos esperando en la terminal de llegadas con un cartel con tu nombre. Desde allí, el trayecto hasta <?php echo esc_html( mt_translate( $destino  )); ?> será directo y confortable.
                </p>
                
                <h3 style="color: var(--text-dark); margin-top: 2.5rem; margin-bottom: 1rem;">La mejor opción para familias y grupos</h3>
                <p>
                    Si viajas con amigos, familia o grupos grandes, disponemos de espaciosas furgonetas y minivans Mercedes-Benz con capacidad para hasta 7 pasajeros y todo su equipaje. 
                </p>
                <p>
                    Además, proporcionamos bajo solicitud las sillas de retención infantil adecuadas para que los más pequeños viajen de forma segura y cumpliendo todas las normativas de tráfico.
                </p>

                <div style="background: #eef2ff; border-left: 4px solid #0066CC; padding: 20px; border-radius: 0 8px 8px 0; margin-top: 2rem;">
                    <h4 style="margin-top:0; color:#0066CC;">CONSEJO: Reserva tu trayecto de ida y vuelta</h4>
                    <p style="margin-bottom:0;">
                        Para disfrutar de un viaje sin sobresaltos, te aconsejamos reservar conjuntamente el trayecto de ida y el de vuelta al aeropuerto. Nuestro conductor te recogerá en la puerta de tu hotel en <?php echo esc_html( mt_translate( $destino  )); ?> a la hora óptima para que llegues a tiempo a tu vuelo en Barcelona.
                    </p>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- CTA FINAL -->
    <section class="cta" id="cta-final">
      <div class="wrap">
        <div class="cta__inner">
          <div>
            <p class="tag"><?php echo esc_html( mt_translate( 'Reserva tu próximo traslado' ) ); ?></p>
            <h2><?php echo esc_html( mt_translate( 'Tu viaje comienza con una recogida bien organizada' ) ); ?></h2>
            <p class="cta__lead"><?php echo esc_html( mt_translate( 'Indica el origen, el destino, la fecha y la hora. Te mostraremos las opciones disponibles para que reserves el vehículo que mejor se adapta a tu trayecto.' ) ); ?></p>
          </div>
          <div class="cta__btns">
            <a href="#reservar" class="btn btn-solid" style="background:#fff;color:var(--deep);"><?php echo esc_html( mt_translate( 'Presupuestar y reservar' ) ); ?></a>
            <a href="https://wa.me/34662024136?text=<?php echo urlencode( mt_translate( 'Hola, quisiera información sobre traslados desde Barcelona a ' ) . $destino ); ?>" class="btn btn-ghost-inv" target="_blank" rel="noopener"><?php echo esc_html( mt_translate( 'Consultar por WhatsApp' ) ); ?></a>
          </div>
        </div>
      </div>
    </section>
</main>

<?php get_footer(); ?>

