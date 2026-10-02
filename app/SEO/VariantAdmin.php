<?php
namespace MeTransfers\SEO;

final class VariantAdmin {
	public function register() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	public static function menu() {
		add_management_page( 'Revisión SEO de idiomas', 'Revisión SEO de idiomas', \MeTransfers\Admin\Capabilities::MANAGE_INTEGRATIONS, 'mt-seo-variants', array( __CLASS__, 'render' ) );
	}

	public static function render() {
		if ( ! current_user_can( \MeTransfers\Admin\Capabilities::MANAGE_INTEGRATIONS ) ) {
			wp_die( esc_html__( 'No tienes permiso para revisar variantes SEO.', 'me-transfers' ) );
		}
		$errors = array();
		$saved  = 0;
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			check_admin_referer( 'mt_seo_variants' );
			$ids      = array_map( 'absint', (array) wp_unslash( $_POST['variant_ids'] ?? array() ) );
			$hashes   = (array) wp_unslash( $_POST['source_hash'] ?? array() );
			$language = sanitize_key( wp_unslash( $_POST['language'] ?? '' ) );
			$reviewed = '1' === ( $_POST['editorial_reviewed'] ?? '' );
			$rows     = array();
			if ( count( $ids ) > 10 ) {
				$errors[] = 'Revisa como máximo 10 variantes por lote.';
			}
			foreach ( $ids as $id ) {
				if ( ! current_user_can( 'edit_post', $id ) ) {
					$errors[] = 'No tienes permiso para editar el contenido ' . $id;
				}
				$rows[] = array(
					'id'                 => $id,
					'language'           => $language,
					'source_hash'        => (string) ( $hashes[ $id ] ?? '' ),
					'editorial_approved' => $reviewed,
				);
			}
			if ( ! $errors ) {
				$batch  = VariantApproval::prepare( $rows );
				$errors = VariantApproval::apply( $batch );
				$saved  = $errors ? 0 : count( $batch['variants'] );
			}
			if ( $saved ) {
				// Let installed caches invalidate public pages and Yoast rebuild its sitemap.
				do_action( 'wpseo_sitemap_cache_clear' );
				if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
					sg_cachepress_purge_cache();
				}
			}
		}
		echo '<div class="wrap"><h1>Revisión SEO de idiomas</h1>';
		if ( $saved ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $saved . ' variante(s) aprobadas para indexación.' ) . '</p></div>';
		}
		foreach ( $errors as $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}
		echo '<p>Abre cada versión y revisa el título, contenido, navegación y metadatos. Aprueba solo traducciones completas. Se comprueba una respuesta HTTP 200 sin redirecciones antes de guardar.</p>';
		$languages = defined( 'MT_SEO_LANGS' ) ? MT_SEO_LANGS : array();
		$posts     = get_posts(
			array(
				'post_type'      => array( 'page', 'post', 'ruta' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'has_password'   => false,
			)
		);
		foreach ( $languages as $language ) {
			if ( 'es' === $language || ! Indexability::isIndexableLanguage( $language ) ) {
				continue;
			}
			echo '<h2>' . esc_html( strtoupper( $language ) ) . '</h2><form method="post">';
			wp_nonce_field( 'mt_seo_variants' );
			echo '<input type="hidden" name="language" value="' . esc_attr( $language ) . '">';
			echo '<table class="widefat striped"><thead><tr><th>Revisada</th><th>Contenido</th><th>Versión</th><th>Estado SEO</th></tr></thead><tbody>';
			foreach ( $posts as $post ) {
				if ( ! Indexability::isIndexable( $post ) || ! current_user_can( 'edit_post', $post->ID ) ) {
					continue;
				}
				$approved = Variants::isApproved( $post, $language );
				echo '<tr><td><input type="checkbox" name="variant_ids[]" value="' . esc_attr( (string) $post->ID ) . '" aria-label="' . esc_attr( 'Revisada: ' . $post->post_title ) . '">';
				echo '<input type="hidden" name="source_hash[' . esc_attr( (string) $post->ID ) . ']" value="' . esc_attr( Variants::fingerprint( $post ) ) . '"></td>';
				echo '<td>' . esc_html( $post->post_title ) . ' <small>(' . esc_html( $post->post_type . ', ID ' . $post->ID ) . ')</small></td>';
				echo '<td><a href="' . esc_url( Variants::canonical( $post, $language ) ) . '" target="_blank" rel="noopener">Revisar ' . esc_html( strtoupper( $language ) ) . '</a></td>';
				echo '<td>' . esc_html( $approved ? 'Aprobada' : 'Pendiente / noindex' ) . '</td></tr>';
			}
			echo '</tbody></table><p><label><input type="checkbox" name="editorial_reviewed" value="1"> Confirmo que he revisado las traducciones seleccionadas y están completas.</label></p>';
			submit_button( 'Aprobar las variantes revisadas' );
			echo '</form>';
		}
		echo '</div>';
	}
}
