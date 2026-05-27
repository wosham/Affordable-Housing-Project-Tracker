<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle(['methods' => ['POST'], 'roles' => ['superadmin'], 'csrf_form' => 'superadmin_approvals']);

$input = Security::jsonInput() ?: $_POST;
$id = Security::cleanInt($input['eot_id'] ?? 0);
$grantedDays = Security::cleanInt($input['granted_days'] ?? 0);

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'EOT id is required.'], 422);
}

$eot = EOTRequest::findDetailed($id);
if (!$eot) {
    Response::json(['success' => false, 'message' => 'EOT request could not be found.'], 404);
}
if (($eot['status'] ?? '') !== 'pending') {
    Response::json(['success' => false, 'message' => 'Only pending EOT requests can be approved.'], 409);
}

$requested = (int)$eot['days_requested'];
$grantedDays = $grantedDays > 0 ? min($grantedDays, $requested) : $requested;
$status = $grantedDays < $requested ? 'partially-granted' : 'granted';

Database::query("UPDATE eot_requests SET status = ?, granted_days = ?, approved_by = ?, approved_at = NOW() WHERE id = ?", [$status, $grantedDays, Auth::id(), $id]);
approval_audit('approve', 'eot_requests', $id, ['eot_number' => $eot['eot_number'], 'granted_days' => $grantedDays]);
Notification::push((int)$eot['submitted_by'], 'eot_approved', 'EOT request approved', 'EOT #' . $eot['eot_number'] . ' for ' . $eot['project_name'] . ' granted ' . $grantedDays . ' day(s).', 'admin/contractor/eot-request.php');

Response::json(['success' => true, 'message' => 'EOT request approved successfully.']);

function approval_audit(string $action, string $module, int $targetId, array $details = []): void
{
    try {
        Database::query('INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)', [Auth::id(), $action, $module, $targetId, json_encode($details), $_SERVER['REMOTE_ADDR'] ?? null, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
    } catch (Throwable) {
    }
}
