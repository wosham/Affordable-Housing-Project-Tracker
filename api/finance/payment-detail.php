<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['finance'],
    'csrf' => false,
]);

if ((string)Auth::role() !== 'finance') {
    Response::json(['success' => false, 'message' => 'This finance action is restricted.'], 403);
}

$ipcId = Security::cleanInt($_GET['ipc_id'] ?? 0);
if ($ipcId <= 0) {
    Response::json(['success' => false, 'message' => 'Choose an IPC.'], 422);
}

$detail = FinancePayment::paymentDetail($ipcId);
if (!$detail) {
    Response::json(['success' => false, 'message' => 'IPC could not be found.'], 404);
}

$detail['gross_label'] = format_money((float)($detail['gross_amount'] ?? 0));
$detail['retention_label'] = format_money((float)($detail['retention_amount'] ?? 0));
$detail['net_label'] = format_money((float)($detail['net_amount'] ?? 0));
$detail['outstanding_label'] = format_money((float)($detail['outstanding_amount'] ?? 0));
$detail['approved_at_label'] = format_date($detail['approved_at'] ?? null);

$payments = [];
foreach ((array)($detail['payments'] ?? []) as $pay) {
    $payments[] = [
        'id' => (int)($pay['id'] ?? 0),
        'reference_no' => (string)($pay['reference_no'] ?? ''),
        'amount' => (float)($pay['amount'] ?? 0),
        'amount_label' => format_money((float)($pay['amount'] ?? 0)),
        'payment_date' => format_date($pay['payment_date'] ?? null),
        'payment_method' => status_label((string)($pay['payment_method'] ?? '')),
        'status' => (string)($pay['status'] ?? ''),
        'processed_by_name' => (string)($pay['processed_by_name'] ?? ''),
        'voucher_no' => (string)($pay['voucher_no'] ?? ''),
    ];
}
$detail['payments'] = $payments;
$detail['process_url'] = Url::to('admin/finance/process-payment.php?ipc_id=' . (int)($detail['id'] ?? 0));

Response::json([
    'success' => true,
    'ipc' => $detail,
]);
