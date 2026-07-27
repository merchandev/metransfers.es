<?php
/**
 * Template Name: Ruta Comercial
 * Template Post Type: post, page, ruta
 *
 * @package Me_Transfers
 */

get_header();

// Fetch Meta Data
$ruta_id  = get_the_ID();
$origen   = get_post_meta( $ruta_id, '_mt_ruta_origen',   true );
$destino  = get_post_meta( $ruta_id, '_mt_ruta_destino',  true );
$duracion = get_post_meta( $ruta_id, '_mt_ruta_duracion', true );
$pax      = get_post_meta( $ruta_id, '_mt_ruta_pax',      true );
$maletas  = get_post_meta( $ruta_id, '_mt_ruta_maletas',  true );
$precio   = get_post_meta( $ruta_id, '_mt_ruta_precio',   true );

// H1 SEO: construido desde los metadatos de la ruta, no desde el título interno
if ( $origen && $destino ) {
    $h1_text = sprintf(
        'Transfer privado del %s a %s',
        esc_html( $origen ),
        esc_html( $destino )
    );
} else {
    // Fallback al título del post si faltan los metadatos
    $h1_text = get_the_title();
}

$hero_bg = get_the_post_thumbnail_url( $ruta_id, 'full' );
if ( ! $hero_bg ) {
// $hero_bg fallback video removed: was pointing to staging2.metransfers.es. Set via CMS/options if needed.
}

?>

<main id="primary" class="site-main">

    <!-- HERO SECTION -->
    <section class="hero-section" <?php if(strpos($hero_bg, '.mp4') === false) echo 'style="background-image: url('.esc_url($hero_bg).'); background-size: cover; background-position: center;"'; ?>>
        
        <?php if(strpos($hero_bg, '.mp4') !== false): ?>
        <iframe class="hero-video-bg" src="https://player.vimeo.com/video/1200289297?background=1&autoplay=1&loop=1&byline=0&title=0&muted=1&quality=1080p" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
        <?php endif; ?>
        
        <div class="hero-overlay-dark"></div>
        <div class="hero-overlay-vignette"></div>

        <div class="container hero-container" style="padding-top: 4rem;">
            <div class="hero-layout">
                <div class="hero-content gs-reveal">
                    <div class="hero-badge">
                        <span class="hero-badge-dot"></span> Ruta Premium Garantizada
                    </div>
                    <h1 class="hero-title"><?php echo wp_kses_post( $h1_text ); ?></h1>

                    <p class="hero-subtitle">
                        Traslado privado con vehículos Mercedes-Benz, conductor profesional y presupuesto cerrado sin sorpresas.
                    </p>

                    <div class="hero-actions">
                        <a href="#booking-widget" class="btn btn-primary hero-btn-main">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            Cotizar Traslado
                        </a>
                    </div>
                </div>

                <div class="hero-booking-column gs-reveal" id="booking-widget">
                    <div class="hero-booking-card">
                        <?php if ( shortcode_exists( 'wptb_booking_form' ) ) : ?>
                            <?php echo do_shortcode( '[wptb_booking_form]' ); ?>
                        <?php else : ?>
                            <p class="hero-booking-placeholder">Activa el plugin de reservas.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- RESUMEN DE LA RUTA -->
    <?php if($duracion || $pax || $precio): ?>
    <section class="ruta-resumen section" style="background-color: #f8fafc; padding: 3rem 0;">
        <div class="container gs-reveal">
            <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-around; text-align: center;">
                <?php if($duracion): ?>
                <div class="ruta-stat">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#004E9A" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <h4 style="margin: 10px 0 5px; color:#0D1B2A;">Tiempo de Viaje</h4>
                    <p style="margin:0; color:#475569; font-weight: 500;"><?php echo esc_html($duracion); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if($pax || $maletas): ?>
                <div class="ruta-stat">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#004E9A" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <h4 style="margin: 10px 0 5px; color:#0D1B2A;">Capacidad</h4>
                    <p style="margin:0; color:#475569; font-weight: 500;">Max <?php echo esc_html($pax); ?> Pasajeros / <?php echo esc_html($maletas); ?> Maletas</p>
                </div>
                <?php endif; ?>

                <div class="ruta-stat">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#004E9A" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M17 12h.01"/><path d="M7 12h.01"/></svg>
                    <h4 style="margin: 10px 0 5px; color:#0D1B2A;">Presupuesto a Medida</h4>
                    <p style="margin:0; color:#475569; font-weight: 500;">A consultar</p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- QUE INCLUYE -->
    <section class="ruta-incluye section">
        <div class="container gs-reveal">
            <h2 class="section-title text-center">¿Qué incluye tu reserva?</h2>
            <div class="tours-grid" style="margin-top: 2rem;">
                <article class="tour-card">
                    <div class="tour-content">
                        <h3 style="color:#004E9A;">Meet & Greet</h3>
                        <p>Tu conductor te estará esperando en la terminal de llegadas o lobby de tu hotel con un cartel con tu nombre.</p>
                    </div>
                </article>
                <article class="tour-card">
                    <div class="tour-content">
                        <h3 style="color:#004E9A;">Seguimiento de Vuelo</h3>
                        <p>Monitorizamos tu vuelo. Si hay retrasos, ajustamos la hora de recogida sin ningún cargo extra.</p>
                    </div>
                </article>
                <article class="tour-card">
                    <div class="tour-content">
                        <h3 style="color:#004E9A;">Presupuesto a Medida</h3>
                        <p>Sin taxímetros, sin tarifas dinámicas, sin sorpresas. El presupuesto que apruebas es el que pagas.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- CONTENT AREA -->
    <section class="section container gs-reveal">
        <div class="entry-content" style="max-width: 800px; margin: 0 auto; line-height: 1.8; font-size: 1.1rem; color: #334155;">
            <?php
            while ( have_posts() ) :
                the_post();
                the_content();
            endwhile;
            ?>
        </div>
    </section>

    <!-- GYG REVIEWS -->
    <section class="gyg-section section">
        <div class="container gs-reveal text-center">
            <h2 class="section-title">Confianza de <span class="text-gradient">Viajeros Globales</span></h2>
            <span class="gyg-badge" style="margin-bottom: 2rem; display: inline-block;">â˜… 4.9 / 5 en GetYourGuide</span>
            <?php if ( shortcode_exists( 'gyg_reviews' ) ) : ?>
                <?php echo do_shortcode( '[gyg_reviews count="4"]' ); ?>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php get_footer(); ?>
