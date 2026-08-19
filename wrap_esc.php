<?php
$files = glob("template-*.php");
$files = array_merge($files, glob("single*.php"));
$files = array_merge($files, glob("archive*.php"));
$files = array_merge($files, glob("page*.php"));

foreach ($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        
        // Match echo esc_html( $variable )
        // Let's specifically target the pattern
        $content = preg_replace_callback('/(echo\s+esc_html\(\s*)([^\)]+)(\s*\);)/', function($m) {
            // Check if it's already translated
            if (strpos($m[2], 'mt_translate') !== false) {
                return $m[0];
            }
            return $m[1] . 'mt_translate( ' . $m[2] . ' )' . $m[3];
        }, $content);

        // Also do esc_attr for placeholders or titles
        $content = preg_replace_callback('/(echo\s+esc_attr\(\s*)([^\)]+)(\s*\);)/', function($m) {
            if (strpos($m[2], 'mt_translate') !== false) {
                return $m[0];
            }
            // Skip if it looks like a URL, ID, or class
            if (preg_match('/(url|id|class|icon)/i', $m[2])) {
                return $m[0];
            }
            return $m[1] . 'mt_translate( ' . $m[2] . ' )' . $m[3];
        }, $content);

        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
