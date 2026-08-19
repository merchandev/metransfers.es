<?php
$file = 'functions.php';
$content = file_get_contents($file);

// Add nav_menu_item_title filter
$filter = <<<'PHP'
// =============================================================================
// FILTRO: traducir títulos del wp_nav_menu al idioma activo
// =============================================================================

add_filter( 'nav_menu_item_title', function( $title, $item, $args, $depth ) {
    if ( function_exists( 'mt_translate' ) ) {
        return mt_translate( $title );
    }
    return $title;
}, 10, 4 );
PHP;

if (strpos($content, 'nav_menu_item_title') === false) {
    $content .= "\n" . $filter . "\n";
    file_put_contents($file, $content);
}
