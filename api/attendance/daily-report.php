<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager', 'clerk'],
]);

$date = trim((string)($_GET['date'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    Response::json(['success' => false, 'message' => 'Invalid report date.'], 422);
}

$role = (string)Auth::role();
$userId = (int)Auth::id();
$limit = max(1, min(500, Security::cleanInt($_GET['limit'] ?? 200)));
$offset = max(0, Security::cleanInt($_GET['offset'] ?? 0));

$filters = array_filter([
    'date' => $date,
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'constituency_id' => Security::cleanInt($_GET['constituency_id'] ?? 0),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'gps' => Security::cleanString((string)($_GET['gps'] ?? '')),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
], static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== 0);

if (!in_array((string)($filters['role'] ?? ''), ManagerAttendance::ROLES, true)) {
    unset($filters['role']);
}
if (!in_array((string)($filters['status'] ?? ''), ManagerAttendance::STATUSES, true)) {
    unset($filters['status']);
}
if (!in_array((string)($filters['gps'] ?? ''), ['', 'flagged'], true)) {
    unset($filters['gps']);
}

$stats = ManagerAttendance::summary($userId, $role, $date, $filters);
$rows = ManagerAttendance::records($userId, $role, $filters, $limit, $offset);
$projectSummaries = ManagerAttendance::projectCoverage($userId, $role, $date, $filters);
$gateways = ManagerAttendance::gateways($userId, $role, $date, $filters);
$exceptions = ManagerAttendance::exceptions($userId, $role, $date, $filters);

Response::json([
    'success' => true,
    'date' => $date,
    'scope' => $role === 'superadmin' ? 'global' : 'assigned-projects',
    'totals' => $stats,
    'project_summaries' => $projectSummaries,
    'gateways' => $gateways,
    'missing_geo_fences' => ManagerAttendance::missingGeoFences($userId, $role, $filters),
    'exceptions' => $exceptions,
    'records' => $rows,
    'pagination' => [
        'limit' => $limit,
        'offset' => $offset,
        'returned' => count($rows),
    ],
]);
