<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle(['methods' => ['POST'], 'roles' => ['superadmin', 'manager'], 'csrf' => false]);

$csrf = Csrf::fromRequest();
if (!Csrf::verify($csrf, 'manager_contract_controls') && !Csrf::verify($csrf, 'default')) {
    Response::json(['success' => false, 'message' => 'CSRF token mismatch. Please refresh the page and try again.'], 419);
}

$input = $_POST ?: Security::jsonInput();
$userId = (int)Auth::id();
$role = (string)Auth::role();

try {
    $id = ManagerContractControl::saveSubcontractor($userId, $role, $input);
    $record = ManagerContractControl::find('subcontractor', $id, $userId, $role);
    Logger::log(!empty($input['id']) ? 'update' : 'create', 'subcontractors', $id, [
        'project_id' => $record['project_id'] ?? null,
        'status' => $record['status'] ?? null,
        'risk_status' => $record['risk_status'] ?? null,
    ]);

    if (in_array((string)($record['risk_status'] ?? ''), ['high', 'critical'], true) || in_array((string)($record['status'] ?? ''), ['suspended', 'terminated'], true)) {
        Notification::pushRole(
            'superadmin',
            'subcontractor_risk',
            'Subcontractor needs attention',
            ($record['company'] ?? 'A subcontractor') . ' on ' . ($record['project_name'] ?? 'a project') . ' has been flagged.',
            'admin/manager/subcontractors.php?project_id=' . (int)($record['project_id'] ?? 0)
        );
    }

    Response::json(['success' => true, 'message' => 'Subcontractor record saved successfully.', 'record' => $record ? ManagerContractControl::subPayload($record) : []]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'Subcontractor record could not be saved.'], 422);
}
