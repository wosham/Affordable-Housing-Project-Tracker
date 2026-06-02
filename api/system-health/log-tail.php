<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin'],
    'csrf' => false,
]);

$file = Security::cleanString((string)($_GET['file'] ?? 'error.log'));
$lines = max(10, min(200, Security::cleanInt($_GET['lines'] ?? 40)));

Response::json([
    'success' => true,
    'log' => SystemHealth::logTail($file, $lines),
]);
