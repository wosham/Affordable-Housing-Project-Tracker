<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager'],
]);

$id = Security::cleanInt($_GET['id'] ?? $_GET['boq_item_id'] ?? 0);
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'BOQ item id is required.'], 422);
}

$item = ManagerBOQ::findScoped($id, (int)Auth::id(), (string)Auth::role());
if (!$item) {
    Response::json(['success' => false, 'message' => 'BOQ item could not be found.'], 404);
}

$usage = array_map(static function (array $row): array {
    return [
        'ipc_id' => (int)($row['ipc_id'] ?? 0),
        'ipc_no' => (string)($row['ipc_no'] ?? ('IPC #' . (int)($row['ipc_id'] ?? 0))),
        'status' => (string)($row['status'] ?? ''),
        'status_label' => status_label($row['status'] ?? ''),
        'claimed_qty' => (float)($row['claimed_qty'] ?? 0),
        'certified_qty' => (float)($row['certified_qty'] ?? 0),
        'certified_value' => (float)($row['certified_value'] ?? 0),
        'submitted_at' => format_datetime($row['submitted_at'] ?? null),
    ];
}, BOQItem::usageByIPC($id));

$history = Database::fetchAll(
    "SELECT bru.*, COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'System') AS user_name
     FROM boq_review_updates bru
     LEFT JOIN users u ON u.id = bru.user_id
     WHERE bru.boq_item_id = ?
     ORDER BY bru.created_at DESC
     LIMIT 12",
    [$id]
);

$history = array_map(static function (array $row): array {
    return [
        'user' => trim((string)($row['user_name'] ?? 'System')) ?: 'System',
        'created_at' => format_datetime($row['created_at'] ?? null),
        'old_certified_qty' => (float)($row['old_certified_qty'] ?? 0),
        'new_certified_qty' => (float)($row['new_certified_qty'] ?? 0),
        'old_paid_qty' => (float)($row['old_paid_qty'] ?? 0),
        'new_paid_qty' => (float)($row['new_paid_qty'] ?? 0),
        'old_review_status' => (string)($row['old_review_status'] ?? ''),
        'new_review_status' => (string)($row['new_review_status'] ?? ''),
        'old_risk_status' => (string)($row['old_risk_status'] ?? ''),
        'new_risk_status' => (string)($row['new_risk_status'] ?? ''),
        'note' => (string)($row['note'] ?? ''),
    ];
}, $history);

Response::json([
    'success' => true,
    'item' => ManagerBOQ::payload($item),
    'usage' => $usage,
    'history' => $history,
]);
