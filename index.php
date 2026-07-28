<?php
/**
 * The main template file "” Blog index.
 *
 * @package Me_Transfers
 */

get_header();
?>

<main id="primary" class="site-main blog-index-main">

	<!-- Blog Hero Banner -->
	<section class="blog-index-hero">
		<div class="container blog-index-hero__inner">
			<span class="blog-index-eyebrow"><?php esc_html_e( 'GuÍas, consejos y noticias', 'me-transfers' ); ?></span>
			<h1 class="blog-index-title"><?php esc_html_e( 'Blog de MeTransfers', 'me-transfers' ); ?></h1>
			<p class="blog-index-intro"><?php esc_html_e( 'Descubre los mejores destinos, rutas y consejos de viaje para disfrutar de Barcelona y toda España en traslado privado de lujo.', 'me-transfers' ); ?></p>
		</div>
	</section>

	<!-- Blog Posts Grid -->
	<section class="blog-index-section section">
		<div class="container">
			<?php if ( have_posts() ) : ?>

				<div class="blog-index-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'blog-index-card' ); ?>>

							<div class="blog-index-card__body">
								<h2 class="blog-index-card__title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h2>

								<p class="blog-index-card__excerpt">
									<?php echo esc_html( wp_trim_words( get_the_excerpt(), 20, '...' ) ); ?>
								</p>

								<div class="blog-index-card__footer">
									<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="blog-index-card__date">
										<?php echo esc_html( get_the_date( 'd M Y' ) ); ?>
									</time>
									<a href="<?php the_permalink(); ?>" class="blog-index-card__link">
										<?php esc_html_e( 'Leer artÍculo', 'me-transfers' ); ?>
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
									</a>
								</div>
							</div>

						</article>
					<?php endwhile; ?>
				</div>

				<!-- Pagination -->
				<div class="blog-index-pagination">
					<?php the_posts_navigation( array(
						'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> ' . esc_html__( 'Entradas anteriores', 'me-transfers' ),
						'next_text' => esc_html__( 'Entradas siguientes', 'me-transfers' ) . ' <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
					) ); ?>
				</div>

			<?php else : ?>
				<div class="blog-index-empty">
					<p><?php esc_html_e( 'No hay entradas publicadas todavÍa.', 'me-transfers' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

</main><!-- #primary -->

<?php get_footer(); ?>
