<?php

declare(strict_types=1);

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'MT_LANGS' ) ) {
	define(
		'MT_LANGS',
		array(
			'es' => array(
				'label'       => 'ES',
				'name'        => 'Español',
				'google_code' => 'es',
			),
			'en' => array(
				'label'       => 'EN',
				'name'        => 'English',
				'google_code' => 'en',
			),
			'zh' => array(
				'label'       => 'ZH',
				'name'        => '中文',
				'google_code' => 'zh-CN',
			),
		)
	);
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( string $path = '' ): string {
		return 'https://example.test' . $path;
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( string $scheme = 'auth' ): string {
		return 'unit-test-' . $scheme . '-salt';
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return (int) ( $GLOBALS['mt_test_current_user_id'] ?? 0 );
	}
}

if ( ! function_exists( 'user_can' ) ) {
	function user_can( $user_id, $capability ): bool {
		return ! empty( $GLOBALS['mt_test_user_caps'][ (int) $user_id ][ (string) $capability ] );
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = array() ): array {
		if ( isset( $GLOBALS['mt_test_get_posts'] ) && is_callable( $GLOBALS['mt_test_get_posts'] ) ) {
			return (array) call_user_func( $GLOBALS['mt_test_get_posts'], $args );
		}
		return array();
	}
}

if ( ! function_exists( 'get_users' ) ) {
	function get_users( $args = array() ): array {
		return (array) ( $GLOBALS['mt_test_users'] ?? array() );
	}
}

if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key, $single = false ) {
		return $GLOBALS['mt_test_user_meta'][ (int) $user_id ][ (string) $key ] ?? '';
	}
}

if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $key, $value ): bool {
		if ( isset( $GLOBALS['mt_test_user_meta'][ (int) $user_id ][ (string) $key ] )
			&& $GLOBALS['mt_test_user_meta'][ (int) $user_id ][ (string) $key ] === $value ) {
			return false;
		}
		$GLOBALS['mt_test_user_meta'][ (int) $user_id ][ (string) $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key, $single = false ) {
		return $GLOBALS['mt_test_post_meta'][ (int) $post_id ][ (string) $key ] ?? '';
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( $post_id ): string {
		return (string) ( $GLOBALS['mt_test_post_types'][ (int) $post_id ] ?? '' );
	}
}

if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( $post_id ): string {
		return (string) ( $GLOBALS['mt_test_post_statuses'][ (int) $post_id ] ?? '' );
	}
}

if ( ! function_exists( 'status_header' ) ) {
	function status_header( $code ): void {
		$GLOBALS['mt_test_status_header'] = (int) $code;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ): string {
		return trim( strip_tags( (string) $value ) );
	}
}
