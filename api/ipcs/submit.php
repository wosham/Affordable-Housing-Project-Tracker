<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'contractor'],
    'csrf' => false,
]);

if (!ipc_submit_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = ipc_submit_input();
$projectId = Security::cleanInt($input['project_id'] ?? 0);
$periodFrom = ipc_submit_date($input['period_from'] ?? null);
$periodTo = ipc_submit_date($input['period_to'] ?? null);
$gross = max(0.0, Security::cleanFloat($input['gross_amount'] ?? 0));
$retention = max(0.0, Security::cleanFloat($input['retention_amount'] ?? 0));
$net = isset($input['net_amount']) && $input['net_amount'] !== ''
    ? max(0.0, Security::cleanFloat($input['net_amount']))
    : max(0.0, $gross - $retention);
$lines = is_array($input['lines'] ?? null) ? $input['lines'] : [];

if ($projectId <= 0 || !$periodFrom || !$periodTo) {
    Response::json(['success' => false, 'message' => 'Project and claim period are required.'], 422);
}

if (strtotime($periodTo) < strtotime($periodFrom)) {
    Response::json(['success' => false, 'message' => 'The period end date cannot be before the start date.'], 422);
}

if ($gross <= 0 && $lines === []) {
    Response::json(['success' => false, 'message' => 'Enter a claim amount or at least one line item.'], 422);
}

$project = Project::findDetailed($projectId);
if (!$project) {
    Response::json(['success' => false, 'message' => 'Project could not be found.'], 404);
}

$actorId = (int)Auth::id();
$role = (string)Auth::role();
$contractorId = $role === 'superadmin'
    ? Security::cleanInt($input['contractor_id'] ?? ($project['contractor_id'] ?? 0))
    : $actorId;

if ($contractorId <= 0) {
    Response::json(['success' => false, 'message' => 'A contractor is required for this IPC.'], 422);
}

if (!ipc_submit_can_access_project($actorId, $role, $projectId, $contractorId, $project)) {
    Response::json(['success' => false, 'message' => 'You do not have access to submit IPCs for this project.'], 403);
}

$ipcNumber = Security::cleanInt($input['ipc_number'] ?? 0);
if ($ipcNumber <= 0) {
    $row = Database::fetch('SELECT COALESCE(MAX(ipc_number), 0) + 1 AS next_no FROM ipcs WHERE project_id = ?', [$projectId]);
    $ipcNumber = (int)($row['next_no'] ?? 1);
}

try {
    Database::beginTransaction();

    $ipcId = (int)IPC::create([
        'project_id' => $projectId,
        'contractor_id' => $contractorId,
        'ipc_number' => $ipcNumber,
        'period_from' => $periodFrom,
        'period_to' => $periodTo,
        'gross_amount' => $gross,
        'retention_amount' => $retention,
        'net_amount' => $net,
        'status' => 'submitted',
        'submitted_at' => date('Y-m-d H:i:s'),
    ]);

    $lineTotal = 0.0;
    foreach ($lines as $line) {
        if (!is_array($line)) {
            continue;
        }

        $description = trim(strip_tags((string)($line['description'] ?? '')));
        $qty = max(0.0, Security::cleanFloat($line['qty_this_period'] ?? 0));
        $cumulative = max(0.0, Security::cleanFloat($line['cumulative_qty'] ?? $qty));
        $rate = max(0.0, Security::cleanFloat($line['rate'] ?? 0));
        $amount = max(0.0, Security::cleanFloat($line['amount'] ?? ($qty * $rate)));

        if ($description === '' && $amount <= 0) {
            continue;
        }

        IPCLine::create([
            'ipc_id' => $ipcId,
            'boq_item_id' => Security::cleanInt($line['boq_item_id'] ?? 0) ?: null,
            'description' => $description !== '' ? $description : 'IPC claim item',
            'qty_this_period' => $qty,
            'cumulative_qty' => $cumulative,
            'rate' => $rate,
            'amount' => $amount,
        ]);
        $lineTotal += $amount;
    }

    if ($lineTotal <= 0 && $gross > 0) {
        IPCLine::create([
            'ipc_id' => $ipcId,
            'boq_item_id' => null,
            'description' => trim(strip_tags((string)($input['description'] ?? 'IPC claim summary'))) ?: 'IPC claim summary',
            'qty_this_period' => 1,
            'cumulative_qty' => 1,
            'rate' => $gross,
            'amount' => $gross,
        ]);
    }

    ipc_submit_audit('submit', 'ipcs', $ipcId, [
        'ipc_number' => $ipcNumber,
        'project' => $project['name'] ?? '',
        'net_amount' => $net,
    ]);

    Notification::pushRole('clerk', 'ipc_submitted', 'IPC submitted', 'IPC #' . $ipcNumber . ' for ' . ($project['name'] ?? 'a project') . ' is ready for site verification.', 'admin/clerk/ipc-verify.php');
    Notification::pushRole('superadmin', 'ipc_submitted', 'IPC submitted', 'IPC #' . $ipcNumber . ' has been submitted for ' . ($project['name'] ?? 'a project') . '.', 'admin/superadmin/ipcs.php');

    Database::commit();
} catch (Throwable $exception) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'IPC could not be submitted.'], 500);
}

Response::json([
    'success' => true,
    'message' => 'IPC submitted successfully.',
    'ipc' => IPC::findDetailed($ipcId),
]);

function ipc_submit_input(): array
{
    $json = Security::jsonInput();
    return $json !== [] ? $json : $_POST;
}

function ipc_submit_csrf_ok(): bool
{
    $token = Csrf::fromRequest();
    foreach (['contractor_ipc', 'contractor_ipcs', 'contractor_ipc_submit', 'superadmin_ipcs', 'default'] as $form) {
        if (Csrf::verify($token, $form)) {
            return true;
        }
    }

    return false;
}

function ipc_submit_date(mixed $value): ?string
{
    $timestamp = strtotime(trim((string)$value));
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

function ipc_submit_can_access_project(int $actorId, string $role, int $projectId, int $contractorId, array $project): bool
{
    if ($role === 'superadmin') {
        return true;
    }

    if ((int)($project['contractor_id'] ?? 0) === $contractorId && $actorId === $contractorId) {
        return true;
    }

    return ProjectAssignment::canManageProject($actorId, $projectId, $role);
}

function ipc_submit_audit(string $action, string $module, int $targetId, array $details = []): void
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
