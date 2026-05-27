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

$filters = [
    'date' => $date,
    'project_id' => isset($_GET['project_id']) && ctype_digit((string)$_GET['project_id']) ? (int)$_GET['project_id'] : null,
    'constituency_id' => isset($_GET['constituency_id']) && ctype_digit((string)$_GET['constituency_id']) ? (int)$_GET['constituency_id'] : null,
    'role' => trim((string)($_GET['role'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'gps' => trim((string)($_GET['gps'] ?? '')),
    'q' => trim((string)($_GET['q'] ?? '')),
];

$filters = array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== '');
$stats = AttendanceRecord::stats($date, $filters);
$rows = AttendanceRecord::detailed($filters, 500, 0);
$projectSummaries = AttendanceRecord::projectSummaries($date, $filters);

$gateways = Database::fetchAll(
    'SELECT ag.*, p.name AS project_name, c.name AS constituency_name,
            CONCAT(u.first_name, " ", u.last_name) AS opened_by_name
     FROM attendance_gateways ag
     JOIN projects p ON p.id = ag.project_id
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     LEFT JOIN users u ON u.id = ag.opened_by
     WHERE ag.date = ?
     ORDER BY p.name ASC',
    [$date]
);

$missingGeo = Database::fetchAll(
    'SELECT p.id, p.name, c.name AS constituency_name
     FROM projects p
     LEFT JOIN constituencies c ON c.id = p.constituency_id
     LEFT JOIN geo_fences gf ON gf.project_id = p.id
     WHERE gf.id IS NULL OR gf.status IN ("missing", "needs-review")
     ORDER BY p.name ASC',
);

Response::json([
    'success' => true,
    'date' => $date,
    'totals' => [
        'records' => (int)($stats['total_records'] ?? 0),
        'present' => (int)($stats['present'] ?? 0),
        'absent' => (int)($stats['absent'] ?? 0),
        'geo_fail' => (int)($stats['geo_fail'] ?? 0),
        'late' => (int)($stats['late'] ?? 0),
        'outside_window' => (int)($stats['outside_window'] ?? 0),
        'projects_with_records' => (int)($stats['projects_with_records'] ?? 0),
    ],
    'project_summaries' => $projectSummaries,
    'gateways' => $gateways,
    'missing_geo_fences' => $missingGeo,
    'records' => $rows,
]);
