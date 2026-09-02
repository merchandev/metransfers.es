<?php
/**
 * Template part for displaying generic page content
 */

defined( 'ABSPATH' ) || exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('page-content generic-page section'); ?>>
	<div class="container">
		<header class="page-content__header section-header gs-reveal">
			<h1 class="section-title"><?php the_title(); ?></h1>
		</header>

		<div class="page-content__body gs-reveal">
			<?php
			the_content();

			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'me-transfers' ),
				'after'  => '</div>',
			) );
			?>
		</div>
	</div>
</article>