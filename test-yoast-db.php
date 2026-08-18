<?php
require_once dirname(__FILE__) . '/../../../wp-load.php';
global $wpdb;
$table = $wpdb->prefix . 'yoast_indexable';
$page = get_page_by_path('barcelona-taxis');
if ($page) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT primary_focus_keyword_score, readability_score FROM $table WHERE object_id = %d AND object_type = 'post'", $page->ID));
    $meta = get_post_meta($page->ID, '_yoast_wpseo_content_score', true);
    echo "ID: " . $page->ID . "\n";
    echo "DB SEO: " . ($row ? $row->primary_focus_keyword_score : 'null') . "\n";
    echo "DB READ: " . ($row ? $row->readability_score : 'null') . "\n";
    echo "META READ: " . $meta . "\n";
} else {
    echo "Page not found";
}
