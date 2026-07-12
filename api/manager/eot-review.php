<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle(['methods' => ['POST'], 'roles' => ['superadmin', 'manager'], 'csrf' => false]);

ApiCsrf::requireAny(ApiCsrf::forms('manager_contract_controls'));

$input = $_POST ?: Security::jsonInput();
$userId = (int)Auth::id();
$role = (string)Auth::role();

try {
    $id = ManagerContractControl::reviewEot($userId, $role, $input);
    $record = ManagerContractControl::find('eot', $id, $userId, $role);
    Logger::log('review', 'eot_requests', $id, [
        'recommendation' => $record['manager_recommendation'] ?? null,
        'recommended_days' => $record['manager_recommended_days'] ?? null,
    ]);

    if (in_array((string)($record['manager_recommendation'] ?? ''), ['approve', 'partial', 'reject'], true)) {
        Notification::pushRole(
            'superadmin',
            'eot_manager_review',
            'EOT request reviewed',
            ($record['project_name'] ?? 'A project') . ' has an EOT manager recommendation ready for final action.',
            'admin/superadmin/approvals.php?tab=eots'
        );
    } elseif (($record['manager_recommendation'] ?? '') === 'clarification') {
        Notification::push((int)($record['submitted_by'] ?? 0), 'eot_clarification', 'EOT clarification requested', 'Please review EOT #' . ($record['eot_number'] ?? $id) . ' for ' . ($record['project_name'] ?? 'your project') . '.', 'admin/contractor/eot-request.php');
    }

    Response::json(['success' => true, 'message' => 'EOT review saved successfully.', 'record' => $record ? ManagerContractControl::eotPayload($record) : []]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'EOT review could not be saved.'], 422);
}
