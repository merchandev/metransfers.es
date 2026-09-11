<?php

// Keep the runtime theme version aligned with the WordPress style.css header.
// functions.php retains a guarded legacy fallback, so defining it here makes
// 5.0.5 authoritative from the earliest application bootstrap stage.
if ( ! defined( 'ME_TRANSFERS_VERSION' ) ) {
	define( 'ME_TRANSFERS_VERSION', '5.0.5' );
}

// Basic PSR-4 Autoloader for MeTransfers App
spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    $prefix = 'MeTransfers\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

require_once __DIR__ . '/Core/Application.php';