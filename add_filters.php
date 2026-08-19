<?php
$file = 'includes/i18n.php';
$content = file_get_contents($file);

// Ensure we don't add it multiple times
if (strpos($content, "add_filter( 'the_content', 'mt_translate_content'") === false) {
    $filters = <<<PHP

// =================================================================
// 6. FILTROS PARA TRADUCIR EL CONTENIDO DINÁMICO (POST_CONTENT / TITLE)
// =================================================================

function mt_translate_content( \$content ) {
    if ( function_exists( 'mt_translate' ) ) {
        return mt_translate( \$content );
    }
    return \$content;
}
add_filter( 'the_content', 'mt_translate_content', 99 );

function mt_translate_title( \$title, \$id = null ) {
    if ( function_exists( 'mt_translate' ) ) {
        return mt_translate( \$title );
    }
    return \$title;
}
add_filter( 'the_title', 'mt_translate_title', 99, 2 );

function mt_translate_excerpt( \$excerpt ) {
    if ( function_exists( 'mt_translate' ) ) {
        return mt_translate( \$excerpt );
    }
    return \$excerpt;
}
add_filter( 'the_excerpt', 'mt_translate_excerpt', 99 );
add_filter( 'get_the_excerpt', 'mt_translate_excerpt', 99 );

PHP;

    $content .= "\n" . $filters;
    file_put_contents($file, $content);
    echo "Added the_content / the_title filters to i18n.php\n";
} else {
    echo "Filters already exist\n";
}
