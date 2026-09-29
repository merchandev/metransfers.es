<?php

define( 'WPTB_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/app/Legacy/WPTB/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'OBJECT', 'OBJECT' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'MT_PLATFORM_VERSION', 'test' );

define( 'MT_ACTIVE_LANGS', array( 'es' ) );
define( 'MT_SEO_LANGS', array( 'es' ) );
define(
	'MT_LANGS',
	array(
		'es' => array( 'label' => 'ES', 'name' => 'Español (España)', 'locale' => 'es_ES', 'hreflang' => 'es-ES', 'google_code' => 'es' ),
		'en' => array( 'label' => 'EN', 'name' => 'English', 'locale' => 'en_US', 'hreflang' => 'en-US', 'google_code' => 'en' ),
	)
);

function mt_translate( $text ) {
	return (string) $text;
}
