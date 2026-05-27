<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'auth' => true,
    'csrf' => false,
]);

$projectId = Security::cleanInt($_GET['project_id'] ?? 0);
if ($projectId <= 0) {
    $assignment = Database::fetch(
        'SELECT project_id FROM project_assignments WHERE user_id = ? ORDER BY assigned_at DESC LIMIT 1',
        [(int)Auth::id()]
    );
    $projectId = (int)($assignment['project_id'] ?? 0);
}

if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'No assigned project was found.'], 404);
}

if (Auth::role() !== 'superadmin' && Database::fetch('SELECT id FROM project_assignments WHERE user_id = ? AND project_id = ? LIMIT 1', [(int)Auth::id(), $projectId]) === null) {
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
$gateway = AttendanceGateway::forProjectDate($projectId, date('Y-m-d'));
$geoFence = GeoFenceModel::forProject($projectId);
$alreadySigned = AttendanceRecord::todayForUser((int)Auth::id(), $projectId);
$isOpen = $gateway && (int)$gateway['is_open'] === 1 && strtotime((string)$gateway['closes_at']) >= time();

Response::json([
    'success' => true,
    'project' => $project,
    'gateway' => $gateway,
    'geo_fence' => $geoFence,
    'is_open' => $isOpen,
    'already_signed' => (bool)$alreadySigned,
    'attendance' => $alreadySigned,
]);
