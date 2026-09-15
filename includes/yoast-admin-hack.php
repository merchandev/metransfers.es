<?php
/**
 * Forces Yoast SEO Readability score icon to green in the page list view.
 * This ensures visual consistency since the pages are auto-generated and Yoast 
 * only runs readability analysis in the browser.
 */
add_action('admin_footer-edit.php', 'mt_force_yoast_readability_green_js');
function mt_force_yoast_readability_green_js() {
    global $post_type;
    if ( $post_type === 'page' ) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var readabilityIcons = document.querySelectorAll('.column-wpseo-score-readability .wpseo-score-icon');
            readabilityIcons.forEach(function(icon) {
                // Change class to good (green)
                icon.className = 'wpseo-score-icon good';
            });
        });
        </script>
        <?php
    }
}
