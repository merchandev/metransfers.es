<?php
/** Isolated CLI contract: validation must stop before any database mutation. */
define( 'WP_CLI', true );
define( 'OBJECT', 'OBJECT' );
class WP_CLI {
	public static function error( $message ) { throw new RuntimeException( $message ); }
	public static function log( $message ) {}
	public static function success( $message ) {}
}
class MT_EditorialTestPost {
	public $ID;
	public $post_name;
	public $post_title = 'Transfer guide';
	public $post_type = 'post';
	public $post_status = 'publish';
	public $post_content = '<p>Existing <a href="https://sales.example/">sales link</a></p>';
	public $post_excerpt = 'Unrelated topic';
	public $post_modified = '2026-08-05 09:00:00';
	public function to_array() { return get_object_vars( $this ); }
}
function get_post( $id ) { return $GLOBALS['mt_blog_test_posts'][ (int) $id ] ?? null; }
function get_page_by_path( $slug, $output, $types ) {
	foreach ( $GLOBALS['mt_blog_test_posts'] as $post ) {
		if ( $post->post_name === $slug ) { return $post; }
	}
	return null;
}
function get_post_meta( $id, $key = '', $single = false ) { return $key ? '' : array(); }
function get_permalink( $post ) { $post = is_object( $post ) ? $post : get_post( $post ); return 'https://example.test/' . $post->post_name . '/'; }
function sanitize_title( $slug ) { return strtolower( preg_replace( '/[^a-z0-9-]/i', '', $slug ) ); }
function sanitize_key( $key ) { return sanitize_title( $key ); }
function add_option( $key, $value, $deprecated = '', $autoload = false ) { ++$GLOBALS['mt_blog_test_writes']; return true; }
function get_option( $key, $default = false ) { return $GLOBALS['mt_blog_test_options'][ $key ] ?? $default; }
function wp_update_post( $data, $error = false ) { ++$GLOBALS['mt_blog_test_writes']; return $data['ID']; }
function wp_slash( $value ) { return addslashes( $value ); }
function update_post_meta( $id, $key, $value ) { ++$GLOBALS['mt_blog_test_writes']; }
function update_option( $key, $value, $autoload = false ) { ++$GLOBALS['mt_blog_test_writes']; return true; }

function mt_blog_assert( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
require dirname( __DIR__ ) . '/app/bootstrap.php';
foreach ( array( 1, 2 ) as $id ) {
	$post = new MT_EditorialTestPost();
	$post->ID = $id;
	$post->post_name = 'old-topic-' . $id;
	$GLOBALS['mt_blog_test_posts'][ $id ] = $post;
}
$manifest = array( 'version' => 'test-editorial-guards', 'posts' => array() );
foreach ( array( 1, 2 ) as $id ) {
	$manifest['posts'][] = array( 'id' => $id, 'old_slug' => 'old-topic-' . $id, 'title' => 'Transfer guide', 'expected_modified' => '2026-08-05 09:00:00', 'new_slug' => 'new-topic-' . $id, 'new_excerpt' => 'Transfer guide excerpt' );
}
$manifest_path = tempnam( sys_get_temp_dir(), 'mt-blog-test-' );
file_put_contents( $manifest_path, json_encode( $manifest ) );
$GLOBALS['mt_blog_test_writes'] = 0;
$args = array( $manifest_path );
include dirname( __DIR__ ) . '/tools/fix-blog-slugs.php';
mt_blog_assert( 0 === $GLOBALS['mt_blog_test_writes'], 'Dry run changed data.' );
$args[] = '--apply';
$GLOBALS['mt_blog_test_posts'][2]->post_modified = '2026-10-01 10:00:00';
try {
	include dirname( __DIR__ ) . '/tools/fix-blog-slugs.php';
	throw new LogicException( 'Newer post edit was accepted.' );
} catch ( RuntimeException $e ) {
	mt_blog_assert( str_contains( $e->getMessage(), 'expected_modified' ), 'Wrong validation error.' );
}
mt_blog_assert( 0 === $GLOBALS['mt_blog_test_writes'], 'Preflight must reject all rows before changing the first post.' );
$GLOBALS['mt_blog_test_posts'][2]->post_modified = '2026-08-05 09:00:00';
$GLOBALS['mt_blog_test_posts'][2]->post_name = 'new-topic-1';
try {
	include dirname( __DIR__ ) . '/tools/fix-blog-slugs.php';
	throw new LogicException( 'Slug collision was accepted.' );
} catch ( RuntimeException $e ) {
	mt_blog_assert( str_contains( $e->getMessage(), 'another post' ), 'Slug collision not detected.' );
}
mt_blog_assert( 0 === $GLOBALS['mt_blog_test_writes'], 'Collision must not mutate data.' );
$GLOBALS['mt_blog_test_posts'][2]->post_name = 'old-topic-2';
$backup = array();
foreach ( $GLOBALS['mt_blog_test_posts'] as $id => $post ) {
	$backup[ $id ] = array( 'post' => $post->to_array(), 'meta' => array(), 'permalink' => get_permalink( $post ) );
	$post->post_name = 'new-topic-' . $id;
	$post->post_excerpt = 'Transfer guide excerpt';
}
$GLOBALS['mt_blog_test_options'] = array( 'mt_blog_slugs_backup_test-editorial-guards' => $backup, 'mt_blog_slugs_backup_test-editorial-guards_redirects' => array() );
$GLOBALS['mt_blog_test_posts'][2]->post_content = '<p>A newer sales article</p>';
try {
	include dirname( __DIR__ ) . '/tools/restore-blog-slugs.php';
	throw new LogicException( 'Rollback overwrote newer content.' );
} catch ( RuntimeException $e ) {
	mt_blog_assert( str_contains( $e->getMessage(), 'newer edit' ), 'Rollback guard not detected.' );
}
mt_blog_assert( 0 === $GLOBALS['mt_blog_test_writes'], 'Rollback validation must precede every write.' );
unlink( $manifest_path );
echo "Blog repair preflight and rollback guard tests passed.\n";
