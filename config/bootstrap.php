<?php

require_once __DIR__ . '/cors.php';

// Сесия за автентикация. Стартира се само при реални HTTP заявки
// (не при CLI скриптове, където няма cookie контекст).
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

spl_autoload_register(function (string $class): void {
    $baseDir = dirname(__DIR__) . '/src/';

    $paths = [
        $baseDir . 'Core/' . $class . '.php',
        $baseDir . 'Dto/' . $class . '.php',
        $baseDir . 'Dao/' . $class . '.php',
        $baseDir . 'Import/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
