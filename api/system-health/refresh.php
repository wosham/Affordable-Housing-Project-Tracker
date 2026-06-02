<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
]);

$snapshot = SystemHealth::snapshot(true, (int)Auth::id());
Logger::log('refresh', 'system_health', 0, ['status' => $snapshot['status'], 'score' => $snapshot['score']]);

Response::json([
    'success' => true,
    'message' => 'System health refreshed.',
    'snapshot' => [
        'status' => $snapshot['status'],
        'score' => $snapshot['score'],
        'checked_at' => $snapshot['checked_at'],
        'counts' => $snapshot['counts'],
        'recommendations' => $snapshot['recommendations'],
    ],
]);
