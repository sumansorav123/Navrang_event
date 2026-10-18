<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);
define('APP_PATH', $rootPath . '/app');
define('PUBLIC_PATH', $rootPath . '/public');
define('CONFIG_PATH', $rootPath . '/config');
define('UPLOAD_PATH', $rootPath . '/storage/uploads');
define('PRIVATE_UPLOAD_PATH', $rootPath . '/storage/uploads/private');

require_once APP_PATH . '/helpers.php';

define('SITE_NAME', env('APP_NAME', 'Navrang Events'));
define('APP_URL', env('APP_URL', 'http://localhost/navrang/public'));
define('APP_ENV', env('APP_ENV', 'development'));

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => (int) env('SESSION_LIFE', 3600),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

ensureDatabaseSchema();
ensureInitialAdmin();
