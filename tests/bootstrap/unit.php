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
			'es' => array( 'label' => 'ES', 'name' => 'Español', 'google_code' => 'es' ),
			'en' => array( 'label' => 'EN', 'name' => 'English', 'google_code' => 'en' ),
			'zh' => array( 'label' => 'ZH', 'name' => '中文', 'google_code' => 'zh-CN' ),
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
