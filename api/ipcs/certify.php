<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'consultant'],
    'csrf' => false,
]);

if (!ipc_certify_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = ipc_certify_input();
$ipcId = Security::cleanInt($input['ipc_id'] ?? 0);
$comment = trim(strip_tags((string)($input['comment'] ?? '')));
$checklist = array_values(array_filter(array_map('strval', (array)($input['checklist'] ?? []))));

if ($ipcId <= 0) {
    Response::json(['success' => false, 'message' => 'IPC id is required.'], 422);
}

$ipc = IPC::findDetailed($ipcId);
if (!$ipc) {
    Response::json(['success' => false, 'message' => 'IPC could not be found.'], 404);
}

if ((string)$ipc['status'] !== 'clerk-endorsed') {
    Response::json(['success' => false, 'message' => 'Only verified IPCs can be certified.'], 409);
}

$actorId = (int)Auth::id();
$role = (string)Auth::role();
if ($role === 'consultant' && !ipc_certify_consultant_allowed($actorId, (int)$ipc['project_id'])) {
    Response::json(['success' => false, 'message' => 'You are not assigned to certify this project.'], 403);
}

$lines = ConsultantIPC::lineItems($ipcId);
$warnings = ConsultantIPC::warnings($ipc, $lines);
$requiredChecklist = ConsultantIPC::REVIEW_CHECKLIST;
if ($role === 'consultant' && count(array_intersect($requiredChecklist, $checklist)) < count($requiredChecklist)) {
    Response::json(['success' => false, 'message' => 'Complete the certification checklist first.'], 422);
}
if (ConsultantIPC::hasCriticalWarnings($warnings)) {
    Response::json(['success' => false, 'message' => 'Resolve critical IPC warnings before certification.', 'warnings' => $warnings], 409);
}

try {
    Database::beginTransaction();
    Database::query(
        "UPDATE ipcs SET status = 'certified', certified_at = NOW(), certified_by = ?, certification_comment = ? WHERE id = ?",
        [$actorId, $comment !== '' ? $comment : null, $ipcId]
    );
    IPCApproval::record($ipcId, 2, $actorId, 'certified', $comment);
    ipc_certify_audit('certify', 'ipcs', $ipcId, [
        'ipc_number' => $ipc['ipc_number'],
        'project' => $ipc['project_name'],
        'warnings' => $warnings,
    ]);

    Notification::pushRole('manager', 'ipc_certified', 'IPC certified', 'IPC #' . $ipc['ipc_number'] . ' for ' . $ipc['project_name'] . ' is ready for manager endorsement.', 'admin/manager/ipc-queue.php');
    Notification::pushRole('superadmin', 'ipc_certified', 'IPC certified', 'IPC #' . $ipc['ipc_number'] . ' has been certified for ' . $ipc['project_name'] . '.', 'admin/superadmin/ipcs.php');
    Notification::push((int)$ipc['contractor_id'], 'ipc_certified', 'IPC certified', 'IPC #' . $ipc['ipc_number'] . ' for ' . $ipc['project_name'] . ' has been certified.', 'admin/contractor/ipc-history.php');

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'IPC certification could not be completed.'], 500);
}

Response::json([
    'success' => true,
    'message' => 'IPC certified successfully.',
    'ipc' => IPC::findDetailed($ipcId),
]);

function ipc_certify_input(): array
{
    $json = Security::jsonInput();
    return $json !== [] ? $json : $_POST;
}

function ipc_certify_csrf_ok(): bool
{
    $token = Csrf::fromRequest();
    foreach (['consultant_ipcs', 'consultant_ipc', 'consultant_ipc_inbox', 'superadmin_ipcs', 'default'] as $form) {
        if (Csrf::verify($token, $form)) {
            return true;
        }
    }

    return false;
}

function ipc_certify_consultant_allowed(int $userId, int $projectId): bool
{
    $project = Project::findDetailed($projectId);
    if ((int)($project['consultant_id'] ?? 0) === $userId) {
        return true;
    }

    return ProjectAssignment::canManageProject($userId, $projectId, 'consultant');
}

function ipc_certify_audit(string $action, string $module, int $targetId, array $details = []): void
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
