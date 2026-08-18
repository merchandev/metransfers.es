<?php
/**
 * Header - Me Transfers Premium
 * @package Me_Transfers
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php /* Robots: controlado por wp_robots filter en functions.php según WP_ENVIRONMENT_TYPE */ ?>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

	<!-- Favicon -->
	<link rel="icon"       type="image/png" sizes="32x32" href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/favicon.png' ); ?>">
	<link rel="icon"       type="image/png" sizes="16x16" href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/favicon.png' ); ?>">
	<link rel="shortcut icon"                              href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/favicon.png' ); ?>">
	<link rel="apple-touch-icon"                           href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/favicon.png' ); ?>">
	<!-- Consent Mode v2 Default Settings -->
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}

	// Comprobar cookie de consentimiento
	var cookieName = 'mt_cookie_consent_v2';
	var hasConsent = document.cookie.split('; ').find(row => row.startsWith(cookieName + '='));
	var analyticsGranted = false;
	var marketingGranted = false;

	if (hasConsent) {
		try {
			var consentData = JSON.parse(decodeURIComponent(hasConsent.split('=')[1]));
			analyticsGranted = consentData.analytics;
			marketingGranted = consentData.marketing;
		} catch(e) {}
	}

	gtag('consent', 'default', {
		'ad_storage': marketingGranted ? 'granted' : 'denied',
		'ad_user_data': marketingGranted ? 'granted' : 'denied',
		'ad_personalization': marketingGranted ? 'granted' : 'denied',
		'analytics_storage': analyticsGranted ? 'granted' : 'denied',
		'wait_for_update': 500
	});
	
	// Para Site Kit u otras implementaciones que usan dataLayer
	dataLayer.push({
		'event': 'default_consent'
	});
	</script>

	<?php wp_head(); ?>
<style>.site-header .btn{min-width:0!important;width:auto!important;padding-inline:clamp(1rem,2vw,1.5rem)!important;flex-shrink:1!important} @media (max-width: 991px) { .site-header .hdr-cta { display: none !important; } .hero-container { padding-top: 0 !important; padding-inline: 0 !important; } .site-header { width: calc(100% - 32px) !important; left: 16px !important; transform: none !important; border-radius: 12px !important; padding: 0 !important; } .site-header .container.header-inner { position: relative !important; display: flex !important; flex-wrap: nowrap !important; justify-content: space-between !important; align-items: center !important; width: 100% !important; margin: 0 !important; padding: 0 16px !important; } .site-branding { position: absolute !important; left: 50% !important; transform: translateX(-50%) !important; margin: 0 !important; } #mob-menu:not(.open) { visibility: hidden !important; transition: visibility 0s 0.3s; } #mob-menu.open { visibility: visible !important; transition: visibility 0s 0s; } } @media (min-width: 992px) { .header-right { align-items: center !important; } .header-right .gtranslate_wrapper, .header-right .gtranslate_wrapper select, .header-right .gtranslate_wrapper a.glink, .header-right .hdr-cta { height: 44px !important; min-height: 44px !important; max-height: 44px !important; display: inline-flex !important; align-items: center !important; box-sizing: border-box !important; margin: 0 !important; } .header-right .hdr-cta { justify-content: center !important; } }</style>
<style>
  /* NUEVO DISEÑO PREMIUM PARA EL FORMULARIO (FONDO VERDE) */
  .hero-booking-card {
      background: linear-gradient(145deg, rgba(5, 33, 20, 0.85) 0%, rgba(3, 22, 12, 0.65) 100%) !important;
      backdrop-filter: blur(12px) !important;
      -webkit-backdrop-filter: blur(12px) !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5) !important;
  }
