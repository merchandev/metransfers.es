<?php
/**
 * Archivo de todas las Rutas — MeTransfers
 * URL: /rutas/
 *
 * @package Me_Transfers
 */

get_header();

// ─── Catálogo de destinos agrupados por zona ───────────────────────────────
$zonas = array(
    'Costa Dorada' => array(
        'icon'   => '🏖️',
        'slugs'  => array( 'salou', 'cambrils', 'tarragona', 'la-pineda', 'calafell', 'vilanova', 'portaventura' ),
    ),
    'Costa Brava' => array(
        'icon'   => '⛵',
        'slugs'  => array( 'lloret-de-mar', 'tossa-de-mar', 'blanes', 'calella', 'platja-daro', 'roses', 'cadaques', 'girona' ),
    ),
    'Cataluña Interior' => array(
        'icon'   => '⛰️',
        'slugs'  => array( 'sitges', 'montserrat', 'reus', 'andorra', 'la-molina', 'baqueira-beret' ),
    ),
);

// ─── Obtener TODAS las rutas publicadas ────────────────────────────────────
$all_rutas = get_posts( array(
    'post_type'      => 'ruta',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'meta_query'     => array(
        array(
            'key'   => '_mt_seo_ready',
            'value' => '1',
        ),
    ),
) );

// Indexar por slug para acceso rápido
$rutas_by_slug = array();
foreach ( $all_rutas as $r ) {
    $rutas_by_slug[ $r->post_name ] = $r;
}

// ─── Agrupar rutas por destino ─────────────────────────────────────────────
$grupos = array(); // [ 'Salou' => [ postObj, postObj, … ] ]

foreach ( $all_rutas as $r ) {
    $destino = get_post_meta( $r->ID, '_mt_ruta_destino', true );
    if ( ! $destino ) {
        // Extraer destino del slug si falta el meta
        $parts   = explode( '–', $r->post_title );
        $destino = isset( $parts[1] ) ? trim( $parts[1] ) : $r->post_title;
    }
    $grupos[ $destino ][] = $r;
}
ksort( $grupos );

// Punto de partida de cada ruta (extraído del meta)
function mt_get_origen( $post ) {
    $origen = get_post_meta( $post->ID, '_mt_ruta_origen', true );
    if ( ! $origen ) {
        $parts  = explode( '–', $post->post_title );
        $origen = isset( $parts[0] ) ? trim( $parts[0] ) : '—';
    }
    return $origen;
}

$total = count( $all_rutas );
?>

