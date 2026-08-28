<?php
require_once dirname(__DIR__, 4) . '/wp-load.php';
$pages = get_posts(array(
    'post_type' => 'page',
    'posts_per_page' => -1,
    'post_status' => 'publish'
));
foreach($pages as $p) {
    echo $p->post_name . "\n";
}
