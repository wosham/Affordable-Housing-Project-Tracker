<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => 'superadmin_approvals',
]);

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
    Database::query("UPDATE ipcs SET status = 'approved', approved_at = NOW() WHERE id = ?", [$ipcId]);
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
