<?php

function process_file($file) {
    if (!is_file($file)) return;
    $content = file_get_contents($file);
    $orig = $content;

    // 1. Placeholders
    $content = preg_replace_callback('/placeholder="([^"]*[a-zA-Z][^"]*)"/u', function($m) {
        $val = $m[1];
        if (strpos($val, '<?php') !== false) return $m[0]; // Already has PHP
        return 'placeholder="<?php echo esc_attr( mt_translate( \'' . addslashes($val) . '\' ) ); ?>"';
    }, $content);

    // 2. Button texts (specifically the Reservar berlina ejecutiva ones in front-page)
    $content = preg_replace_callback('/(<a[^>]+class="btn btn-ghost"[^>]*>)\s*([^<]+)\s*(<\/a>)/i', function($m) {
        $text = trim($m[2]);
        if (empty($text) || strpos($text, '<?php') !== false) return $m[0];
        return $m[1] . '<?php echo esc_html( mt_translate( \'' . addslashes($text) . '\' ) ); ?>' . $m[3];
    }, $content);

    // 3. Labels with class="screen-reader-text"
    $content = preg_replace_callback('/(<label[^>]+class="screen-reader-text"[^>]*>)\s*([^<]+)\s*(<\/label>)/i', function($m) {
        $text = trim($m[2]);
        if (empty($text) || strpos($text, '<?php') !== false) return $m[0];
        return $m[1] . '<?php echo esc_html( mt_translate( \'' . addslashes($text) . '\' ) ); ?>' . $m[3];
    }, $content);

    // 4. statsbar__lbl
    $content = preg_replace_callback('/(<span class="rutas-statsbar__lbl">)\s*([^<]+)\s*(<\/span>)/i', function($m) {
        $text = trim($m[2]);
        if (empty($text) || strpos($text, '<?php') !== false) return $m[0];
        return $m[1] . '<?php echo esc_html( mt_translate( \'' . addslashes($text) . '\' ) ); ?>' . $m[3];
    }, $content);

    // 5. Hardcoded strings specific to footer and others
    $reps = [
        '><?php echo esc_html( mt_translate( 'Normalmente responde al instante' ) ); ?><' => '><?php echo esc_html( mt_translate( \'Normalmente responde al instante\' ) ); ?><',
        '><?php echo esc_html( mt_translate( 'Ayuda' ) ); ?><' => '><?php echo esc_html( mt_translate( \'Ayuda\' ) ); ?><',
        '<?php echo mt_translate( 'Todas nuestras' ); ?> <span class="text-gradient"><?php echo mt_translate( 'Rutas' ); ?></span>' => '<?php echo mt_translate( \'Todas nuestras\' ); ?> <span class="text-gradient"><?php echo mt_translate( \'Rutas\' ); ?></span>',
    ];
    foreach ($reps as $k => $v) {
        $content = str_replace($k, $v, $content);
    }

    if ($orig !== $content) {
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}

$files = glob("*.php");
$files = array_merge($files, glob("includes/*.php"));

foreach ($files as $file) {
    process_file($file);
}

echo "Sweep completed.\n";
