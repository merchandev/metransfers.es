<?php
/**
 * Template Name: Página Madre (Servicios Hub)
 *
 * @package Me_Transfers
 */

get_header(); ?>

<main id="primary" class="site-main">
    <section class="hero-section" style="padding-bottom: 4rem;">
        <div class="hero-overlay-dark"></div>
        <div class="container hero-container" style="text-align:center;">
            <h1 class="hero-title" style="color:#fff; margin-top:2rem;"><?php the_title(); ?></h1>
        </div>
    </section>
    
    <section class="section container gs-reveal">
        <div class="entry-content" style="max-width: 1000px; margin: 0 auto;">
            <?php
            while ( have_posts() ) :
                the_post();
                the_content();
            endwhile;
            ?>
        </div>
    </section>

    <section id="search" class="search-plugin-section section" style="background-color: #f8fafc;">
        <div class="container gs-reveal">
            <header class="section-header section-header--compact text-center">
                <h2 class="section-title"><?php echo mt_translate('Busca tu'); ?> <span class="text-gradient"><?php echo mt_translate('Destino'); ?></span></h2>
            </header>
            <div class="search-plugin-wrapper" style="max-width: 800px; margin: 0 auto;">
                <?php if ( shortcode_exists( 'premium_transfers_search' ) ) : ?>
                    <?php echo do_shortcode( '[premium_transfers_search]' ); ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
