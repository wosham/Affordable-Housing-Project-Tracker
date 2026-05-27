<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle(['methods' => ['POST'], 'roles' => ['superadmin'], 'csrf_form' => 'superadmin_approvals']);

$input = Security::jsonInput() ?: $_POST;
$id = Security::cleanInt($input['eot_id'] ?? 0);
$reason = trim(strip_tags((string)($input['reason'] ?? '')));

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'EOT id is required.'], 422);
}
if ($reason === '') {
    Response::json(['success' => false, 'message' => 'A rejection reason is required.'], 422);
}

$eot = EOTRequest::findDetailed($id);
if (!$eot) {
    Response::json(['success' => false, 'message' => 'EOT request could not be found.'], 404);
}
if (($eot['status'] ?? '') !== 'pending') {
    Response::json(['success' => false, 'message' => 'Only pending EOT requests can be rejected.'], 409);
}

Database::query("UPDATE eot_requests SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?", [Auth::id(), $id]);
approval_audit('reject', 'eot_requests', $id, ['eot_number' => $eot['eot_number'], 'reason' => $reason]);
Notification::push((int)$eot['submitted_by'], 'eot_rejected', 'EOT request rejected', 'EOT #' . $eot['eot_number'] . ' for ' . $eot['project_name'] . ' was rejected: ' . $reason, 'admin/contractor/eot-request.php');

Response::json(['success' => true, 'message' => 'EOT request rejected successfully.']);

function approval_audit(string $action, string $module, int $targetId, array $details = []): void
{
    try {
        Database::query('INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)', [Auth::id(), $action, $module, $targetId, json_encode($details), $_SERVER['REMOTE_ADDR'] ?? null, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
    } catch (Throwable) {
    }
}
