<?php
define('WP_USE_THEMES', false);
// Try to find wp-load.php by going up directories
$dir = __DIR__;
while(!file_exists($dir . '/wp-load.php') && dirname($dir) !== $dir) {
    $dir = dirname($dir);
}
if(file_exists($dir . '/wp-load.php')) {
    require_once($dir . '/wp-load.php');
} else {
    die("No wp-load.php found\n");
}

$posts = get_posts(array(
    'post_type'      => 'any',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
));

$types = [];
foreach($posts as $post) {
    $types[$post->post_type] = ($types[$post->post_type] ?? 0) + 1;
}

echo "Total posts found: " . count($posts) . "\n";
print_r($types);
