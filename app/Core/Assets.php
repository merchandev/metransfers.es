<?php
namespace MeTransfers\Core;

class Assets {
	public function register() {
		// Run after plugins so the integrated booking design wins the cascade
		// while an old external booking plugin is being removed from a site.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 100 );
	}

	public function enqueue() {

		if ( \MeTransfers\HotelPortal\HotelPortal::isPortalRequest() ) {
			return;
		}

		$site_tracking_path = get_template_directory() . '/assets/js/site-tracking.js';
		if ( file_exists( $site_tracking_path ) ) {
			wp_enqueue_script(
				'mt-site-tracking',
				get_template_directory_uri() . '/assets/js/site-tracking.js',
				array(),
				(string) filemtime( $site_tracking_path ),
				true
			);
		}

		if ( ! self::is_booking_context() ) {
			return;
		}

		$base_dir = get_template_directory() . '/assets/css/';
		$base_url = get_template_directory_uri() . '/assets/css/';

		$phase  = self::booking_phase();
		$styles = array(
			'mt-tokens'     => 'tokens.css',
			'mt-components' => 'components.css',
		);
		if ( in_array( $phase, array( 'search', 'vehicle', 'details', 'confirmation', 'hotel' ), true ) ) {
			$styles['mt-booking'] = 'booking.css';
		}
		if ( 'payment' === $phase ) {
			$styles['mt-checkout'] = 'checkout.css';
		}

		$dependency = array();
		foreach ( $styles as $handle => $filename ) {
			$path = $base_dir . $filename;
			if ( ! file_exists( $path ) ) {
				continue;
			}

			wp_enqueue_style( $handle, $base_url . $filename, $dependency, (string) filemtime( $path ) );
			$dependency = array( $handle );
		}

		$tracking_path = get_template_directory() . '/assets/js/booking-tracking.js';
		if ( file_exists( $tracking_path ) ) {
			wp_enqueue_script(
				'mt-booking-tracking',
				get_template_directory_uri() . '/assets/js/booking-tracking.js',
				wp_script_is( 'mt-site-tracking', 'enqueued' ) ? array( 'mt-site-tracking' ) : array(),
				(string) filemtime( $tracking_path ),
				true
			);
		}
	}

	public static function is_booking_context() {
		return 'none' !== self::booking_phase();
	}

	public static function booking_phase() {
		if ( is_admin() ) {
			return 'none';
		}

		// ── Detección para páginas traducidas (i18n) ─────────────────────────
		// Cuando el Router i18n sirve una URL como /en/seleccionar-vehiculo/,
		// WordPress carga un virtual post y is_page() devuelve false.
		// Leemos mt_page (query var del router) para detectar el contexto correcto.
		$mt_lang = (string) get_query_var( 'mt_lang', '' );
		if ( $mt_lang && 'es' !== $mt_lang ) {
			$mt_page = trim( (string) get_query_var( 'mt_page', '' ), '/' );
			if ( '' === $mt_page || 'home' === $mt_page ) {
				return 'search';
			}
			// Mapa de páginas i18n a fases de booking
			$i18n_phase_map = array(
				'seleccionar-vehiculo' => 'vehicle',
				'reservas-metransfers' => 'details',
				'pago'                 => 'payment',
				'reservas-hotel'       => 'hotel',
				'reservaciones'        => 'search',
			);
			if ( isset( $i18n_phase_map[ $mt_page ] ) ) {
				// Confirmation: reservas-metransfers con payment_result en GET
				if ( 'reservas-metransfers' === $mt_page && isset( $_GET['payment_result'] ) ) {
					return 'confirmation';
				}
				return $i18n_phase_map[ $mt_page ];
			}
			// Páginas de servicio y SEO: todas son contexto de búsqueda
			$search_pages = array(
				'aeropuerto-barcelona',
				'traslados-aeropuerto',
				'puerto-barcelona',
				'traslados-puerto',
				'conductor-privado',
				'chofer-por-horas',
				'traslados-corporativos',
				'corporativo-y-eventos',
				'tours-privados',
				'bodas-eventos',
				'grupos',
				'flota',
				'taxis-privado-barcelona',
			);
			if ( in_array( $mt_page, $search_pages, true )
				|| 0 === strpos( $mt_page, 'taxis-barcelona-' )
				|| 0 === strpos( $mt_page, 'traslados-barcelona-' ) ) {
				return 'search';
			}
			// Para páginas i18n no mapeadas, verificar el post real hidratado
			// (el dispatch() ya lo resolvió; intentamos leer el post actual)
			$post = get_post();
			if ( $post && is_string( $post->post_content ) ) {
				$search_shortcodes = array(
					'wptb_booking_form',
					'wptb_booking',
					'wptb_popular_destinations_carousel',
					'wptb_popular_destinations',
					'wptb_booking_popup',
					'premium_transfers_search',
				);
				foreach ( $search_shortcodes as $sc ) {
					if ( has_shortcode( $post->post_content, $sc ) ) {
						return 'search';
					}
				}
			}
		}
		// ── Fin detección i18n ────────────────────────────────────────────────

		if ( is_page( 'reservas-metransfers' ) && isset( $_GET['payment_result'] ) ) {
			return 'confirmation';
		}

		if ( is_front_page() ) {
			return 'search';
		}

		if ( is_page( 'seleccionar-vehiculo' ) ) {
			return 'vehicle';
		}

		if ( is_page( 'reservas-metransfers' ) ) {
			return 'details';
		}

		if ( is_page( 'pago' ) ) {
			return 'payment';
		}

		if ( is_page( 'reservas-hotel' ) ) {
			return 'hotel';
		}

		if ( is_singular( 'ruta' ) ) {
			return 'search';
		}

		if ( is_page_template( array( 'template-madre.php', 'page-seo-dynamic.php' ) ) ) {
			return 'search';
		}

		if ( ! is_singular() ) {
			return 'none';
		}

		$post = get_post();
		if ( ! $post || ! is_string( $post->post_content ) ) {
			return 'none';
		}

		if ( 'page' === $post->post_type
			&& ( 0 === strpos( $post->post_name, 'taxis-' ) || 'reservaciones' === $post->post_name ) ) {
			return 'search';
		}

		$phases = array(
			'confirmation' => array(),
			'payment'      => array( 'wptb_checkout', 'wptb_stripe_checkout', 'wptb_redsys_checkout' ),
			'details'      => array( 'wptb_booking_details' ),
			'vehicle'      => array( 'wptb_vehicle_selection' ),
			'hotel'        => array( 'hqp_booking_form' ),
			'search'       => array( 'wptb_booking_form', 'wptb_booking', 'wptb_popular_destinations_carousel', 'wptb_popular_destinations', 'wptb_booking_popup', 'premium_transfers_search' ),
		);

		foreach ( $phases as $phase => $shortcodes ) {
			foreach ( $shortcodes as $shortcode ) {
				if ( has_shortcode( $post->post_content, $shortcode ) ) {
					return $phase;
				}
			}
		}

		return 'none';
	}
}
