<?php

declare(strict_types=1);

if (!headers_sent()) {
    header_remove('X-Powered-By');
}

require_once __DIR__ . '/Env.php';

Env::load(dirname(__DIR__, 2) . '/.env');

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
require_once __DIR__ . '/Model.php';
foreach (glob(dirname(__DIR__) . '/models/*.php') ?: [] as $modelFile) {
    require_once $modelFile;
}
foreach (glob(dirname(__DIR__) . '/services/*.php') ?: [] as $serviceFile) {
    require_once $serviceFile;
}
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/ApiCsrf.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Url.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/PublicApi.php';
require_once __DIR__ . '/Guard.php';
require_once __DIR__ . '/RoleAccess.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/SystemConfig.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Uploader.php';
require_once __DIR__ . '/Paginator.php';
require_once __DIR__ . '/CmsLoader.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/GeoFence.php';
require_once __DIR__ . '/helpers.php';
require_once dirname(__DIR__) . '/helpers/contact-enquiries.php';

require_once dirname(__DIR__) . '/middleware/CsrfMiddleware.php';
require_once dirname(__DIR__) . '/middleware/ApiMiddleware.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/middleware/RoleMiddleware.php';

Security::sendHeaders();
if (!defined('APP_SKIP_SESSION') || APP_SKIP_SESSION !== true) {
    Session::start($app_config['session'] ?? []);
}

return [
    'app' => $app_config,
    'database' => $db_config,
    'paths' => $paths,
];
