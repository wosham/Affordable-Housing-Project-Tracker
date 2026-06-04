<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager'],
    'csrf' => false,
]);

$csrf = Csrf::fromRequest();
if (!Csrf::verify($csrf, 'manager_site_records') && !Csrf::verify($csrf, 'default')) {
    Response::json(['success' => false, 'message' => 'CSRF token mismatch. Please refresh the page and try again.'], 419);
}

$input = $_POST ?: Security::jsonInput();
$id = Security::cleanInt($input['id'] ?? 0);
$status = Security::cleanString((string)($input['status'] ?? ''));
if ($id <= 0 || $status === '') {
    Response::json(['success' => false, 'message' => 'Record and status are required.'], 422);
}

try {
    $record = ManagerSiteRecord::updateStatus('meeting', $id, (int)Auth::id(), (string)Auth::role(), $status);
    Logger::log('update', 'site_meeting_minutes', $id, ['status' => $record['status'] ?? $status]);
    Response::json(['success' => true, 'message' => 'Meeting status updated.', 'record' => ManagerSiteRecord::meetingPayload($record)]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'Meeting status could not be updated.'], 422);
}
