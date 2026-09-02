<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hotel_id      = isset( $hotel_id ) ? absint( $hotel_id ) : 0;
$hotel_ids     = isset( $hotel_ids ) ? array_map( 'absint', (array) $hotel_ids ) : array();
$portal_user   = wp_get_current_user();
$display_name  = (string) $portal_user->display_name;
$avatar_letter = function_exists( 'mb_substr' ) ? mb_substr( $display_name, 0, 1, 'UTF-8' ) : substr( $display_name, 0, 1 );
?>
<header class="mt-hotel-topbar">
	<button class="mt-hotel-menu-toggle" type="button" aria-controls="mt-hotel-sidebar" aria-expanded="false">
		<span aria-hidden="true">☰</span>
		<span class="screen-reader-text"><?php esc_html_e( 'Abrir menú', 'me-transfers' ); ?></span>
	</button>
	<?php if ( count( $hotel_ids ) > 1 ) : ?>
		<form method="post" class="mt-hotel-switcher">
			<?php wp_nonce_field( 'mt_hotel_switch', 'mt_hotel_switch_nonce' ); ?>
			<input type="hidden" name="mt_hotel_switch" value="1">
			<label for="mt-hotel-current"><?php esc_html_e( 'Hotel actual', 'me-transfers' ); ?></label>
			<select id="mt-hotel-current" name="hotel_id" data-auto-submit>
				<?php foreach ( $hotel_ids as $assigned_hotel_id ) : ?>
					<option value="<?php echo esc_attr( (string) $assigned_hotel_id ); ?>" <?php selected( $hotel_id, $assigned_hotel_id ); ?>><?php echo esc_html( get_the_title( $assigned_hotel_id ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<noscript><button type="submit"><?php esc_html_e( 'Cambiar', 'me-transfers' ); ?></button></noscript>
		</form>
	<?php else : ?>
		<div class="mt-hotel-current-name">
			<span><?php esc_html_e( 'Hotel actual', 'me-transfers' ); ?></span>
			<strong><?php echo esc_html( get_the_title( $hotel_id ) ); ?></strong>
		</div>
	<?php endif; ?>
	<div class="mt-hotel-user">
		<span class="mt-hotel-avatar" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $avatar_letter, 'UTF-8' ) : strtoupper( $avatar_letter ) ); ?></span>
		<span><?php echo esc_html( $portal_user->display_name ); ?></span>
	</div>
</header>
