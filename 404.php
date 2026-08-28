<?php
/**
 * 404 - Página no encontrada
 *
 * @package Me_Transfers
 */

// Garantizar que la respuesta HTTP sea 404
if ( ! headers_sent() ) {
    status_header( 404 );
}

get_header();
?>

<main id="primary" class="site-main" role="main" aria-label="<?php echo esc_attr( mt_translate( 'Página no encontrada' ) ); ?>">

    <section class="error-404-premium">
        <div class="container error-404-premium__inner">
            
            <div class="error-404-premium__visual">
                <h1 class="error-404-premium__digits">404</h1>
                <p class="error-404-premium__badge"><?php echo esc_html( mt_translate( 'ERROR 404' ) ); ?></p>
            </div>

            <div class="error-404-premium__content">
                <h2 class="error-404-premium__title">
                    <?php echo esc_html( mt_translate( 'Vaya, parece que te has perdido.' ) ); ?>
                </h2>
                
                <p class="error-404-premium__desc">
                    <?php echo esc_html( mt_translate( 'La página que buscas no existe o ha sido movida. No te preocupes, puedes volver al inicio o buscar lo que necesitas a continuación.' ) ); ?>
                </p>

                <div class="error-404-premium__actions" style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:24px;">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary" style="text-decoration:none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin-right:8px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <?php echo esc_html( mt_translate( 'Volver al inicio' ) ); ?>
                    </a>
                    <a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>" class="btn btn-outline" style="text-decoration:none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin-right:8px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <?php echo esc_html( mt_translate( 'Reservar traslado' ) ); ?>
                    </a>
                </div>

                <div class="error-404-premium__search" style="margin-top:24px;">
                    <form role="search" method="get" class="error-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <div class="error-search-wrapper" style="display:flex;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:100px;padding:4px;box-shadow:0 4px 15px rgba(0,0,0,.03);">
                            <svg class="error-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:16px;color:#94a3b8;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="search" class="error-search-input" placeholder="<?php echo esc_attr( mt_translate( 'Buscar en el sitio...' ) ); ?>" value="<?php echo get_search_query(); ?>" name="s" required style="flex:1;border:none;background:transparent;padding:12px 16px;font-size:16px;color:#0f172a;outline:none;" />
                            <button type="submit" class="btn btn-solid" style="border-radius:100px;"><?php echo esc_html( mt_translate( 'Buscar' ) ); ?></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
