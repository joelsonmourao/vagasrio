<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

$config = require ROOT_PATH . '/config/app.php';
date_default_timezone_set((string) ($config['site']['timezone'] ?? 'America/Sao_Paulo'));

session_name($config['security']['session_name']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relativeClass) . '.php';
    $file = ROOT_PATH . '/app/' . $relativePath;
    if (is_file($file)) {
        require $file;
        return;
    }

    $parts = explode('/', $relativePath);
    $parts[0] = strtolower($parts[0] ?? '');
    $fallback = ROOT_PATH . '/app/' . implode('/', $parts);
    if (is_file($fallback)) {
        require $fallback;
    }
});

require ROOT_PATH . '/app/helpers/functions.php';

$dbPath = ROOT_PATH . '/database/portal.sqlite';
App\Services\Database::init($dbPath, $config);
