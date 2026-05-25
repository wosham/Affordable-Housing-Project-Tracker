<?php

declare(strict_types=1);

$app_config = require dirname(__DIR__) . '/config/app.php';
$db_config = require dirname(__DIR__) . '/config/database.php';
$paths = require dirname(__DIR__) . '/config/paths.php';

$GLOBALS['app_config'] = $app_config;
$GLOBALS['db_config'] = $db_config;
$GLOBALS['paths'] = $paths;

date_default_timezone_set($app_config['timezone'] ?? 'Africa/Nairobi');

if (($app_config['debug'] ?? false) === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Url.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Guard.php';
require_once __DIR__ . '/helpers.php';

Security::sendHeaders();
Session::start($app_config['session'] ?? []);

return [
    'app' => $app_config,
    'database' => $db_config,
    'paths' => $paths,
];
