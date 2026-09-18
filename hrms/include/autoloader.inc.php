<?php

// 1. Load Composer autoload (for Firebase JWT & other libs)
require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/../classes/',
        __DIR__ . '/../../admin/classes/',
    ];
    $file = strtolower($className) . '.class.php';
    foreach ($paths as $path) {
        if (file_exists($path . $file)) {
            include_once $path . $file;
            return;
        }
    }
});

?>
