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
    $id = ManagerSiteRecord::saveIncident($userId, $role, $input);
    $record = ManagerSiteRecord::find('incident', $id, $userId, $role);
    Logger::log(!empty($input['id']) ? 'update' : 'create', 'hs_incidents', $id, [
        'project_id' => $record['project_id'] ?? null,
        'severity' => $record['severity'] ?? null,
        'status' => $record['status'] ?? null,
    ]);

    if (in_array((string)($record['severity'] ?? ''), ['high', 'critical'], true) || (string)($record['incident_type'] ?? '') === 'fatality') {
        Notification::pushRole(
            'superadmin',
            'hs_incident',
            'H&S incident needs attention',
            ($record['project_name'] ?? 'A project') . ' has a ' . status_label((string)($record['severity'] ?? 'medium')) . ' H&S incident.',
            'admin/manager/hs-incidents.php?project_id=' . (int)($record['project_id'] ?? 0)
        );
    }

    Response::json([
        'success' => true,
        'message' => 'H&S incident saved successfully.',
        'record' => $record ? ManagerSiteRecord::incidentPayload($record) : [],
    ]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'H&S incident could not be saved.'], 422);
}
