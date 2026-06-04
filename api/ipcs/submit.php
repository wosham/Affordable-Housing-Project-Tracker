<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['contractor'],
    'csrf' => false,
]);

if (!ipc_submit_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = ipc_submit_input();
$actorId = (int)Auth::id();
$role = (string)Auth::role();
$projectId = Security::cleanInt($input['project_id'] ?? 0);
$periodFrom = ipc_submit_date($input['period_from'] ?? null);
$periodTo = ipc_submit_date($input['period_to'] ?? null);
$reference = Security::cleanString((string)($input['contractor_reference'] ?? ''));
$declaration = (string)($input['declaration_accepted'] ?? '') === '1';
$linesInput = is_array($input['lines'] ?? null) ? $input['lines'] : [];

if ($projectId <= 0 || !$periodFrom || !$periodTo) {
    Response::json(['success' => false, 'message' => 'Project and claim period are required.'], 422);
}

if (strtotime($periodTo) < strtotime($periodFrom)) {
    Response::json(['success' => false, 'message' => 'The period end date cannot be before the start date.'], 422);
}

if (!$declaration) {
    Response::json(['success' => false, 'message' => 'Accept the contractor declaration before submitting.'], 422);
}

$project = Project::findDetailed($projectId);
if (!$project || !ContractorIPC::canAccess($actorId, $role, $projectId)) {
    Response::json(['success' => false, 'message' => 'You do not have access to submit IPCs for this project.'], 403);
}

if ((int)($project['contractor_id'] ?? 0) > 0 && (int)$project['contractor_id'] !== $actorId) {
    Response::json(['success' => false, 'message' => 'This project is assigned to another contractor account.'], 403);
}

$duplicate = Database::fetch(
    "SELECT id FROM ipcs
     WHERE project_id = ? AND contractor_id = ? AND period_from = ? AND period_to = ? AND status NOT IN ('rejected','paid')
     LIMIT 1",
    [$projectId, $actorId, $periodFrom, $periodTo]
);
if ($duplicate) {
    Response::json(['success' => false, 'message' => 'An active IPC already exists for this project period.'], 409);
}

$boqRows = ContractorIPC::boqLines($projectId, $actorId, $role);
$boqById = [];
foreach ($boqRows as $row) {
    $boqById[(int)$row['id']] = $row;
}

$claimLines = [];
$gross = 0.0;
foreach ($linesInput as $line) {
    if (!is_array($line)) {
        continue;
    }

    $boqId = Security::cleanInt($line['boq_item_id'] ?? 0);
    $qtyThis = round(max(0.0, Security::cleanFloat($line['qty_this_period'] ?? 0)), 3);
    if ($boqId <= 0 || $qtyThis <= 0 || !isset($boqById[$boqId])) {
        continue;
    }

    $boq = $boqById[$boqId];
    $quantity = (float)($boq['quantity'] ?? 0);
    $previous = (float)($boq['previously_claimed_qty'] ?? 0);
    $remaining = max(0.0, $quantity - $previous);
    if ($qtyThis - $remaining > 0.0001) {
        Response::json(['success' => false, 'message' => 'Claim quantity exceeds remaining BOQ quantity for item ' . ($boq['item_no'] ?? '#' . $boqId) . '.'], 422);
    }

    $rate = (float)($boq['rate'] ?? 0);
    $amount = round($qtyThis * $rate, 2);
    if ($amount <= 0) {
        continue;
    }

    $gross += $amount;
    $claimLines[] = [
        'boq_item_id' => $boqId,
        'description' => trim((string)($boq['item_no'] ?? '')) . ' - ' . trim((string)($boq['description'] ?? 'IPC claim item')),
        'qty_this_period' => $qtyThis,
        'cumulative_qty' => round($previous + $qtyThis, 3),
        'rate' => $rate,
        'amount' => $amount,
    ];
}

if ($claimLines === [] || $gross <= 0) {
    Response::json(['success' => false, 'message' => 'Enter at least one valid BOQ claim quantity.'], 422);
}

$retention = round($gross * ContractorIPC::RETENTION_RATE, 2);
$net = max(0.0, round($gross - $retention, 2));
$next = Database::fetch('SELECT COALESCE(MAX(ipc_number), 0) + 1 AS next_no FROM ipcs WHERE project_id = ?', [$projectId]);
$ipcNumber = max(1, (int)($next['next_no'] ?? 1));

try {
    $ipcId = ContractorIPC::createSubmission([
        'project_id' => $projectId,
        'contractor_id' => $actorId,
        'ipc_number' => $ipcNumber,
        'period_from' => $periodFrom,
        'period_to' => $periodTo,
        'contractor_reference' => $reference,
        'gross_amount' => $gross,
        'retention_amount' => $retention,
        'net_amount' => $net,
        'lines' => $claimLines,
    ]);

    ipc_submit_audit('contractor_ipc_submitted', 'ipcs', $ipcId, [
        'project_id' => $projectId,
        'ipc_number' => $ipcNumber,
        'gross' => $gross,
        'net' => $net,
    ]);

    Notification::pushRole('clerk', 'ipc_submitted', 'IPC submitted', 'IPC #' . $ipcNumber . ' for ' . ($project['name'] ?? 'a project') . ' is ready for site verification.', 'admin/clerk/ipc-verify.php');
    Notification::pushRole('consultant', 'ipc_submitted', 'IPC submitted', 'IPC #' . $ipcNumber . ' for ' . ($project['name'] ?? 'a project') . ' has entered the review workflow.', 'admin/consultant/ipc-inbox.php');
    Notification::pushRole('manager', 'ipc_submitted', 'IPC submitted', 'IPC #' . $ipcNumber . ' for ' . ($project['name'] ?? 'a project') . ' has entered the claims workflow.', 'admin/manager/ipc-queue.php');
    Notification::pushRole('superadmin', 'ipc_submitted', 'IPC submitted', 'IPC #' . $ipcNumber . ' has been submitted for ' . ($project['name'] ?? 'a project') . '.', 'admin/superadmin/ipcs.php');
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'IPC could not be submitted.'], 500);
}

Response::json([
    'success' => true,
    'message' => 'IPC submitted successfully.',
    'ipc' => IPC::findDetailed($ipcId),
    'redirect' => Url::to('admin/contractor/ipc-history.php'),
]);

function ipc_submit_input(): array
{
    $json = Security::jsonInput();
    return $json !== [] ? $json : $_POST;
}

function ipc_submit_csrf_ok(): bool
{
    $token = Csrf::fromRequest();
    foreach (['contractor_ipc', 'contractor_ipcs', 'contractor_ipc_submit', 'default'] as $form) {
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
