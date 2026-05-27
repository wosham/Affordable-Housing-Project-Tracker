<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'clerk'],
    'csrf_form' => 'attendance_gateway',
]);

$projectId = Security::cleanInt($_POST['project_id'] ?? 0);
if ($projectId <= 0) {
    $assignment = Database::fetch(
        'SELECT project_id FROM project_assignments WHERE user_id = ? ORDER BY assigned_at DESC LIMIT 1',
        [(int)Auth::id()]
    );
    $projectId = (int)($assignment['project_id'] ?? 0);
}

if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'Choose a project before closing attendance.'], 422);
}

if (Auth::role() !== 'superadmin' && Database::fetch('SELECT id FROM project_assignments WHERE user_id = ? AND project_id = ? LIMIT 1', [(int)Auth::id(), $projectId]) === null) {
    Response::json(['success' => false, 'message' => 'You are not assigned to this project.'], 403);
}

$gateway = AttendanceGateway::forProjectDate($projectId, date('Y-m-d'));
if (!$gateway) {
    Response::json(['success' => false, 'message' => 'No attendance gateway is open for this project today.'], 404);
}

Database::query(
    'UPDATE attendance_gateways SET is_open = 0, closes_at = NOW(), notes = COALESCE(NULLIF(?, ""), notes) WHERE id = ?',
    [trim((string)($_POST['notes'] ?? '')), (int)$gateway['id']]
);

Logger::log('close', 'attendance_gateways', (int)$gateway['id'], ['project_id' => $projectId]);

Response::json([
    'success' => true,
    'message' => 'Attendance gateway is closed.',
    'gateway' => AttendanceGateway::forProjectDate($projectId, date('Y-m-d')),
]);
