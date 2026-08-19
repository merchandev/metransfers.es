<?php
/**
 * Template for displaying single blog posts.
 *
 * @package Me_Transfers
 */

get_header();
?>

<main id="primary" class="site-main">

<?php
while ( have_posts() ) :
	the_post();

	$post_id         = get_the_ID();
	$has_thumb       = has_post_thumbnail( $post_id );
	$thumb_id        = $has_thumb ? get_post_thumbnail_id( $post_id ) : 0;
	$categories      = get_the_category( $post_id );
	$tags            = get_the_tags( $post_id );
	$blog_page_id    = (int) get_option( 'page_for_posts' );
	$blog_url        = $blog_page_id ? get_permalink( $blog_page_id ) : home_url( '/blog/' );
	$blog_label      = $blog_page_id ? get_the_title( $blog_page_id ) : __( 'Blog', 'me-transfers' );
	$author_name     = get_the_author();
	$author_url      = get_author_posts_url( (int) get_the_author_meta( 'ID' ) );
	$published_iso   = get_the_date( 'c' );
	$modified_iso    = get_the_modified_date( 'c' );
	$content_source  = preg_replace( '/\[\/?mt_hero_card[^\]]*\]/i', '', (string) get_the_content() );
	$content_plain   = wp_strip_all_tags( strip_shortcodes( $content_source ) );
	$summary_source  = has_excerpt() ? get_the_excerpt() : wp_trim_words( $content_plain, 32, '...' );
	$summary         = trim( wp_strip_all_tags( $summary_source ) );
	$article_images  = array();
	$logo_url        = get_template_directory_uri() . '/assets/img/MT - MeTransfers.png';

	if ( has_custom_logo() ) {
		$logo_image = wp_get_attachment_image_src( (int) get_theme_mod( 'custom_logo' ), 'full' );
		if ( ! empty( $logo_image[0] ) ) {
			$logo_url = $logo_image[0];
		}
	}

	if ( $has_thumb && $thumb_id ) {
		foreach ( array( 'full', 'large', 'medium_large' ) as $image_size ) {
			$image_src = wp_get_attachment_image_src( $thumb_id, $image_size );
			if ( ! empty( $image_src[0] ) ) {
				$article_images[] = $image_src[0];
			}
		}

		$article_images = array_values( array_unique( $article_images ) );
	}

	$article_schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink( $post_id ),
		),
		'headline'         => wp_strip_all_tags( get_the_title( $post_id ) ),
		'description'      => $summary,
		'datePublished'    => $published_iso,
		'dateModified'     => $modified_iso,
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author_name,
			'url'   => $author_url,
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'logo'  => array(
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			),
		),
		'wordCount'        => str_word_count( $content_plain ),
	);

	if ( ! empty( $article_images ) ) {
		$article_schema['image'] = $article_images;
	}

	if ( ! empty( $categories ) ) {
		$article_schema['articleSection'] = wp_list_pluck( $categories, 'name' );
	}

	$breadcrumb_schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Inicio', 'me-transfers' ),
				'item'     => home_url( '/' ),
			),
			array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => $blog_label,
				'item'     => $blog_url,
			),
			array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => wp_strip_all_tags( get_the_title( $post_id ) ),
			),
		),
	);

	$related_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
	);

	if ( ! empty( $categories ) ) {
		$related_args['category__in'] = wp_get_post_categories( $post_id );
	}

	$related_posts = new WP_Query( $related_args );
	?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-article luxury-single-post' ); ?>>
		<?php if ( ! defined( 'WPSEO_VERSION' ) ) : ?>
		<script type="application/ld+json"><?php echo wp_json_encode( array( $breadcrumb_schema, $article_schema ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
		<?php endif; ?>

		<header class="single-article-hero single-article-hero--solid">
			<div class="container single-article-hero-inner">
				<nav class="single-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'me-transfers' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'me-transfers' ); ?></a>
					<span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( $blog_url ); ?>"><?php echo esc_html( mt_translate( $blog_label  )); ?></a>
					<span aria-hidden="true">/</span>
					<span aria-current="page"><?php the_title(); ?></span>
				</nav>

				<h1 class="single-article-title"><?php the_title(); ?></h1>

				<div class="single-article-meta">
					<span class="single-meta-pill">
						<time datetime="<?php echo esc_attr( mt_translate( $published_iso  )); ?>"><?php echo esc_html( mt_translate( get_the_date( 'd M Y' ) ) ); ?></time>
					</span>
				</div>
			</div>
		</header>

		<div class="single-article-main">
			<div class="container">
				<div class="single-article-layout">
					
					<div class="single-article-shell">
						<div class="single-article-body">
							<div class="entry-content luxury-prose js-toc-content">
								<?php
								// Filtro de H1 duplicado: el template ya emite el H1 en el header del artículo.
								// Para proteger el diseño y el SEO, degradamos cualquier H1 añadido 
								// manualmente en el editor a un H2.
								$mt_demote_h1_to_h2 = static function ( $content ) {
									$content = str_ireplace( '<h1', '<h2', $content );
									$content = str_ireplace( '</h1', '</h2', $content );
									return $content;
								};
								add_filter( 'the_content', $mt_demote_h1_to_h2, 20 );
								the_content();
								remove_filter( 'the_content', $mt_demote_h1_to_h2, 20 );
								?>
							</div>
						</div>

						<footer class="single-article-footer">
							<div class="single-article-utility">
								<div class="single-share">
									<span class="single-share-label" style="color: var(--text-secondary) !important; font-weight: bold;"><?php esc_html_e( 'Compartir', 'me-transfers' ); ?></span>
									<a href="https://wa.me/?text=<?php echo urlencode( get_the_title( $post_id ) . ' ' . get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener" class="share-btn share-wa">WhatsApp</a>
								</div>
							</div>

							<div class="single-cta-box">
								<div class="single-cta-copy">
									<span class="single-cta-eyebrow"><?php esc_html_e( 'Reserva privada', 'me-transfers' ); ?></span>
									<h2 class="single-cta-title"><?php esc_html_e( '¿Listo para tu próximo traslado?', 'me-transfers' ); ?></h2>
									<p><?php esc_html_e( 'Reserva tu traslado privado o tour personalizado en Barcelona con una experiencia premium, puntual y adaptada a tu agenda.', 'me-transfers' ); ?></p>
								</div>
								<a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Reservar ahora', 'me-transfers' ); ?></a>
							</div>
						</footer>
					</div>

					<!-- Sidebar -->
					<aside class="single-article-sidebar">
						<div class="sidebar-sticky">
							<div class="widget toc-widget">
								<h3 class="widget-title">Índice de contenidos</h3>
								<nav id="toc-container" class="toc-nav"></nav>
							</div>
							
							<div class="widget cta-widget">
								<h3 class="widget-title">Reserva tu traslado</h3>
								<p>Vehículos premium, conductores profesionales y atención personalizada.</p>
								<a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>" class="btn btn-solid" style="width:100%; justify-content:center; margin-bottom:12px;">Presupuesto online</a>
								<a href="https://wa.me/34662024136?text=Hola,%20me%20gustar%C3%ADa%20informaci%C3%B3n%20sobre%20un%20traslado%20privado." target="_blank" rel="noopener" class="btn btn-ghost-inv" style="width:100%; justify-content:center; border:2px solid #25D366; color:#25D366;">
									Consultar por WhatsApp
								</a>
							</div>
						</div>
					</aside>

				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const content = document.querySelector('.js-toc-content');
			const tocContainer = document.getElementById('toc-container');
			
			if (content && tocContainer) {
				const headers = content.querySelectorAll('h2, h3');
				if (headers.length > 0) {
					const list = document.createElement('ul');
					headers.forEach(function(header, index) {
						if (!header.id) {
							header.id = 'seccion-' + index;
						}
						const li = document.createElement('li');
						li.className = 'toc-item toc-level-' + header.tagName.toLowerCase();
						const a = document.createElement('a');
						a.href = '#' + header.id;
						a.textContent = header.textContent;
						li.appendChild(a);
						list.appendChild(li);
					});
					tocContainer.appendChild(list);
				} else {
					document.querySelector('.toc-widget').style.display = 'none';
				}
			}
		});
		</script>

		<?php if ( $related_posts->have_posts() ) : ?>
			<section class="single-related-section" aria-labelledby="related-posts-title">
				<div class="container">
					<div class="single-related-header">
						<div>
							<span class="single-related-eyebrow"><?php esc_html_e( 'Sigue leyendo', 'me-transfers' ); ?></span>
							<h2 id="related-posts-title" class="single-related-title"><?php esc_html_e( 'También te puede interesar', 'me-transfers' ); ?></h2>
						</div>
						<a href="<?php echo esc_url( $blog_url ); ?>" class="single-related-link"><?php esc_html_e( 'Ver todo el blog', 'me-transfers' ); ?></a>
					</div>

					<div class="related-grid">
						<?php
						while ( $related_posts->have_posts() ) :
							$related_posts->the_post();

							$related_read_time = max( 1, (int) ceil( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) );
							?>
							<article class="related-card">

								<div class="related-card-body">
									<?php
									$related_categories = get_the_category();
									if ( ! empty( $related_categories ) ) :
										?>
										<span class="single-category-badge single-category-badge--small"><?php echo esc_html( mt_translate( $related_categories[0]->name  )); ?></span>
									<?php endif; ?>

									<h3 class="related-card-title">
										<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
									</h3>

									<p class="related-card-excerpt"><?php echo esc_html( mt_translate( wp_trim_words( get_the_excerpt(), 18, '...' ) ) ); ?></p>

									<div class="related-card-meta">
										<time datetime="<?php echo esc_attr( mt_translate( get_the_date( 'c' ) ) ); ?>"><?php echo esc_html( mt_translate( get_the_date( 'd M Y' ) ) ); ?></time>
										<span><?php echo esc_html( mt_translate( $related_read_time  )); ?> <?php esc_html_e( 'min', 'me-transfers' ); ?></span>
									</div>
								</div>
							</article>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php wp_reset_postdata(); ?>
	</article>

<?php endwhile; ?>

</main>

<?php get_footer(); ?>
