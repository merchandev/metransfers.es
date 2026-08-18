<?php
$wp_load = realpath(__DIR__ . '/../../../wp-load.php');
if (file_exists($wp_load)) {
    require_once $wp_load;
    global $wpdb;
    $table = $wpdb->prefix . 'yoast_indexable';
    $cols = $wpdb->get_col("DESCRIBE $table");
    
    $page = get_page_by_path('barcelona-taxis');
    if ($page) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE object_id = %d AND object_type = 'post'", $page->ID), ARRAY_A);
        $output = "Columns:\n" . implode(', ', $cols) . "\n\nRow:\n" . print_r($row, true);
        
        $meta = get_post_meta($page->ID);
        $output .= "\n\nMeta:\n" . print_r($meta, true);
        
        file_put_contents(__DIR__ . '/yoast-debug.txt', $output);
    }
}
