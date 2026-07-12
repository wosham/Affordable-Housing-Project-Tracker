<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager', 'clerk'],
    'csrf' => false,
]);

if (!ipc_endorse_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = ipc_endorse_input();
$ipcId = Security::cleanInt($input['ipc_id'] ?? 0);
$comment = trim(strip_tags((string)($input['comment'] ?? '')));

if ($ipcId <= 0) {
    Response::json(['success' => false, 'message' => 'IPC id is required.'], 422);
}

$ipc = IPC::findDetailed($ipcId);
if (!$ipc) {
    Response::json(['success' => false, 'message' => 'IPC could not be found.'], 404);
}

$role = (string)Auth::role();
$actorId = (int)Auth::id();
$status = (string)$ipc['status'];

if ($status === 'submitted') {
    if (!in_array($role, ['superadmin', 'clerk'], true)) {
        Response::json(['success' => false, 'message' => 'Only the Clerk of Works can verify a submitted IPC.'], 403);
    }

    $nextStatus = 'clerk-endorsed';
    $step = 1;
    $action = 'endorsed';
    $message = 'IPC verified successfully.';
    $notifyRole = 'consultant';
    $notifyTitle = 'IPC ready for certification';
    $notifyBody = 'IPC #' . $ipc['ipc_number'] . ' for ' . $ipc['project_name'] . ' has been verified and is ready for certification.';
    $notifyLink = 'admin/consultant/ipc-inbox.php';
} elseif ($status === 'certified') {
    if (!in_array($role, ['superadmin', 'manager'], true)) {
        Response::json(['success' => false, 'message' => 'Only the project manager can endorse a certified IPC.'], 403);
    }

    if ($role === 'manager' && !ProjectAssignment::canManageProject($actorId, (int)$ipc['project_id'], $role)) {
        Response::json(['success' => false, 'message' => 'You do not manage this project.'], 403);
    }

    $nextStatus = 'endorsed';
    $step = 3;
    $action = 'endorsed';
    $message = 'IPC endorsed successfully.';
    $notifyRole = 'superadmin';
    $notifyTitle = 'IPC ready for final approval';
    $notifyBody = 'IPC #' . $ipc['ipc_number'] . ' for ' . $ipc['project_name'] . ' has been endorsed for final approval.';
    $notifyLink = 'admin/superadmin/ipcs.php';
} else {
    Response::json(['success' => false, 'message' => 'This IPC is not ready for this endorsement step.'], 409);
}

try {
    Database::beginTransaction();
    Database::query(
        'UPDATE ipcs SET status = ? WHERE id = ?',
        [$nextStatus, $ipcId]
    );
    IPCApproval::record($ipcId, $step, $actorId, $action, $comment);
    ipc_endorse_audit($action, 'ipcs', $ipcId, [
        'ipc_number' => $ipc['ipc_number'],
        'project' => $ipc['project_name'],
        'from_status' => $status,
        'to_status' => $nextStatus,
    ]);

    Notification::pushRole($notifyRole, 'ipc_' . str_replace('-', '_', $nextStatus), $notifyTitle, $notifyBody, $notifyLink);
    Notification::push((int)$ipc['contractor_id'], 'ipc_updated', 'IPC status updated', 'IPC #' . $ipc['ipc_number'] . ' for ' . $ipc['project_name'] . ' moved to ' . status_label($nextStatus) . '.', 'admin/contractor/ipc-history.php');

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'IPC endorsement could not be completed.'], 500);
}

Response::json([
    'success' => true,
    'message' => $message,
    'ipc' => IPC::findDetailed($ipcId),
]);

function ipc_endorse_input(): array
{
    $json = Security::jsonInput();
    return $json !== [] ? $json : $_POST;
}

function ipc_endorse_csrf_ok(): bool
{
    return ApiCsrf::checkAny(['clerk_ipcs', 'clerk_ipc_verify', 'manager_ipcs', 'manager_ipc_queue', 'superadmin_ipcs', 'superadmin_approvals', 'default']);
}

function ipc_endorse_audit(string $action, string $module, int $targetId, array $details = []): void
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
