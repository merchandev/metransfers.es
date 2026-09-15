<?php
/**
 * Template for pages and destination landing pages.
 *
 * @package Me_Transfers
 */

get_header();
?>

<main id="primary" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();

		$current_post        = get_post();
		$current_destination = me_transfers_get_current_destination( $current_post );
		if ( me_transfers_is_destinations_hub( $current_post ) ) :
			get_template_part( 'template-parts/page/content', 'destinations-hub' );
		elseif ( me_transfers_is_faq_page( $current_post ) ) :
			get_template_part( 'template-parts/page/content', 'faq' );
		elseif ( 'legal' === get_post_meta( $current_post->ID, '_me_transfers_page_role', true ) || is_page( array( 'privacidad', 'politica-de-privacidad', 'cookie', 'cookies', 'terminos-y-condiciones', 'aviso-legal' ) ) ) :
			get_template_part( 'template-parts/page/content', 'legal' );
		elseif ( $current_destination ) :
			get_template_part( 'template-parts/page/content', 'destination' );
		elseif ( $tour = me_transfers_get_current_tour( $current_post ) ) :
			get_template_part( 'template-parts/page/content', 'tour', array( 'tour' => $tour ) );
		elseif ( $service = me_transfers_get_current_service( $current_post ) ) :
			get_template_part( 'template-parts/page/content', 'service' );
		else :
			get_template_part( 'template-parts/page/content', 'default' );
		endif;
	endwhile;
	?>
</main>

<?php get_footer(); ?>
