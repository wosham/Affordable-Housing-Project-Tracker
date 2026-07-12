<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf' => false,
]);

if (!ipc_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = approval_input();
$ipcId = Security::cleanInt($input['ipc_id'] ?? 0);
$comment = trim(strip_tags((string)($input['comment'] ?? '')));

if ($ipcId <= 0) {
    Response::json(['success' => false, 'message' => 'IPC id is required.'], 422);
}

$ipc = IPC::findDetailed($ipcId);
if (!$ipc) {
    Response::json(['success' => false, 'message' => 'IPC could not be found.'], 404);
}

if (!in_array($ipc['status'], ['endorsed', 'certified'], true)) {
    Response::json(['success' => false, 'message' => 'Only endorsed or certified IPCs can receive final approval.'], 409);
}

try {
    Database::beginTransaction();
    Database::query(
        "UPDATE ipcs SET status = 'approved', approved_at = NOW(), approved_by = ?, rejected_by = NULL, rejected_at = NULL, rejection_reason = NULL WHERE id = ?",
        [(int)Auth::id(), $ipcId]
    );
    Database::query(
        "UPDATE boq_items bi
         JOIN ipc_lines il ON il.boq_item_id = bi.id
         SET bi.certified_qty = GREATEST(COALESCE(bi.certified_qty, 0), COALESCE(il.cumulative_qty, 0)),
             bi.certified_updated_by = ?,
             bi.last_certified_at = COALESCE(bi.last_certified_at, NOW()),
             bi.updated_by = ?,
             bi.updated_at = NOW()
         WHERE il.ipc_id = ?",
        [(int)Auth::id(), (int)Auth::id(), $ipcId]
    );
    IPCApproval::record($ipcId, 4, (int)Auth::id(), 'approved', $comment);
    approval_audit('approve', 'ipcs', $ipcId, ['ipc_number' => $ipc['ipc_number'], 'project' => $ipc['project_name']]);

    Notification::push((int)$ipc['contractor_id'], 'ipc_approved', 'IPC approved', 'IPC #' . $ipc['ipc_number'] . ' for ' . $ipc['project_name'] . ' has been approved.', 'admin/contractor/ipc-history.php');
    Notification::pushRole('finance', 'ipc_approved', 'IPC ready for payment', 'IPC #' . $ipc['ipc_number'] . ' for ' . $ipc['project_name'] . ' is approved and ready for processing.', 'admin/finance/approved-ipcs.php');

    Database::commit();
} catch (Throwable $exception) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Approval could not be completed.'], 500);
}

Response::json([
    'success' => true,
    'message' => 'IPC approved successfully.',
    'ipc' => IPC::findDetailed($ipcId),
]);

function approval_input(): array
{
    $json = Security::jsonInput();
    return $json !== [] ? $json : $_POST;
}

function ipc_csrf_ok(): bool
{
    return ApiCsrf::checkAny(['superadmin_approvals', 'superadmin_ipcs']);
}

function approval_audit(string $action, string $module, int $targetId, array $details = []): void
{
    try {
        Database::query(
            'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::id(),
                $action,
                $module,
                $targetId,
                json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
    } catch (Throwable) {
    }
}
