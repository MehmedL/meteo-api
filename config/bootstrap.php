<?php

require_once __DIR__ . '/cors.php';

spl_autoload_register(function (string $class): void {
    $baseDir = dirname(__DIR__) . '/src/';

    $paths = [
        $baseDir . 'Core/' . $class . '.php',
        $baseDir . 'Dto/' . $class . '.php',
        $baseDir . 'Dao/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