.hero-booking-card h3, .hero-booking-card label, .hero-booking-card p, .hero-booking-card span {
    color: #ffffff !important;
}
body .hero-booking-card {
    background-color: transparent !important;
}
.hero-badge {
    width: fit-content !important;
    justify-self: flex-start !important;
    align-self: flex-start !important;
}
@media (max-width: 991px) {
    .hero-badge {
        justify-self: center !important;
        align-self: center !important;
    }
}
</style>
<style id="btt-loader-style">
/* Loader BTT: activo solo cuando llega desde la app con ?source=BTT */
.btt-global-loader {
    display: none;
    position: fixed; inset: 0;
    background: linear-gradient(180deg, rgba(0,58,82,.96) 0%, rgba(5,23,61,1) 100%);
    z-index: 999999;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
}
.btt-global-loader.is-active { display: flex; }
.btt-spinner {
    width: 50px; height: 50px;
    border: 4px solid rgba(255,255,255,.2);
    border-top-color: white;
    border-radius: 50%;
    animation: btt-spin 1s linear infinite;
    margin-bottom: 20px;
}
@keyframes btt-spin { 100% { transform: rotate(360deg); } }
</style>
<script>
// Loader BTT — sin document.write
(function() {
    if (window.location.search.indexOf('source=BTT') !== -1) {
        document.documentElement.classList.add('btt-loading');
        var loader = document.createElement('div');
        loader.className = 'btt-global-loader is-active';
        loader.innerHTML = '<div class="btt-spinner"></div><h2 style="font-weight:300;letter-spacing:1px">Calculando su mejor ruta...</h2>';
        document.addEventListener('DOMContentLoaded', function() {
            document.body.appendChild(loader);
            document.getElementById('page') && (document.getElementById('page').style.display = 'none');
        });
    }
}());
</script>

</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Saltar al contenido', 'me-transfers' ); ?></a>

<header id="masthead" class="site-header" role="banner">
	<div class="container header-inner">

		<!-- ① Logo -->
		<div class="site-branding">
			<?php if ( has_custom_logo() ) :
				the_custom_logo();
			else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo-link" rel="home" aria-label="<?php bloginfo( 'name' ); ?>">
					<img
						src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/MT - MeTransfers.png' ); ?>"
						alt="<?php bloginfo( 'name' ); ?>"
						class="site-logo-img"
						width="160"
						height="44"
						loading="eager"
						decoding="async"
					>
				</a>
			<?php endif; ?>
		</div>

		<!-- ② Nav Desktop -->
		<nav class="main-navigation" id="main-nav" aria-label="Menú principal">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'menu-1',
				'menu_id'        => 'primary-menu',
				'menu_class'     => 'nav-menu',
				'container'      => false,
				'fallback_cb'    => 'me_transfers_fallback_menu',
			) );
			?>
		</nav>

		<!-- Selector de idioma (Sistema nativo MT) -->
		<?php gct_render_language_switcher(); ?>

		<!-- Acciones: Botón + Hamburger -->
		<div class="header-right">

			<!-- CTA botón -->
			<a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>" class="btn btn-primary hdr-cta">Reservar Ya</a>

			<!-- Hamburger -->
			<button type="button" class="burger" id="burger-btn" aria-label="Abrir menú" aria-controls="mob-menu" aria-expanded="false">
				<span></span><span></span><span></span>
			</button>

		</div>
	</div><!-- .header-inner -->

</header><!-- #masthead -->

<!-- Drawer mobile (Fuera del header para evitar conflictos con transform) -->
<div id="mob-menu" class="mob-menu" aria-hidden="true">
    <div class="mob-menu-inner">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'menu-1',
            'menu_class'     => 'mob-nav-list',
            'container'      => false,
            'fallback_cb'    => 'me_transfers_fallback_menu',
        ) );
        ?>
        <!-- Logo en el menú mobile (oculto a SEO para no duplicar) -->
        <div class="mob-logo" aria-hidden="true">
            <img
                src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/MT - MeTransfers.png' ); ?>"
                alt=""
                width="130"
                height="36"
                loading="lazy"
            >
        </div>
        <a href="<?php echo esc_url( me_transfers_get_section_url( 'panel' ) ); ?>" class="btn btn-primary mob-menu-cta" tabindex="-1">Reservar Ya</a>
    </div>
</div>
<div id="mob-overlay" class="mob-overlay" aria-hidden="true"></div>
