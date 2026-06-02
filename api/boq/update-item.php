<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'consultant', 'clerk'],
    'csrf_form' => 'superadmin_boq',
]);

$input = $_POST;
if ($input === []) {
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    $input = is_array($decoded) ? $decoded : [];
}

$id = Security::cleanInt($input['id'] ?? $input['boq_item_id'] ?? 0);
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'BOQ item id is required.'], 422);
}

$item = BOQItem::findDetailed($id);
if (!$item) {
    Response::json(['success' => false, 'message' => 'BOQ item could not be found.'], 404);
}

$certifiedQty = max(0, (float)($input['certified_qty'] ?? $item['certified_qty'] ?? 0));
$paidQty = max(0, (float)($input['paid_qty'] ?? $item['paid_qty'] ?? 0));
$status = Security::cleanString((string)($input['status'] ?? $item['status'] ?? 'active'));
$notes = Security::cleanString((string)($input['notes'] ?? ''));
$force = !empty($input['force']);

if (!in_array($status, BOQItem::statusOptions(), true)) {
    Response::json(['success' => false, 'message' => 'Invalid BOQ status.'], 422);
}

$quantity = (float)($item['quantity'] ?? 0);
$warnings = [];
$warnCertifiedAboveContract = SystemConfig::bool('boq.warn_certified_above_contract', true);
$blockPaidAboveCertified = SystemConfig::bool('boq.block_paid_above_certified', true);
if ($warnCertifiedAboveContract && $certifiedQty > $quantity) {
    $warnings[] = 'Certified quantity exceeds the contract quantity.';
}
if ($paidQty > $certifiedQty) {
    if ($blockPaidAboveCertified) {
        Response::json([
            'success' => false,
            'message' => 'Paid quantity cannot exceed certified quantity under current BOQ settings.',
            'warnings' => ['Paid quantity exceeds the certified quantity.'],
        ], 422);
    }
    $warnings[] = 'Paid quantity exceeds the certified quantity.';
}
if ($paidQty > $quantity) {
    $warnings[] = 'Paid quantity exceeds the contract quantity.';
}

if ($warnings !== [] && !$force) {
    Response::json([
        'success' => false,
        'message' => 'This update needs confirmation before it can be saved.',
        'warnings' => $warnings,
        'requires_force' => true,
    ], 409);
}

Database::beginTransaction();
try {
    BOQItem::updateQuantities($id, [
        'certified_qty' => $certifiedQty,
        'paid_qty' => $paidQty,
        'status' => $status,
        'notes' => $notes,
    ], (int)Auth::id());

    Logger::log('update', 'boq_items', $id, [
        'old' => [
            'certified_qty' => (float)($item['certified_qty'] ?? 0),
            'paid_qty' => (float)($item['paid_qty'] ?? 0),
            'status' => $item['status'] ?? '',
        ],
        'new' => [
            'certified_qty' => $certifiedQty,
            'paid_qty' => $paidQty,
            'status' => $status,
        ],
        'warnings' => $warnings,
    ]);

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'BOQ item could not be updated.'], 500);
}

$updated = BOQItem::payload(BOQItem::findDetailed($id) ?: []);
Response::json([
    'success' => true,
    'message' => 'BOQ item updated successfully.',
    'warnings' => $warnings,
    'item' => [
        'id' => (int)$updated['id'],
        'certifiedQty' => (float)$updated['certified_qty'],
        'paidQty' => (float)$updated['paid_qty'],
        'certifiedValue' => (float)$updated['certified_value'],
        'paidValue' => (float)$updated['paid_value'],
        'status' => $updated['status'],
        'risks' => $updated['risks'],
    ],
]);
