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
$h1_seo = get_post_meta( $ruta_id, '_mt_ruta_h1', true );
if ( ! empty( $h1_seo ) ) {
    $h1_text = $h1_seo;
} elseif ( $origen && $destino ) {
    $h1_text = sprintf(
        'Transfer privado del %s a %s',
        esc_html( $origen ),
        esc_html( $destino )
    );
} else {
    $h1_text = get_the_title();
}

$hero_bg = get_the_post_thumbnail_url( $ruta_id, 'full' );

?>

<main id="primary" class="site-main ruta-page">

    <!-- HERO — mismo tamaño y estructura que el home -->
    <section class="ruta-hero" style="background-image:url('<?php echo $hero_bg ? esc_url( $hero_bg ) : 'https://metransfers.es/wp-content/uploads/2026/07/airport-transfer-me-tranfers-me-tranfers-barcelona-espana.webp'; ?>'); background-size:cover; background-position:center;">

        <div class="hero-overlay-dark"></div>
        <div class="hero-overlay-vignette"></div>

        <div class="container ruta-hero__inner">
            <div class="ruta-hero__left gs-reveal">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    Transfer Privado · MeTransfers
                </div>
                <h1 class="ruta-hero__title"><?php echo wp_kses_post( $h1_text ); ?></h1>
                <p class="ruta-hero__subtitle">
                    Vehículo Mercedes-Benz, conductor profesional y precio cerrado. Sin taxímetros ni sorpresas.
                </p>

                <!-- BREADCRUMB ORIGEN → DESTINO -->
                <?php if ( $origen && $destino ) : ?>
                <div class="ruta-hero__route">
                    <span class="ruta-hero__route-point ruta-hero__route-point--origin">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/></svg>
                        <?php echo esc_html( $origen ); ?>
                    </span>
                    <span class="ruta-hero__route-arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                    <span class="ruta-hero__route-point ruta-hero__route-point--dest">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo esc_html( $destino ); ?>
                    </span>
                </div>
                <?php endif; ?>

                <div class="ruta-hero__actions">
                    <a href="#booking-widget" class="btn btn-primary hero-btn-main">Solicitar precio</a>
                    <a href="tel:+34662024136" class="btn btn-outline-white">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 5.93 5.93l.91-.91a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21.73 16a2 2 0 0 1 .27.92z"/></svg>
                        Llamar
                    </a>
                </div>
            </div>

            <div class="ruta-hero__right gs-reveal" id="booking-widget">
                <div class="hero-booking-card">
                    <?php if ( shortcode_exists( 'wptb_booking_form' ) ) : ?>
                        <?php echo mt_translate( do_shortcode( '[wptb_booking_form]' ) ); ?>
                    <?php else : ?>
                        <p class="hero-booking-placeholder">Activa el plugin de reservas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS BAR -->
    <div class="ruta-statsbar">
        <div class="container">
            <div class="ruta-statsbar__grid">
                <?php if ( $duracion ) : ?>
                <div class="ruta-statsbar__item gs-reveal">
                    <div class="ruta-statsbar__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="ruta-statsbar__label">Duración</div>
                        <div class="ruta-statsbar__value"><?php echo esc_html( $duracion ); ?> <span style="font-size: 0.85em; opacity: 0.7;" title="Tiempo estimado. Puede variar según tráfico.">*</span></div>
                    </div>
                </div>
                <?php else : ?>
                <div class="ruta-statsbar__item gs-reveal">
                    <div class="ruta-statsbar__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="ruta-statsbar__label">Duración</div>
                        <div class="ruta-statsbar__value" style="font-size: 0.85em; opacity: 0.8;">Pendiente de confirmar</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $pax ) : ?>
                <div class="ruta-statsbar__item gs-reveal">
                    <div class="ruta-statsbar__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div>
                        <div class="ruta-statsbar__label">Pasajeros</div>
                        <div class="ruta-statsbar__value">Máx. <?php echo esc_html( $pax ); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $maletas ) : ?>
                <div class="ruta-statsbar__item gs-reveal">
                    <div class="ruta-statsbar__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
                    </div>
                    <div>
                        <div class="ruta-statsbar__label">Equipaje</div>
                        <div class="ruta-statsbar__value"><?php echo esc_html( $maletas ); ?> maletas</div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="ruta-statsbar__item gs-reveal">
                    <div class="ruta-statsbar__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <div class="ruta-statsbar__label">Vehículo</div>
                        <div class="ruta-statsbar__value">Mercedes-Benz</div>
                    </div>
                </div>

                <div class="ruta-statsbar__item gs-reveal">
                    <div class="ruta-statsbar__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    </div>
                    <div>
                        <div class="ruta-statsbar__label">Disponibilidad</div>
                        <div class="ruta-statsbar__value">24h · 7 días</div>
                    </div>
                </div>
            </div>
            
            <p style="text-align: center; font-size: 0.85rem; color: var(--text-secondary); margin-top: 1.5rem; margin-bottom: 0; padding: 0 1rem; opacity: 0.8;">
                * El tiempo de trayecto es una estimación. La duración real puede tener variaciones según el estado del tráfico, condiciones del clima, paradas extras solicitadas o eventualidades en carretera que escapan del control de la empresa de traslados.
            </p>
        </div>
    </div>

    <!-- QUE INCLUYE -->
    <section class="ruta-incluye section">
        <div class="container">
            <div class="ruta-section-header gs-reveal">
                <h2 class="section-title">¿Qué incluye tu traslado?</h2>
                <p class="section-subtitle">Todo lo que necesitas para llegar con tranquilidad, incluido de serie.</p>
            </div>
            <div class="ruta-features gs-reveal">
                <div class="ruta-feature">
                    <div class="ruta-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="ruta-feature__body">
                        <h3>Meet &amp; Greet</h3>
                        <p>Tu conductor espera en la terminal de llegadas con un cartel con tu nombre. Cero esperas innecesarias.</p>
                    </div>
                </div>
                <div class="ruta-feature">
                    <div class="ruta-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3.055 11H5a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2 2 2 0 0 1 2 2v2.945"/><path d="M8 3.935V5.5A2.5 2.5 0 0 0 10.5 8h.5a2 2 0 0 1 2 2 2 2 0 0 0 4 0 2 2 0 0 1 2-2h1.064"/><path d="M15 3.516V5a2 2 0 0 0 2 2h2.484"/><circle cx="12" cy="12" r="10"/></svg>
                    </div>
                    <div class="ruta-feature__body">
                        <h3>Seguimiento de Vuelo</h3>
                        <p>Monitorizamos tu vuelo cuando facilitas correctamente el número de vuelo y adaptamos la coordinación de la recogida a la hora real de llegada. Los tiempos de espera incluidos dependen de las condiciones de tu reserva.</p>
                    </div>
                </div>
                <div class="ruta-feature">
                    <div class="ruta-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="ruta-feature__body">
                        <h3>Precio Cerrado</h3>
                        <p>El precio que aceptas en tu reserva es el que pagas. Sin taxímetros, sin tarifas dinámicas.</p>
                    </div>
                </div>
                <div class="ruta-feature">
                    <div class="ruta-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="ruta-feature__body">
                        <h3>Puerta a Puerta</h3>
                        <p>Recogida y entrega en el punto exacto que indiques. Hotel, apartamento, crucero o estación.</p>
                    </div>
                </div>
                <div class="ruta-feature">
                    <div class="ruta-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                    </div>
                    <div class="ruta-feature__body">
                        <h3>Equipaje Incluido</h3>
                        <p>Maletero amplio para toda tu equipaje sin cargos extra. Informamos de límites si los hay.</p>
                    </div>
                </div>
                <div class="ruta-feature">
                    <div class="ruta-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="ruta-feature__body">
                        <h3>Cancelación Flexible</h3>
                        <p>Consulta las condiciones de cancelación aplicables antes de confirmar tu reserva.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTENT AREA (editor WordPress) -->
    <?php
    $post_content = get_the_content();
    if ( ! empty( trim( $post_content ) ) ) :
    ?>
    <section class="ruta-content section">
        <div class="container">
            <div class="ruta-content__inner entry-content gs-reveal">
                <?php
                while ( have_posts() ) :
                    the_post();
                    // Filtro de H1 duplicado: el template ya emite el H1 en el header de la ruta.
                    // Para proteger el SEO, degradamos cualquier H1 añadido manualmente en el editor a un H2.
                    $mt_demote_h1_to_h2 = static function ( $content ) {
                        $content = str_ireplace( '<h1', '<h2', $content );
                        $content = str_ireplace( '</h1', '</h2', $content );
                        return $content;
                    };
                    add_filter( 'the_content', $mt_demote_h1_to_h2, 20 );
                    the_content();
                    remove_filter( 'the_content', $mt_demote_h1_to_h2, 20 );
                endwhile;
                ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA STRIP -->
    <section class="ruta-cta-strip gs-reveal">
        <div class="container">
            <div class="ruta-cta-strip__inner">
                <div class="ruta-cta-strip__text">
                    <h2>¿Listo para reservar tu traslado?</h2>
                    <p>Presupuesto inmediato y confirmación en minutos.</p>
                </div>
                <div class="ruta-cta-strip__actions">
                    <a href="#booking-widget" class="btn btn-primary">Solicitar precio ahora</a>
                    <a href="https://wa.me/34662024136?text=Hola%2C+me+interesa+reservar+un+traslado" target="_blank" rel="noopener" class="btn btn-whatsapp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- GYG REVIEWS -->
    <section class="gyg-section section">
        <div class="container gs-reveal text-center">
            <h2 class="section-title">Confianza de <span class="text-gradient">Viajeros Globales</span></h2>
            <span class="gyg-badge" style="margin-bottom: 2rem; display: inline-block;">Opiniones verificadas en GetYourGuide</span>
            <?php if ( shortcode_exists( 'gyg_reviews' ) ) : ?>
                <?php echo do_shortcode( '[gyg_reviews count="4"]' ); ?>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php get_footer(); ?>

