<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'auth' => true,
    'csrf' => false,
]);

$userId = (int)Auth::id();
$role = (string)Auth::role();
$projectId = Security::cleanInt($_GET['project_id'] ?? 0);
$date = Security::cleanString((string)($_GET['date'] ?? date('Y-m-d')));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

if ($projectId <= 0) {
    $assignment = Database::fetch(
        'SELECT project_id FROM project_assignments WHERE user_id = ? AND status = "active" ORDER BY assigned_at DESC LIMIT 1',
        [$userId]
    );
    $projectId = (int)($assignment['project_id'] ?? 0);
}

if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'No assigned project was found.'], 404);
}

$assigned = Database::fetch(
    'SELECT id FROM project_assignments WHERE user_id = ? AND project_id = ? AND status = "active" LIMIT 1',
    [$userId, $projectId]
) !== null;

if (!$assigned && $role !== 'superadmin' && $role !== 'manager') {
    Response::json(['success' => false, 'message' => 'You are not assigned to this project.'], 403);
}

$project = Database::fetch(
    'SELECT p.id, p.name, p.slug, c.name AS constituency_name, w.name AS ward_name
     FROM projects p
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     LEFT JOIN wards w ON w.id = p.ward_id
     WHERE p.id = ? LIMIT 1',
    [$projectId]
);
// Auto-open policy window (County Director times) so early arrivals are not blocked.
$gateway = AttendancePolicy::ensureProjectWindow($projectId, $date);
$geoFence = GeoFenceModel::forProject($projectId);
$alreadySigned = AttendanceRecord::todayForAnyTarget($userId);
$isOpen = AttendancePolicy::isEffectivelyOpen($gateway);
$summary = $role === 'clerk' ? ClerkAttendance::summary($userId, $date, $projectId) : [];
$records = $role === 'clerk' ? ClerkAttendance::records($userId, $date, $projectId, [], 80) : [];
$expected = $role === 'clerk' ? ClerkAttendance::expectedPeople($userId, $projectId, $date) : [];
$policy = AttendancePolicy::windowPayload();
$clerkConfirmed = $gateway && !empty($gateway['clerk_confirmed_at']);

Response::json([
    'success' => true,
    'project' => $project,
    'gateway' => $gateway,
    'geo_fence' => $geoFence,
    'is_open' => (bool)$isOpen,
    'clerk_confirmed' => (bool)$clerkConfirmed,
    'already_signed' => (bool)$alreadySigned,
    'attendance' => $alreadySigned,
    'summary' => $summary,
    'records' => $records,
    'expected' => $expected,
    'policy' => $policy,
]);
