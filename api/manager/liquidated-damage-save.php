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
    $id = ManagerContractControl::saveLd($userId, $role, $input);
    $record = ManagerContractControl::find('ld', $id, $userId, $role);
    Logger::log(!empty($input['id']) ? 'update' : 'create', 'liquidated_damages', $id, [
        'project_id' => $record['project_id'] ?? null,
        'status' => $record['status'] ?? null,
        'total_ld' => $record['total_ld'] ?? null,
    ]);

    if ((float)($record['total_ld'] ?? 0) > 0 && in_array((string)($record['status'] ?? ''), ['pending', 'applied'], true)) {
        Notification::pushRole(
            'superadmin',
            'liquidated_damages',
            'Liquidated damages recorded',
            ($record['project_name'] ?? 'A project') . ' has LD exposure of ' . format_money($record['total_ld'] ?? 0) . '.',
            'admin/superadmin/financials.php'
        );
    }

    Response::json(['success' => true, 'message' => 'Liquidated damages record saved successfully.', 'record' => $record ? ManagerContractControl::ldPayload($record) : []]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'LD record could not be saved.'], 422);
}
