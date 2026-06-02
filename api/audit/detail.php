<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin'],
    'csrf' => false,
]);

$id = Security::cleanInt($_GET['id'] ?? 0);
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Audit event id is required.'], 422);
}

$event = AuditLog::findDetailed($id);
if (!$event) {
    Response::json(['success' => false, 'message' => 'Audit event could not be found.'], 404);
}

Response::json([
    'success' => true,
    'event' => $event,
]);
