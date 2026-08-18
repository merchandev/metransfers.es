<?php
/**
 * Template part for displaying service content
 */

defined( 'ABSPATH' ) || exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('service-content section'); ?>>
	<div class="container">
		<header class="service-content__header section-header gs-reveal">
			<h1 class="section-title"><?php the_title(); ?></h1>
		</header>

		<div class="service-content__body gs-reveal">
			<?php the_content(); ?>
		</div>
		
		<div class="service-content__action gs-reveal text-center mt-5">
			<a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>" class="btn btn-primary">Reservar Este Servicio</a>
		</div>
	</div>
</article>
