<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle(['methods' => ['POST'], 'roles' => ['superadmin', 'manager'], 'csrf' => false]);

ApiCsrf::requireAny(ApiCsrf::forms('manager_contract_controls'));

$input = $_POST ?: Security::jsonInput();
$id = Security::cleanInt($input['id'] ?? 0);
$status = Security::cleanString((string)($input['status'] ?? ''));
if ($id <= 0 || $status === '') {
    Response::json(['success' => false, 'message' => 'Record and status are required.'], 422);
}

try {
    $record = ManagerContractControl::updateStatus('subcontractor', $id, (int)Auth::id(), (string)Auth::role(), $status);
    Logger::log('update', 'subcontractors', $id, ['status' => $record['status'] ?? $status]);
    Response::json(['success' => true, 'message' => 'Subcontractor status updated.', 'record' => ManagerContractControl::subPayload($record)]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'Subcontractor status could not be updated.'], 422);
}
