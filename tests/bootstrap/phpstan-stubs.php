<?php

define( 'WPTB_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/app/Legacy/WPTB/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'OBJECT', 'OBJECT' );
define( 'MT_PLATFORM_VERSION', 'test' );

define( 'MT_ACTIVE_LANGS', array( 'es' ) );
define( 'MT_SEO_LANGS', array( 'es' ) );
define(
	'MT_LANGS',
	array(
		'es' => array( 'label' => 'ES', 'name' => 'Español', 'google_code' => 'es' ),
	)
);

function mt_translate( $text ) {
	return (string) $text;
}
