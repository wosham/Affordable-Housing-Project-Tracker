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
$userId = (int)Auth::id();
$role = (string)Auth::role();

try {
    $id = ManagerSiteRecord::saveMeeting($userId, $role, $input);
    $record = ManagerSiteRecord::find('meeting', $id, $userId, $role);
    Logger::log(!empty($input['id']) ? 'update' : 'create', 'site_meeting_minutes', $id, [
        'project_id' => $record['project_id'] ?? null,
        'status' => $record['status'] ?? null,
        'action_status' => $record['action_status'] ?? null,
    ]);

    if (($record['action_status'] ?? '') === 'overdue') {
        Notification::pushRole(
            'superadmin',
            'site_meeting_actions',
            'Site meeting action overdue',
            ($record['project_name'] ?? 'A project') . ' has overdue site meeting action items.',
            'admin/manager/site-meeting-minutes.php?project_id=' . (int)($record['project_id'] ?? 0)
        );
    }

    Response::json([
        'success' => true,
        'message' => 'Site meeting minutes saved successfully.',
        'record' => $record ? ManagerSiteRecord::meetingPayload($record) : [],
    ]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'Site meeting minutes could not be saved.'], 422);
}
