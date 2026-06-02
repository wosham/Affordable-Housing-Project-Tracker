<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager', 'consultant', 'clerk'],
    'csrf' => false,
]);

if (!ipc_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = approval_input();
$ipcId = Security::cleanInt($input['ipc_id'] ?? 0);
$reason = trim(strip_tags((string)($input['reason'] ?? $input['comment'] ?? '')));

if ($ipcId <= 0) {
    Response::json(['success' => false, 'message' => 'IPC id is required.'], 422);
}

if (SystemConfig::bool('ipc.rejection_reason_required', true) && $reason === '') {
    Response::json(['success' => false, 'message' => 'A rejection reason is required.'], 422);
}

$ipc = IPC::findDetailed($ipcId);
if (!$ipc) {
    Response::json(['success' => false, 'message' => 'IPC could not be found.'], 404);
}

if (in_array($ipc['status'], ['paid', 'rejected'], true)) {
    Response::json(['success' => false, 'message' => 'This IPC can no longer be rejected.'], 409);
}

$step = match (Auth::role()) {
    'clerk' => 1,
    'consultant' => 2,
    'manager' => 3,
    default => 4,
};

try {
    Database::beginTransaction();
    Database::query(
        "UPDATE ipcs SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ? WHERE id = ?",
        [(int)Auth::id(), $reason, $ipcId]
    );
    IPCApproval::record($ipcId, $step, (int)Auth::id(), 'rejected', $reason);
    approval_audit('reject', 'ipcs', $ipcId, ['ipc_number' => $ipc['ipc_number'], 'reason' => $reason]);

    Notification::push((int)$ipc['contractor_id'], 'ipc_rejected', 'IPC rejected', 'IPC #' . $ipc['ipc_number'] . ' for ' . $ipc['project_name'] . ' was rejected: ' . $reason, 'admin/contractor/ipc-history.php');

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Rejection could not be completed.'], 500);
}

Response::json([
    'success' => true,
    'message' => 'IPC rejected successfully.',
    'ipc' => IPC::findDetailed($ipcId),
]);

function approval_input(): array
{
    $json = Security::jsonInput();
    return $json !== [] ? $json : $_POST;
}

function ipc_csrf_ok(): bool
{
    $token = Csrf::fromRequest();
    return Csrf::verify($token, 'superadmin_approvals')
        || Csrf::verify($token, 'superadmin_ipcs');
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
