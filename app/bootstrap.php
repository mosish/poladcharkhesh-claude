<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 *
 * There is no Composer on the target host, so autoloading is a small PSR-4
 * mapping of the `App\` namespace onto this directory. Nothing else is global.
 */

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('This application requires PHP 8.1 or newer.');
}

define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

App\Core\Config::load(APP_ROOT);

mb_internal_encoding('UTF-8');
date_default_timezone_set(App\Core\Config::get('APP_TIMEZONE', 'Asia/Tehran'));

if (App\Core\Config::isProduction()) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
