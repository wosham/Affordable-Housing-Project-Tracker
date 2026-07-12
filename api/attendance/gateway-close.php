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
    Response::json(['success' => false, 'message' => 'Choose an assigned project before closing attendance.'], 422);
}

try {
    $gatewayId = ClerkAttendance::closeGateway($userId, $projectId, $date, $notes !== '' ? Security::cleanString($notes) : null);
    Logger::log('close', 'attendance_gateways', $gatewayId, ['project_id' => $projectId, 'date' => $date]);
    Notification::pushRole('manager', 'attendance', 'Attendance gateway closed', 'Attendance has been closed for a clerk-managed site.', 'admin/manager/attendance-summary.php');
    Notification::pushRole('superadmin', 'attendance', 'Attendance gateway closed', 'Attendance has been closed for a project site.', 'admin/superadmin/attendance.php');

    Response::json([
        'success' => true,
        'message' => 'Attendance gateway is closed.',
        'gateway' => AttendanceGateway::forProjectDate($projectId, $date),
        'summary' => ClerkAttendance::summary($userId, $date, $projectId),
    ]);
} catch (RuntimeException $e) {
    Response::json(['success' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Attendance gateway could not be closed.'], 500);
}
