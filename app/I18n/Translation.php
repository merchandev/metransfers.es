<?php
namespace MeTransfers\I18n;

use MeTransfers\Admin\Capabilities;
use MeTransfers\Core\Settings;

final class Translation {
	public static function register() {
		add_filter( 'the_content', array( __CLASS__, 'translate' ), 99 );
		add_filter( 'the_title', array( __CLASS__, 'translateTitle' ), 99, 2 );
		add_filter( 'the_excerpt', array( __CLASS__, 'translate' ), 99 );
		add_filter( 'get_the_excerpt', array( __CLASS__, 'translate' ), 99 );
	}

	public static function translate( $text, $language = '' ) {
		$language = self::resolveLanguage( $language );
		if ( 'es' === $language || '' === trim( (string) $text ) ) {
			return $text;
		}

		$cache_key = self::cacheKey( $text, $language );
		$cached    = wp_cache_get( $cache_key, 'mt_i18n' );
		if ( false === $cached ) {
			$cached = get_option( $cache_key, null );
			if ( null !== $cached ) {
				wp_cache_set( $cache_key, $cached, 'mt_i18n', 3600 );
			}
		}
		return null !== $cached && false !== $cached ? $cached : $text;
	}

	public static function translateTitle( $title, $post_id = null ) {
		return self::translate( $title );
	}

	public static function batch( array $texts, $language = '' ) {
		$language = self::resolveLanguage( $language );
		if ( 'es' === $language || empty( $texts ) ) {
			return $texts;
		}

		$results = array();
		foreach ( $texts as $index => $text ) {
			$results[ $index ] = self::translate( $text, $language );
		}
		ksort( $results );
		return $results;
	}

	public static function remoteBatch( array $texts, $language ) {
		if ( ! is_admin()
			|| ! current_user_can( Capabilities::MANAGE_INTEGRATIONS )
			|| 'es' === $language
			|| ! defined( 'MT_LANGS' )
			|| ! isset( MT_LANGS[ $language ] ) ) {
			return array();
		}

		$api_key = trim( (string) Settings::get( 'translation_api_key', '' ) );
		if ( '' === $api_key ) {
			return array();
		}

		$results = array();
		foreach ( array_chunk( $texts, 100, true ) as $chunk ) {
			$response = wp_remote_post(
				'https://translation.googleapis.com/language/translate/v2',
				array(
					'headers' => array(
						'Content-Type'   => 'application/json',
						'X-Goog-Api-Key' => $api_key,
					),
					'body'    => wp_json_encode(
						array(
							'q'      => array_values( $chunk ),
							'source' => 'es',
							'target' => MT_LANGS[ $language ]['google_code'],
							'format' => 'html',
						)
					),
					'timeout' => 30,
				)
			);
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}

			$body         = json_decode( wp_remote_retrieve_body( $response ), true );
			$translations = isset( $body['data']['translations'] ) ? $body['data']['translations'] : array();
			$keys         = array_keys( $chunk );
			foreach ( $translations as $index => $translation ) {
				if ( ! isset( $keys[ $index ], $translation['translatedText'] ) ) {
					continue;
				}
				$key       = $keys[ $index ];
				$decoded   = html_entity_decode( $translation['translatedText'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$cache_key = self::cacheKey( $chunk[ $key ], $language );
				update_option( $cache_key, $decoded, false );
				wp_cache_set( $cache_key, $decoded, 'mt_i18n', 3600 );
				$results[ $key ] = $decoded;
			}
		}
		return $results;
	}

	private static function resolveLanguage( $language ) {
		return $language && defined( 'MT_LANGS' ) && isset( MT_LANGS[ $language ] )
			? $language
			: Language::get();
	}

	private static function cacheKey( $text, $language ) {
		return 'mt_tr_' . $language . '_' . md5( (string) $text );
	}
}
