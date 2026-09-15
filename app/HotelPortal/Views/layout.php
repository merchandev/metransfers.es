<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title           = isset( $page_title ) ? (string) $page_title : 'MeTransfers';
$content_view         = isset( $content_view ) && is_string( $content_view ) ? $content_view : '';
$portal_authenticated = is_user_logged_in() && ! empty( $hotel_id ) && isset( $hotel_ids );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow,noarchive">
	<title><?php echo esc_html( $page_title ); ?> · MeTransfers</title>
	<?php wp_head(); ?>
</head>
<body class="mt-hotel-portal-body">
	<a class="mt-hotel-skip-link" href="#mt-hotel-main"><?php esc_html_e( 'Saltar al contenido', 'me-transfers' ); ?></a>
	<?php if ( $portal_authenticated ) : ?>
		<div class="mt-hotel-shell">
			<?php include __DIR__ . '/partials/sidebar.php'; ?>
			<button class="mt-hotel-drawer-overlay" type="button" aria-label="<?php esc_attr_e( 'Cerrar menú', 'me-transfers' ); ?>" tabindex="-1"></button>
			<div class="mt-hotel-workspace">
				<?php include __DIR__ . '/partials/topbar.php'; ?>
				<main id="mt-hotel-main" class="mt-hotel-main" tabindex="-1">
					<?php include $content_view; ?>
				</main>
			</div>
		</div>
	<?php else : ?>
		<main id="mt-hotel-main" class="mt-hotel-auth-main" tabindex="-1">
			<?php include $content_view; ?>
		</main>
	<?php endif; ?>
	<?php wp_footer(); ?>
</body>
</html>
