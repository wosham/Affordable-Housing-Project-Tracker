<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle(['methods' => ['POST'], 'roles' => ['superadmin'], 'csrf_form' => 'superadmin_approvals']);

$input = Security::jsonInput() ?: $_POST;
$id = Security::cleanInt($input['variation_id'] ?? 0);
$reason = trim(strip_tags((string)($input['reason'] ?? '')));

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Variation id is required.'], 422);
}
if ($reason === '') {
    Response::json(['success' => false, 'message' => 'A rejection reason is required.'], 422);
}

$variation = Variation::findDetailed($id);
if (!$variation) {
    Response::json(['success' => false, 'message' => 'Variation could not be found.'], 404);
}
if (($variation['status'] ?? '') !== 'pending') {
    Response::json(['success' => false, 'message' => 'Only pending variations can be rejected.'], 409);
}

Database::query("UPDATE variations SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?", [Auth::id(), $id]);
approval_audit('reject', 'variations', $id, ['vo_number' => $variation['vo_number'], 'reason' => $reason]);
Notification::push((int)$variation['submitted_by'], 'variation_rejected', 'Variation rejected', 'VO #' . $variation['vo_number'] . ' for ' . $variation['project_name'] . ' was rejected: ' . $reason, 'admin/contractor/variation-request.php');

Response::json(['success' => true, 'message' => 'Variation rejected successfully.']);

function approval_audit(string $action, string $module, int $targetId, array $details = []): void
{
    try {
        Database::query('INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)', [Auth::id(), $action, $module, $targetId, json_encode($details), $_SERVER['REMOTE_ADDR'] ?? null, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
    } catch (Throwable) {
    }
}
