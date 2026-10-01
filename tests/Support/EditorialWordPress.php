<?php
namespace MeTransfers\I18n;

function wp_cache_get( $key, $group ) {
	$GLOBALS['mt_editorial_cache_reads'][] = $key;
	return $GLOBALS['mt_editorial_cache'][ $key ] ?? false;
}
function wp_cache_set( $key, $value, $group, $ttl ) {
	$GLOBALS['mt_editorial_cache'][ $key ] = $value;
}
function get_option( $key, $default = false ) {
	return $GLOBALS['mt_editorial_options'][ $key ] ?? $default;
}
