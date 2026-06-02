<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'clerk'],
    'csrf_form' => 'attendance_gateway',
]);

$projectId = attendance_api_project_id();
if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'Choose a project before opening attendance.'], 422);
}

if (!attendance_api_user_can_access_project((int)Auth::id(), $projectId)) {
    Response::json(['success' => false, 'message' => 'You are not assigned to this project.'], 403);
}

$date = date('Y-m-d');
$defaultClose = SystemConfig::text('attendance.signin_end', '18:00');
$closeTime = trim((string)($_POST['closes_at'] ?? $defaultClose));
if (!preg_match('/^\d{2}:\d{2}$/', $closeTime)) {
    $minutes = max(30, SystemConfig::int('attendance.gateway_default_minutes', 720));
    $closeTime = date('H:i', time() + ($minutes * 60));
}

$closesAt = $date . ' ' . $closeTime . ':00';
$existing = AttendanceGateway::forProjectDate($projectId, $date);

if ($existing) {
    Database::query(
        'UPDATE attendance_gateways
         SET opened_by = ?, opened_at = NOW(), closes_at = ?, is_open = 1, notes = ?
         WHERE id = ?',
        [(int)Auth::id(), $closesAt, attendance_api_text('notes'), (int)$existing['id']]
    );
    $gatewayId = (int)$existing['id'];
} else {
    Database::query(
        'INSERT INTO attendance_gateways (project_id, date, opened_by, opened_at, closes_at, is_open, notes)
         VALUES (?, ?, ?, NOW(), ?, 1, ?)',
        [$projectId, $date, (int)Auth::id(), $closesAt, attendance_api_text('notes')]
    );
    $gatewayId = (int)Database::lastInsertId();
}

Logger::log('open', 'attendance_gateways', $gatewayId, ['project_id' => $projectId]);

Response::json([
    'success' => true,
    'message' => 'Attendance gateway is open.',
    'gateway' => AttendanceGateway::forProjectDate($projectId, $date),
]);

function attendance_api_project_id(): int
{
    $projectId = Security::cleanInt($_POST['project_id'] ?? 0);
    if ($projectId > 0) {
        return $projectId;
    }

    $assignment = Database::fetch(
        'SELECT project_id FROM project_assignments WHERE user_id = ? ORDER BY assigned_at DESC LIMIT 1',
        [(int)Auth::id()]
    );

    return (int)($assignment['project_id'] ?? 0);
}

function attendance_api_user_can_access_project(int $userId, int $projectId): bool
{
    if (Auth::role() === 'superadmin') {
        return true;
    }

    return Database::fetch(
        'SELECT id FROM project_assignments WHERE user_id = ? AND project_id = ? LIMIT 1',
        [$userId, $projectId]
    ) !== null;
}

function attendance_api_text(string $key): ?string
{
    $value = trim((string)($_POST[$key] ?? ''));
    return $value !== '' ? Security::cleanString($value) : null;
}
