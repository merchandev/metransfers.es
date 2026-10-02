<?php
namespace MeTransfers\SEO;

function wp_remote_get( $url, $args = array() ) {
	return array( 'response' => array( 'code' => $GLOBALS['mt_variant_http'][ $url ] ?? 200 ) );
}
function is_wp_error( $response ): bool { return false; }
function wp_remote_retrieve_response_code( $response ): int { return $response['response']['code']; }
function update_post_meta( $id, $key, $value ) {
	if ( ( $GLOBALS['mt_variant_failed_id'] ?? 0 ) === $id && is_array( $value ) && ! empty( $value['translated_reviewed'] ) ) { return false; }
	$GLOBALS['mt_test_post_meta'][ $id ][ $key ] = $value;
	return true;
}
function delete_post_meta( $id, $key ) {
	unset( $GLOBALS['mt_test_post_meta'][ $id ][ $key ] );
	return true;
}
