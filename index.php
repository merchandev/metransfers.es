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
	<section class="blog-index-hero" style="background: linear-gradient(180deg, #05173D 0%, #003A52 100%); padding: 180px 20px 80px; text-align: center; color: white;">
		<div class="container blog-index-hero__inner" style="max-width: 800px; margin: 0 auto;">
			<span class="blog-index-eyebrow" style="color: #00c2ff; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 3px; display: block; margin-bottom: 1.2rem;"><?php echo esc_html( mt_translate( 'Guías, consejos y noticias' ) ); ?></span>
			<h1 class="blog-index-title" style="color: #ffffff; font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 800; margin-bottom: 1.5rem; line-height: 1.1; letter-spacing: -1px;"><?php echo esc_html( mt_translate( 'Blog & Noticias' ) ); ?></h1>
			<p class="blog-index-intro" style="color: rgba(255,255,255,0.85); font-size: 1.15rem; line-height: 1.6; margin: 0 auto; max-width: 650px;"><?php echo esc_html( mt_translate( 'Descubre los mejores destinos, rutas y consejos de viaje para disfrutar de Barcelona y toda España en traslado privado de lujo.' ) ); ?></p>
		</div>
	</section>
	
	<style>
		.blog-index-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem; }
		.blog-index-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: transform 0.3s; display: flex; flex-direction: column; height: 100%; border: 1px solid #e2e8f0; }
		.blog-index-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.12); }
		.blog-index-card__media { position: relative; height: 240px; display: block; overflow: hidden; }
		.blog-index-card__img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
		.blog-index-card:hover .blog-index-card__img { transform: scale(1.05); }
		.blog-index-card__cat-pill { position: absolute; top: 16px; left: 16px; background: #00c2ff; color: #05173D; font-size: 0.75rem; font-weight: 800; padding: 6px 12px; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; }
		.blog-index-card__body { padding: 24px; display: flex; flex-direction: column; flex: 1; }
		.blog-index-card__meta { margin-bottom: 12px; color: #64748b; font-size: 0.85rem; font-weight: 500; }
		.blog-index-card__title { font-size: 1.3rem; font-weight: 800; line-height: 1.3; margin-bottom: 12px; }
		.blog-index-card__title a { color: #0f172a; text-decoration: none; transition: color 0.2s; }
		.blog-index-card__title a:hover { color: #00c2ff; }
		.blog-index-card__excerpt { color: #475569; font-size: 0.95rem; line-height: 1.6; margin-bottom: 24px; flex: 1; }
		.blog-index-card__footer { margin-top: auto; padding-top: 16px; border-top: 1px solid #f1f5f9; }
		.blog-index-card__link { color: #05173D; font-weight: 700; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s; }
		.blog-index-card__link:hover { color: #00c2ff; }
		.blog-index-card__link svg { transition: transform 0.2s; }
		.blog-index-card__link:hover svg { transform: translateX(4px); }
		
		/* Pagination Styles */
		.blog-index-pagination { margin-top: 50px; display: flex; justify-content: center; }
		.blog-index-pagination .nav-links { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: center; }
		.blog-index-pagination .page-numbers { display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 16px; border-radius: 100px; background: #fff; border: 1px solid #e2e8f0; color: #0f172a; font-weight: 600; text-decoration: none; transition: all 0.2s; }
		.blog-index-pagination .page-numbers:hover { background: #f1f5f9; border-color: #cbd5e1; }
		.blog-index-pagination .page-numbers.current { background: #00c2ff; color: #05173D; border-color: #00c2ff; }
		.blog-index-pagination .page-numbers.dots { background: transparent; border: none; }
	</style>

	<!-- Blog Posts Grid -->
	<section class="blog-index-section section" style="padding: 80px 0; background: #f8fafc;">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="blog-index-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						$post_id    = get_the_ID();
						$categories = get_the_category( $post_id );
						$cat_name   = ! empty( $categories ) ? $categories[0]->name : 'Noticia';
						?>
						<article id="post-<?php echo esc_attr( $post_id ); ?>" <?php post_class( 'blog-index-card', $post_id ); ?>>
							<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="blog-index-card__media">
								<?php if ( has_post_thumbnail( $post_id ) ) : ?>
									<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'blog-index-card__img', 'loading' => 'lazy', 'alt' => esc_attr( get_the_title( $post_id ) ) ) ); ?>
								<?php endif; ?>
								<span class="blog-index-card__cat-pill"><?php echo esc_html( mt_translate( $cat_name ) ); ?></span>
							</a>
							
							<div class="blog-index-card__body">
								<div class="blog-index-card__meta">
									<time class="blog-index-card__date" datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>">
										<?php echo esc_html( get_the_date( 'd M Y', $post_id ) ); ?>
									</time>
								</div>
								
								<h2 class="blog-index-card__title">
									<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
										<?php echo esc_html( get_the_title( $post_id ) ); ?>
									</a>
								</h2>
								
								<?php 
								$excerpt = get_the_excerpt( $post_id );
								if ( ! empty( $excerpt ) ) : 
									$trimmed_excerpt = wp_trim_words( $excerpt, 30, '...' );
								?>
									<p class="blog-index-card__excerpt">
										<?php echo wp_kses_post( wp_strip_all_tags( $trimmed_excerpt ) ); ?>
									</p>
								<?php endif; ?>
								
								<div class="blog-index-card__footer">
									<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="blog-index-card__link">
										<?php echo esc_html( mt_translate( 'Leer artículo' ) ); ?>
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
									</a>
								</div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

				<div class="blog-index-pagination">
					<div class="nav-links">
						<?php
						echo paginate_links(
							array(
								'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> ' . esc_html( mt_translate( 'Entradas anteriores' ) ),
								'next_text' => esc_html( mt_translate( 'Entradas siguientes' ) ) . ' <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
							)
						);
						?>
					</div>
				</div>
			<?php else : ?>
				<div class="blog-index-empty">
					<p><?php echo esc_html( mt_translate( 'No hay entradas publicadas todavía.' ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
