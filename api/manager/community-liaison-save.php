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
    $id = ManagerSiteRecord::saveCommunity($userId, $role, $input);
    $record = ManagerSiteRecord::find('community', $id, $userId, $role);
    Logger::log(!empty($input['id']) ? 'update' : 'create', 'community_liaison', $id, [
        'project_id' => $record['project_id'] ?? null,
        'status' => $record['status'] ?? null,
        'follow_up_date' => $record['follow_up_date'] ?? null,
    ]);

    if (($record['follow_up_date'] ?? null) && strtotime((string)$record['follow_up_date']) < strtotime(date('Y-m-d')) && !in_array((string)($record['status'] ?? ''), ['resolved', 'closed'], true)) {
        Notification::pushRole(
            'superadmin',
            'community_followup',
            'Community follow-up overdue',
            ($record['project_name'] ?? 'A project') . ' has an overdue community liaison follow-up.',
            'admin/manager/community-liaison.php?project_id=' . (int)($record['project_id'] ?? 0)
        );
    }

    Response::json([
        'success' => true,
        'message' => 'Community liaison record saved successfully.',
        'record' => $record ? ManagerSiteRecord::communityPayload($record) : [],
    ]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'Community liaison record could not be saved.'], 422);
}
