<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager', 'consultant', 'clerk', 'finance'],
    'csrf' => false,
]);

$csrf = Csrf::fromRequest();
$allowedForms = ['superadmin_boq', 'manager_boq', 'consultant_boq', 'clerk_boq', 'finance_boq', 'default'];
$csrfOk = false;
foreach ($allowedForms as $form) {
    if (Csrf::verify($csrf, $form)) {
        $csrfOk = true;
        break;
    }
}
if (!$csrfOk) {
    Response::json(['success' => false, 'message' => 'CSRF token mismatch. Please refresh the page and try again.'], 419);
}

$input = $_POST;
if ($input === []) {
    $input = Security::jsonInput();
}

$id = Security::cleanInt($input['id'] ?? $input['boq_item_id'] ?? 0);
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'BOQ item id is required.'], 422);
}

$item = BOQItem::findDetailed($id);
if (!$item) {
    Response::json(['success' => false, 'message' => 'BOQ item could not be found.'], 404);
}

$role = (string)Auth::role();
$userId = (int)Auth::id();
$projectId = (int)($item['project_id'] ?? 0);
$canAccess = match ($role) {
    'superadmin' => true,
    'finance' => true,
    'manager' => ManagerBOQ::canAccessProject($userId, $role, $projectId),
    'consultant', 'clerk' => ProjectAssignment::canManageProject($userId, $projectId, $role),
    default => false,
};

if (!$canAccess) {
    Response::json(['success' => false, 'message' => 'You do not have access to this BOQ item.'], 403);
}

$paidEditable = in_array($role, ['superadmin', 'finance'], true);
$coreStatusEditable = in_array($role, ['superadmin', 'consultant', 'clerk'], true);

$certifiedQty = max(0, Security::cleanFloat($input['certified_qty'] ?? $item['certified_qty'] ?? 0));
$paidQty = $paidEditable
    ? max(0, Security::cleanFloat($input['paid_qty'] ?? $item['paid_qty'] ?? 0))
    : (float)($item['paid_qty'] ?? 0);
$status = $coreStatusEditable
    ? Security::cleanString((string)($input['status'] ?? $item['status'] ?? 'active'))
    : (string)($item['status'] ?? 'active');
$notes = Security::cleanString((string)($input['notes'] ?? $item['notes'] ?? ''));
$reviewStatus = Security::cleanString((string)($input['review_status'] ?? $item['review_status'] ?? 'pending'));
$riskStatus = Security::cleanString((string)($input['risk_status'] ?? $item['risk_status'] ?? 'normal'));
$managerNote = Security::cleanString((string)($input['manager_note'] ?? $item['manager_note'] ?? ''));
$force = !empty($input['force']);

if (!in_array($status, BOQItem::statusOptions(), true)) {
    Response::json(['success' => false, 'message' => 'Invalid BOQ status.'], 422);
}
if (!in_array($reviewStatus, ManagerBOQ::REVIEW_STATUSES, true)) {
    Response::json(['success' => false, 'message' => 'Invalid review status.'], 422);
}
if (!in_array($riskStatus, ManagerBOQ::RISK_STATUSES, true)) {
    Response::json(['success' => false, 'message' => 'Invalid risk status.'], 422);
}
if ($managerNote !== '' && strlen($managerNote) > 2000) {
    Response::json(['success' => false, 'message' => 'Manager note is too long.'], 422);
}

$quantity = (float)($item['quantity'] ?? 0);
$warnings = [];
if (SystemConfig::bool('boq.warn_certified_above_contract', true) && $certifiedQty > $quantity) {
    $warnings[] = 'Certified quantity exceeds the contract quantity.';
}
if ($paidQty > $certifiedQty) {
    if (SystemConfig::bool('boq.block_paid_above_certified', true)) {
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

$before = [
    'certified_qty' => (float)($item['certified_qty'] ?? 0),
    'paid_qty' => (float)($item['paid_qty'] ?? 0),
    'status' => (string)($item['status'] ?? ''),
    'review_status' => (string)($item['review_status'] ?? 'pending'),
    'risk_status' => (string)($item['risk_status'] ?? 'normal'),
];
$after = [
    'certified_qty' => $certifiedQty,
    'paid_qty' => $paidQty,
    'status' => $status,
    'review_status' => $reviewStatus,
    'risk_status' => $riskStatus,
];

Database::beginTransaction();
try {
    BOQItem::updateQuantities($id, [
        'certified_qty' => $certifiedQty,
        'paid_qty' => $paidQty,
        'status' => $status,
        'notes' => $notes,
    ], $userId);

    Database::query(
        'UPDATE boq_items
         SET review_status = ?, risk_status = ?, manager_note = ?, last_reviewed_by = ?, last_reviewed_at = NOW(),
             certified_updated_by = CASE WHEN certified_qty <> ? THEN certified_updated_by ELSE ? END,
             paid_updated_by = CASE WHEN paid_qty <> ? THEN paid_updated_by ELSE ? END
         WHERE id = ?',
        [
            $reviewStatus,
            $riskStatus,
            $managerNote !== '' ? $managerNote : null,
            $userId,
            $certifiedQty,
            $userId,
            $paidQty,
            $paidEditable ? $userId : ($item['paid_updated_by'] ?? null),
            $id,
        ]
    );

    ManagerBOQ::recordReviewUpdate($id, $projectId, $userId, $before, $after, $managerNote);

    Logger::log('update', 'boq_items', $id, [
        'old' => $before,
        'new' => $after,
        'warnings' => $warnings,
    ]);

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'BOQ item could not be updated.'], 500);
}

if (in_array($riskStatus, ['high', 'critical'], true) || $warnings !== []) {
    try {
        Notification::pushRole(
            'superadmin',
            'boq_review',
            'BOQ item flagged',
            ($item['project_name'] ?? 'A project') . ' has a BOQ item marked ' . status_label($riskStatus) . '.',
            'admin/superadmin/boq.php?project_id=' . $projectId
        );
    } catch (Throwable) {
    }
}

$updated = ManagerBOQ::findScoped($id, $userId, $role);
Response::json([
    'success' => true,
    'message' => 'BOQ item updated successfully.',
    'warnings' => $warnings,
    'item' => $updated ? ManagerBOQ::payload($updated) : BOQItem::payload(BOQItem::findDetailed($id) ?: []),
]);
