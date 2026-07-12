<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['contractor', 'superadmin'],
    'csrf' => false,
]);

$id = Security::cleanInt($_GET['id'] ?? 0);
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'IPC id is required.'], 422);
}

$detail = ContractorIPC::claimDetail($id, (int)Auth::id(), (string)Auth::role());
if (!$detail) {
    Response::json(['success' => false, 'message' => 'IPC claim could not be found.'], 404);
}

$ipc = $detail['ipc'];
Response::json([
    'success' => true,
    'ipc' => [
        'id' => (int)$ipc['id'],
        'ipc_number' => (int)$ipc['ipc_number'],
        'project_id' => (int)$ipc['project_id'],
        'project_name' => (string)($ipc['project_name'] ?? ''),
        'contractor_reference' => (string)($ipc['contractor_reference'] ?? ''),
        'status' => (string)($ipc['status'] ?? ''),
        'period_from' => (string)($ipc['period_from'] ?? ''),
        'period_to' => (string)($ipc['period_to'] ?? ''),
        'gross_amount' => (float)($ipc['gross_amount'] ?? 0),
        'retention_amount' => (float)($ipc['retention_amount'] ?? 0),
        'net_amount' => (float)($ipc['net_amount'] ?? 0),
        'submitted_at' => (string)($ipc['submitted_at'] ?? ''),
        'updated_at' => (string)($ipc['updated_at'] ?? ''),
    ],
    'lines' => array_map(static function (array $line): array {
        return [
            'item_no' => (string)($line['item_no'] ?? ''),
            'section' => (string)($line['section'] ?? ''),
            'unit' => (string)($line['unit'] ?? ''),
            'description' => (string)($line['description'] ?? ''),
            'qty_this_period' => (float)($line['qty_this_period'] ?? 0),
            'cumulative_qty' => (float)($line['cumulative_qty'] ?? 0),
            'rate' => (float)($line['rate'] ?? 0),
            'amount' => (float)($line['amount'] ?? 0),
        ];
    }, $detail['lines']),
    'payment' => $detail['payment'] ? [
        'amount' => (float)($detail['payment']['amount'] ?? 0),
        'payment_date' => (string)($detail['payment']['payment_date'] ?? ''),
        'reference_no' => (string)($detail['payment']['reference_no'] ?? ''),
        'bank' => (string)($detail['payment']['bank'] ?? ''),
        'payment_method' => (string)($detail['payment']['payment_method'] ?? ''),
        'receipt_path' => (string)($detail['payment']['receipt_path'] ?? ''),
    ] : null,
    'workflow' => $detail['workflow'],
    'approvals' => $detail['approvals'],
]);
