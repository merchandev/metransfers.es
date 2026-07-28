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

<main id="primary" class="site-main" role="main" aria-label="<?php esc_attr_e( 'Página no encontrada', 'me-transfers' ); ?>">

    <section class="error-404-premium">
        <div class="container error-404-premium__inner">
            
            <div class="error-404-premium__visual">
                <h1 class="error-404-premium__digits">404</h1>
                <p class="error-404-premium__badge"><?php esc_html_e( 'ERROR 404', 'me-transfers' ); ?></p>
            </div>

            <div class="error-404-premium__content">
                <h2 class="error-404-premium__title">
                    <?php esc_html_e( 'Vaya, parece que te has perdido.', 'me-transfers' ); ?>
                </h2>
                
                <p class="error-404-premium__desc">
                    <?php esc_html_e( 'La página que buscas no existe o ha sido movida. No te preocupes, puedes volver al inicio o buscar lo que necesitas a continuación.', 'me-transfers' ); ?>
                </p>

                <div class="error-404-premium__actions">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <?php esc_html_e( 'Volver al inicio', 'me-transfers' ); ?>
                    </a>
                    <a href="<?php echo esc_url( me_transfers_get_section_url( 'search' ) ); ?>" class="btn btn-outline">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <?php esc_html_e( 'Reservar traslado', 'me-transfers' ); ?>
                    </a>
                </div>

                <div class="error-404-premium__search">
                    <form role="search" method="get" class="error-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <div class="error-search-wrapper">
                            <svg class="error-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="search" class="error-search-input" placeholder="<?php esc_attr_e( 'Buscar en el sitio...', 'me-transfers' ); ?>" value="<?php echo get_search_query(); ?>" name="s" required />
                            <button type="submit" class="error-search-submit"><?php esc_html_e( 'Buscar', 'me-transfers' ); ?></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