<main id="primary" class="site-main rutas-archive">

    <!-- ── HERO ────────────────────────────────────────────────────── -->
    <section class="rutas-hero">
        <div class="rutas-hero__overlay"></div>
        <div class="container rutas-hero__inner">
            <div class="hero-badge gs-reveal">
                <span class="hero-badge-dot"></span>
                Traslados Privados desde Barcelona
            </div>
            <h1 class="rutas-hero__title gs-reveal">
                Todas nuestras <span class="text-gradient">Rutas</span>
            </h1>
            <p class="rutas-hero__subtitle gs-reveal">
                <?php echo $total; ?> rutas disponibles con vehículo privado Mercedes-Benz, conductor profesional y precio cerrado.
                Aeropuerto, Puerto, Sants o cualquier punto de Barcelona.
            </p>
            <div class="rutas-hero__search gs-reveal">
                <div class="rutas-search-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="ruta-search" placeholder="Busca tu destino: Salou, Sitges, Lloret…" autocomplete="off">
                </div>
            </div>
        </div>
    </section>

    <!-- ── STATS BAR ───────────────────────────────────────────────── -->
    <div class="rutas-statsbar">
        <div class="container rutas-statsbar__grid">
            <div class="rutas-statsbar__item">
                <span class="rutas-statsbar__num"><?php echo $total; ?></span>
                <span class="rutas-statsbar__lbl">Rutas disponibles</span>
            </div>
            <div class="rutas-statsbar__sep"></div>
            <div class="rutas-statsbar__item">
                <span class="rutas-statsbar__num"><?php echo count( $grupos ); ?></span>
                <span class="rutas-statsbar__lbl">Destinos</span>
            </div>
            <div class="rutas-statsbar__sep"></div>
            <div class="rutas-statsbar__item">
                <span class="rutas-statsbar__num">24/7</span>
                <span class="rutas-statsbar__lbl">Disponibilidad</span>
            </div>
            <div class="rutas-statsbar__sep"></div>
            <div class="rutas-statsbar__item">
                <span class="rutas-statsbar__num">4.9★</span>
                <span class="rutas-statsbar__lbl">GetYourGuide</span>
            </div>
        </div>
    </div>

    <!-- ── GRID POR DESTINO ─────────────────────────────────────────── -->
    <section class="section rutas-grid-section" id="rutas-lista">
        <div class="container">

            <?php if ( empty( $grupos ) ) : ?>
                <p class="rutas-empty">Pronto añadiremos rutas. Contáctanos para cualquier destino.</p>
            <?php else : ?>

            <?php foreach ( $grupos as $destino => $posts ) : ?>
            <div class="rutas-grupo gs-reveal" data-destino="<?php echo esc_attr( strtolower( $destino ) ); ?>">

                <div class="rutas-grupo__header">
                    <h2 class="rutas-grupo__title">
                        <svg class="rutas-grupo__pin" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo esc_html( $destino ); ?>
                    </h2>
                    <span class="rutas-grupo__count"><?php echo count( $posts ); ?> ruta<?php echo count( $posts ) > 1 ? 's' : ''; ?></span>
                </div>

                <div class="rutas-cards">
                    <?php foreach ( $posts as $ruta ) :
                        $origen   = mt_get_origen( $ruta );
                        $duracion = get_post_meta( $ruta->ID, '_mt_ruta_duracion', true );
                        $pax      = get_post_meta( $ruta->ID, '_mt_ruta_pax',      true );
                        $url      = get_permalink( $ruta->ID );
                        // Icono según punto de salida
                        if ( stripos( $origen, 'aeropuerto' ) !== false ) $icono = '✈️';
                        elseif ( stripos( $origen, 'puerto' ) !== false )  $icono = '🚢';
                        elseif ( stripos( $origen, 'sants' ) !== false )   $icono = '🚄';
                        else                                                $icono = '🏙️';
                    ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="ruta-card" data-search="<?php echo esc_attr( strtolower( $destino . ' ' . $origen . ' ' . $ruta->post_title ) ); ?>">
                        <div class="ruta-card__icon"><?php echo $icono; ?></div>
                        <div class="ruta-card__body">
                            <div class="ruta-card__origen"><?php echo esc_html( $origen ); ?></div>
                            <div class="ruta-card__arrow">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </div>
                            <div class="ruta-card__dest"><?php echo esc_html( $destino ); ?></div>
                        </div>
                        <?php if ( $duracion || $pax ) : ?>
                        <div class="ruta-card__meta">
                            <?php if ( $duracion ) : ?><span>⏱ <?php echo esc_html( $duracion ); ?></span><?php endif; ?>
                            <?php if ( $pax ) : ?><span>👤 Máx. <?php echo esc_html( $pax ); ?> pax</span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="ruta-card__cta">Ver ruta <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>
                    </a>
                    <?php endforeach; ?>
                </div>

            </div><!-- .rutas-grupo -->
            <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </section>

    <!-- ── CTA FINAL ────────────────────────────────────────────────── -->
    <section class="ruta-cta-strip">
        <div class="container">
            <div class="ruta-cta-strip__inner">
                <div class="ruta-cta-strip__text">
                    <h2>¿No encuentras tu destino?</h2>
                    <p>Cotizamos cualquier ruta desde Barcelona a medida, sin compromiso.</p>
                </div>
                <div class="ruta-cta-strip__actions">
                    <a href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>" class="btn btn-primary">Solicitar ruta personalizada</a>
                    <a href="https://wa.me/34662024136?text=Hola%2C+necesito+un+traslado+privado+desde+Barcelona" target="_blank" rel="noopener" class="btn btn-whatsapp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

</main>

<script>
(function(){
    var input  = document.getElementById('ruta-search');
    if (!input) return;
    input.addEventListener('input', function(){
        var q = this.value.trim().toLowerCase();
        document.querySelectorAll('.ruta-card').forEach(function(card){
            var hay = card.dataset.search.indexOf(q) !== -1;
            card.style.display = hay || !q ? '' : 'none';
        });
        // Ocultar grupo si todas sus cards están ocultas
        document.querySelectorAll('.rutas-grupo').forEach(function(grupo){
            var visible = grupo.querySelectorAll('.ruta-card:not([style*="display: none"])').length;
            grupo.style.display = visible ? '' : 'none';
        });
    });
})();
</script>

<?php get_footer(); ?>

