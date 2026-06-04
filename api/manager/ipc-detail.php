<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager'],
]);

$ipcId = Security::cleanInt($_GET['id'] ?? $_GET['ipc_id'] ?? 0);
if ($ipcId <= 0) {
    Response::json(['success' => false, 'message' => 'IPC id is required.'], 422);
}

$ipc = ManagerIPCQueue::findScoped($ipcId, (int)Auth::id(), (string)Auth::role());
if (!$ipc) {
    Response::json(['success' => false, 'message' => 'IPC could not be found.'], 404);
}

$lines = array_map(static function (array $line): array {
    return [
        'id' => (int)($line['id'] ?? 0),
        'item_code' => (string)($line['item_code'] ?? ''),
        'description' => (string)($line['description'] ?? ''),
        'unit' => (string)($line['unit'] ?? ''),
        'qty_this_period' => (float)($line['qty_this_period'] ?? 0),
        'cumulative_qty' => (float)($line['cumulative_qty'] ?? 0),
        'rate' => (float)($line['rate'] ?? 0),
        'amount' => (float)($line['amount'] ?? 0),
    ];
}, IPC::lineItems($ipcId));

$timeline = array_map(static function (array $row): array {
    return [
        'step' => (int)($row['step'] ?? 0),
        'action' => (string)($row['action'] ?? ''),
        'action_label' => status_label($row['action'] ?? ''),
        'actor_name' => trim((string)($row['actor_name'] ?? '')) ?: 'Staff',
        'actor_role' => (string)($row['actor_role'] ?? ''),
        'comments' => (string)($row['comments'] ?? ''),
        'actioned_at' => format_datetime($row['actioned_at'] ?? null),
    ];
}, IPCApproval::timeline($ipcId));

Response::json([
    'success' => true,
    'ipc' => ManagerIPCQueue::payload($ipc),
    'lines' => $lines,
    'line_totals' => IPC::lineTotals($ipcId),
    'timeline' => $timeline,
]);
