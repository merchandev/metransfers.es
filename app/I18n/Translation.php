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

	/**
	 * Build the complete source catalog used by the admin prebuilder.
	 *
	 * Booking labels are included explicitly, while literal mt_translate()
	 * calls are discovered from theme PHP files. Published WordPress content is
	 * included because the_content/the_title are translated from the same cache.
	 */
	public static function sourceCatalog() {
		$texts = array_values( \MeTransfers\Booking\I18n::sourceStrings() );
		$root  = function_exists( 'get_template_directory' ) ? get_template_directory() : dirname( __DIR__, 2 );

		if ( is_dir( $root ) ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $iterator as $file ) {
				$path = $file->getPathname();
				if ( 'php' !== strtolower( $file->getExtension() )
					|| preg_match( '#[\\\\/](?:vendor|node_modules|tests|\.git)[\\\\/]#', $path )
					|| $file->getSize() > 2 * 1024 * 1024 ) {
					continue;
				}

				$source = file_get_contents( $path );
				if ( false === $source || false === strpos( $source, 'mt_translate' ) ) {
					continue;
				}
				$texts = array_merge( $texts, self::literalTranslateArguments( $source ) );
			}
		}

		if ( function_exists( 'get_posts' ) ) {
			$posts = get_posts(
				array(
					'post_type'      => 'any',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);
			foreach ( $posts as $post ) {
				foreach ( array( 'post_title', 'post_excerpt', 'post_content' ) as $property ) {
					if ( isset( $post->{$property} ) ) {
						$texts[] = $post->{$property};
					}
				}
				
				// Yoast SEO Meta
				$yoast_title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
				if ( $yoast_title ) {
					$texts[] = $yoast_title;
				}
				$yoast_desc = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
				if ( $yoast_desc ) {
					$texts[] = $yoast_desc;
				}
			}
		}

		$texts = array_map( 'trim', array_filter( $texts, 'is_string' ) );
		$texts = array_filter( $texts );

		return array_values( array_unique( $texts ) );
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

	private static function literalTranslateArguments( $source ) {
		$tokens  = token_get_all( $source );
		$results = array();
		$count   = count( $tokens );

		for ( $index = 0; $index < $count; $index++ ) {
			$token = $tokens[ $index ];
			if ( ! is_array( $token ) || T_STRING !== $token[0] || 'mt_translate' !== strtolower( $token[1] ) ) {
				continue;
			}

			$cursor = $index + 1;
			while ( $cursor < $count && is_array( $tokens[ $cursor ] ) && in_array( $tokens[ $cursor ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				++$cursor;
			}
			if ( $cursor >= $count || '(' !== $tokens[ $cursor ] ) {
				continue;
			}
			++$cursor;
			while ( $cursor < $count && is_array( $tokens[ $cursor ] ) && in_array( $tokens[ $cursor ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				++$cursor;
			}
			if ( $cursor < $count && is_array( $tokens[ $cursor ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $cursor ][0] ) {
				$results[] = self::decodePhpStringLiteral( $tokens[ $cursor ][1] );
			}
		}

		return $results;
	}

	private static function decodePhpStringLiteral( $literal ) {
		$quote = substr( $literal, 0, 1 );
		$value = substr( $literal, 1, -1 );
		if ( "'" === $quote ) {
			return strtr(
				$value,
				array(
					'\\\\' => '\\',
					"\\'"  => '\'',
				)
			);
		}
		return stripcslashes( $value );
	}

	private static function cacheKey( $text, $language ) {
		return 'mt_tr_' . $language . '_' . md5( (string) $text );
	}
}
