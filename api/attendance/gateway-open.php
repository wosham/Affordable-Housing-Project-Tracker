<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['clerk'],
    'csrf_form' => 'attendance_gateway',
]);

$userId = (int)Auth::id();
$projectId = Security::cleanInt($_POST['project_id'] ?? 0);
$date = Security::cleanString((string)($_POST['date'] ?? date('Y-m-d')));
$notes = trim((string)($_POST['notes'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
if ($projectId <= 0) {
    $projectId = ClerkAttendance::defaultProjectId($userId);
}
if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'Choose an assigned project before confirming site open.'], 422);
}

try {
    // Auto-open policy window first, then clerk confirm (no clerk-set times).
    AttendancePolicy::ensureProjectWindow($projectId, $date);
    $gatewayId = ClerkAttendance::openGateway($userId, $projectId, $date, '', $notes !== '' ? Security::cleanString($notes) : 'Site open confirmed by clerk.');
    Logger::log('confirm', 'attendance_gateways', $gatewayId, ['project_id' => $projectId, 'date' => $date, 'mode' => 'clerk_confirmed']);
    Notification::pushRole('manager', 'attendance', 'Site open confirmed', 'A clerk confirmed site open under County Director attendance policy.', 'admin/manager/attendance-summary.php');

    Response::json([
        'success' => true,
        'message' => 'Site open confirmed. Attendance follows County Director policy window.',
        'gateway' => AttendanceGateway::forProjectDate($projectId, $date),
        'policy' => AttendancePolicy::windowPayload(),
        'summary' => ClerkAttendance::summary($userId, $date, $projectId),
    ]);
} catch (RuntimeException $e) {
    Response::json(['success' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Site open could not be confirmed.'], 500);
}
