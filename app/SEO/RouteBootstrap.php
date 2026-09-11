<?php
namespace MeTransfers\SEO;

final class RouteBootstrap {
	private const VERSION     = '2026-09-11-v2';
	private const OPTION      = 'mt_seo_route_bootstrap_version';
	private const LOCK_OPTION = 'mt_seo_route_bootstrap_lock';

	public function register(): void {
		add_action( 'init', array( __CLASS__, 'ensureCanonicalRoutes' ), 25 );
	}

	/**
	 * Canonical routes required to absorb legacy destination URLs that still
	 * have historical Google signals. Existing routes are never overwritten;
	 * missing SEO/route metadata is repaired in place.
	 *
	 * @return array<string,string>
	 */
	public static function catalog(): array {
		return array(
			'barcelona-portaventura'            => 'PortAventura',
			'barcelona-costa-brava'             => 'Costa Brava',
			'barcelona-perpignan'               => 'Perpignan',
			'barcelona-granollers'              => 'Granollers',
			'barcelona-mataro'                  => 'Mataró',
			'barcelona-badalona'                => 'Badalona',
			'barcelona-hospitalet'              => "L'Hospitalet de Llobregat",
			'barcelona-pineda-de-mar'           => 'Pineda de Mar',
			'barcelona-sevilla'                 => 'Sevilla',
			'barcelona-vigo'                    => 'Vigo',
			'barcelona-benidorm'                => 'Benidorm',
			'barcelona-bilbao'                  => 'Bilbao',
			'barcelona-san-sebastian'           => 'San Sebastián',
			'barcelona-malgrat'                 => 'Malgrat de Mar',
			'barcelona-granada'                 => 'Granada',
			'barcelona-valencia'                => 'Valencia',
			'barcelona-santiago-de-compostela'  => 'Santiago de Compostela',
			'barcelona-lourdes'                 => 'Lourdes',
			'barcelona-vall-de-nuria'           => 'Vall de Núria',
			'barcelona-almeria'                 => 'Almería',
			'barcelona-figueres'                => 'Figueres',
			'barcelona-camping-el-delfin-verde' => 'Camping El Delfín Verde',
			'barcelona-marbella'                => 'Marbella',
			'barcelona-santa-susanna'           => 'Santa Susanna',
			'barcelona-begur'                   => 'Begur',
			'barcelona-calella-de-palafrugell'  => 'Calella de Palafrugell',
			'barcelona-cap-de-creus'            => 'Cap de Creus',
			'barcelona-la-escala'               => "L'Escala",
			'barcelona-palamos'                 => 'Palamós',
			'barcelona-madrid'                  => 'Madrid',
			'barcelona-vielha'                  => 'Vielha',
			'barcelona-peniscola'               => 'Peñíscola',
			'barcelona-delta-del-ebro'          => 'Delta del Ebro',
			'barcelona-taull'                   => 'Taüll',
			'barcelona-besalu'                  => 'Besalú',
			'barcelona-morella'                 => 'Morella',
			'barcelona-altea'                   => 'Altea',
			'barcelona-valderrobres'            => 'Valderrobres',
			'barcelona-alquezar'                => 'Alquézar',
			'barcelona-collioure'               => 'Collioure',
			'barcelona-carcassonne'             => 'Carcassonne',
		);
	}

	public static function ensureCanonicalRoutes(): void {
		if ( self::VERSION === (string) get_option( self::OPTION, '' ) ) {
			return;
		}
		if ( ! post_type_exists( 'ruta' ) ) {
			return;
		}

		// Evita carreras cuando varias peticiones llegan justo después del deploy.
		$lock_time = (int) get_option( self::LOCK_OPTION, 0 );
		if ( $lock_time > 0 && ( time() - $lock_time ) < 120 ) {
			return;
		}
		update_option( self::LOCK_OPTION, time(), false );

		$created = 0;
		foreach ( self::catalog() as $slug => $destination ) {
			$existing = get_page_by_path( $slug, OBJECT, 'ruta' );
			if ( $existing ) {
				self::repairRouteMetadata( (int) $existing->ID, $destination );
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'ruta',
					'post_status'  => 'publish',
					'post_name'    => $slug,
					'post_title'   => 'Barcelona - ' . $destination,
					'post_content' => sprintf(
						'Traslado privado desde Barcelona a %1$s con recogida en hotel, domicilio, estación, puerto o aeropuerto. MeTransfers organiza el servicio puerta a puerta con vehículo privado y conductor profesional. Confirma pasajeros, equipaje, fecha y punto exacto de recogida antes de reservar.',
						$destination
					),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			self::repairRouteMetadata( (int) $post_id, $destination );
			++$created;
		}

		update_option( self::OPTION, self::VERSION, false );
		delete_option( self::LOCK_OPTION );
		if ( $created > 0 ) {
			flush_rewrite_rules( false );
		}
	}

	private static function repairRouteMetadata( int $post_id, string $destination ): void {
		$meta = array(
			'_mt_ruta_origen'       => 'Barcelona centro',
			'_mt_ruta_destino'      => $destination,
			'_mt_ruta_h1'           => 'Traslado privado de Barcelona a ' . $destination,
			'_mt_route_bootstrap'   => self::VERSION,
			'_yoast_wpseo_title'    => 'Transfer Barcelona - ' . $destination . ' | MeTransfers',
			'_yoast_wpseo_metadesc' => sprintf(
				'Transfer privado de Barcelona a %1$s con recogida puerta a puerta, vehículo privado y chófer profesional. Reserva con MeTransfers.',
				$destination
			),
		);

		foreach ( $meta as $key => $value ) {
			if ( '' === (string) get_post_meta( $post_id, $key, true ) ) {
				update_post_meta( $post_id, $key, $value );
			}
		}

		// Nunca reactivamos una ruta deshabilitada explícitamente por un editor.
		if ( '' === (string) get_post_meta( $post_id, '_mt_seo_ready', true ) ) {
			update_post_meta( $post_id, '_mt_seo_ready', '1' );
		}
	}
}
