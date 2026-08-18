<?php
// Debug script to check Yoast table columns
add_action('admin_notices', 'mt_debug_yoast_columns');
function mt_debug_yoast_columns() {
    global $wpdb;
    $table = $wpdb->prefix . 'yoast_indexable';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
        $cols = $wpdb->get_col("DESCRIBE $table");
        echo '<div class="notice notice-info is-dismissible"><p>Yoast Columns: ' . implode(', ', $cols) . '</p></div>';
        
        // Check one page's score
        $page = get_page_by_path('barcelona-taxis');
        if ($page) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT primary_focus_keyword_score, readability_score FROM $table WHERE object_id = %d", $page->ID));
            $meta_read = get_post_meta($page->ID, '_yoast_wpseo_content_score', true);
            echo '<div class="notice notice-info is-dismissible"><p>Page ID '.$page->ID.' -> DB SEO: ' . ($row ? $row->primary_focus_keyword_score : 'null') . ', DB Read: ' . ($row ? $row->readability_score : 'null') . ', Meta: ' . $meta_read . '</p></div>';
        }
    }
}
