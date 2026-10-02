<?php
namespace MeTransfers\SEO;

/** Shared validation for the admin screen and WP-CLI. Never writes during validation. */
final class VariantApproval {
	public static function prepare( array $rows, bool $require_editorial = true ): array {
		$errors   = array();
		$prepared = array();
		$seen     = array();
		if ( ! $rows ) {
			$errors[] = 'Selecciona al menos una página.';
		}
		foreach ( $rows as $row ) {
			$post = get_post( $row['id'] ?? 0 );
			$lang = (string) ( $row['language'] ?? '' );
			$key  = (int) ( $row['id'] ?? 0 ) . ':' . $lang;
			if ( isset( $seen[ $key ] ) ) {
				$errors[] = 'Variante repetida: ' . $key;
				continue;
			}
			$seen[ $key ] = true;
			if ( ! $post || ! Indexability::isIndexable( $post ) ) {
				$errors[] = 'Contenido no publicado o no apto para indexación: ' . $key;
				continue;
			}
			if ( 'es' === $lang || ! Indexability::isIndexableLanguage( $lang ) ) {
				$errors[] = 'Idioma no apto para aprobar una variante: ' . $key;
				continue;
			}
			if ( $require_editorial && true !== ( $row['editorial_approved'] ?? false ) ) {
				$errors[] = 'Falta confirmar la revisión editorial: ' . $key;
				continue;
			}
			$canonical = Variants::canonical( $post, $lang );
			$hash      = Variants::fingerprint( $post );
			if ( isset( $row['source_hash'] ) && $hash !== $row['source_hash'] ) {
				$errors[] = 'El contenido cambió desde que se abrió la revisión: ' . $key;
				continue;
			}
			$response = wp_remote_get(
				$canonical,
				array(
					'timeout'     => 15,
					'redirection' => 0,
				)
			);
			$status   = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
			if ( 200 !== $status ) {
				$errors[] = 'La variante debe responder 200 sin redirecciones (' . $status . '): ' . $canonical;
				continue;
			}
			$prepared[] = array(
				'id'       => (int) $post->ID,
				'language' => $lang,
				'approval' => array(
					'schema_version'      => Variants::SCHEMA_VERSION,
					'translated_reviewed' => true,
					'http_status'         => $status,
					'canonical'           => $canonical,
					'source_hash'         => $hash,
					'approved_at'         => gmdate( 'c' ),
					'approved_by'         => get_current_user_id(),
				),
			);
		}
		// An invalid batch must not expose individually valid rows for writing.
		return array(
			'errors'   => $errors,
			'variants' => $errors ? array() : $prepared,
		);
	}

	public static function apply( array $batch ): array {
		if ( ! empty( $batch['errors'] ) || empty( $batch['variants'] ) ) {
			return ! empty( $batch['errors'] ) ? $batch['errors'] : array( 'Lote vacío.' );
		}
		// Recheck all sources before the first write, including edits during HTTP checks.
		foreach ( $batch['variants'] as $row ) {
			if ( ! Indexability::isIndexable( $row['id'] )
				|| ! Indexability::isIndexableLanguage( $row['language'] )
				|| 'es' === $row['language']
				|| Variants::canonical( $row['id'], $row['language'] ) !== $row['approval']['canonical']
				|| Variants::fingerprint( $row['id'] ) !== $row['approval']['source_hash'] ) {
				return array( 'El contenido cambió durante la revisión. Revisa de nuevo el lote.' );
			}
		}
		$written = array();
		foreach ( $batch['variants'] as $row ) {
			$key       = '_mt_seo_variant_' . $row['language'];
			$previous  = get_post_meta( $row['id'], $key, true );
			$written[] = array(
				'id'       => $row['id'],
				'key'      => $key,
				'previous' => $previous,
			);
			update_post_meta( $row['id'], $key, $row['approval'] );
			if ( get_post_meta( $row['id'], $key, true ) !== $row['approval'] ) {
				$errors = array( 'No se pudo guardar la aprobación. Se revierte el lote.' );
				foreach ( array_reverse( $written ) as $change ) {
					if ( '' === $change['previous'] ) {
						delete_post_meta( $change['id'], $change['key'] );
					} else {
						update_post_meta( $change['id'], $change['key'], $change['previous'] );
					}
					if ( get_post_meta( $change['id'], $change['key'], true ) !== $change['previous'] ) {
						$errors[] = 'La restauración requiere revisión: ' . $change['id'];
					}
				}
				return $errors;
			}
		}
		return array();
	}
}
