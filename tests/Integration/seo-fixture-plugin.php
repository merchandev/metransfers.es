<?php
// Loaded only as a mu-plugin by the disposable CI WordPress fixture.
if ( defined( 'MT_SEO_INTEGRATION' ) && MT_SEO_INTEGRATION && 'http://127.0.0.1:8080' === get_option( 'home' ) ) {
	add_filter( 'mt_seo_production_hosts', static function () { return array( '127.0.0.1' ); } );
}
