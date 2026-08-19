<?php
/**
 * Template Name: Página Segmento (B2B/Nichos)
 *
 * @package Me_Transfers
 */

get_header(); ?>

<main id="primary" class="site-main">
    <section class="hero-section" style="padding-bottom: 4rem;">
        <div class="hero-overlay-dark"></div>
        <div class="container hero-container" style="text-align:center;">
            <div class="hero-badge" style="margin: 0 auto 1rem;"><span class="hero-badge-dot"></span> <?php echo mt_translate('Exclusivo Empresas & Agencias'); ?></div>
            <h1 class="hero-title" style="color:#fff;"><?php the_title(); ?></h1>
        </div>
    </section>
    
    <section class="section container gs-reveal">
        <div class="entry-content" style="max-width: 900px; margin: 0 auto; line-height: 1.8; font-size: 1.1rem;">
            <?php
            while ( have_posts() ) :
                the_post();
                the_content();
            endwhile;
            ?>
        </div>
    </section>
    
    <section class="contact-section section" style="background-color: #0D1B2A;">
        <div class="container gs-reveal text-center">
            <h2 class="section-title" style="color: #fff;"><?php echo mt_translate('Atención Especializada 24/7'); ?></h2>
            <p style="color: #cbd5e1; max-width: 600px; margin: 0 auto 2rem;"><?php echo mt_translate('Contáctanos vía WhatsApp para coordinar la logística corporativa o acuerdos para hoteles.'); ?></p>
            <a href="https://wa.me/34662024136?text=Hola,%20quisiera%20informaci%C3%B3n%20para%20empresas/hoteles" target="_blank" rel="noopener" class="btn btn-whatsapp" style="display: inline-flex; align-items: center; gap: 10px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                <?php echo mt_translate('Contactar por WhatsApp'); ?>
            </a>
        </div>
    </section>
</main>

<?php get_footer(); ?>
