<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application configuration
|--------------------------------------------------------------------------
|
| Keep environment-specific values in one place. For production, replace
| these defaults with values loaded from environment variables or a secure
| secrets manager.
|
*/

define('APP_NAME', 'WAKE Business Suite');
define('APP_COMPANY', 'WAKE SERVICES');
define('APP_ENV', 'development');
define('APP_DEBUG', true);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/views');
define('PUBLIC_PATH', BASE_PATH . '/public');

function wake_detect_base_url(): string
{
    if (PHP_SAPI === 'cli') {
        return 'http://localhost/WAKE';
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = rtrim(str_replace('/public', '', dirname($scriptName)), '/');

    return $scheme . '://' . $host . ($basePath === '' ? '' : $basePath);
}

define('BASE_URL', getenv('APP_URL') ?: wake_detect_base_url());

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$publicPrefix = strpos($scriptName, '/public/') !== false ? '/public' : '';
define('PUBLIC_URL', rtrim(BASE_URL, '/') . $publicPrefix);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'wake_business_suite');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
