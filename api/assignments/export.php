<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager'],
    'csrf' => false,
]);

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'constituency_id' => Security::cleanInt($_GET['constituency_id'] ?? 0),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
$rows = ProjectAssignment::listForManager((int)Auth::id(), (string)Auth::role(), $filters, 5000, 0);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="project-assignments-' . date('Ymd-His') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'project', 'constituency', 'user', 'email', 'role', 'type', 'scope', 'status', 'primary', 'start_date', 'end_date', 'assigned_by', 'assigned_at']);
foreach ($rows as $row) {
    $item = ProjectAssignment::payload($row);
    fputcsv($out, [
        $item['id'],
        $item['project_name'],
        $item['constituency_name'],
        $item['user_name'],
        $item['email'],
        $item['role_slug'],
        $item['assignment_type'],
        $item['scope'],
        $item['status'],
        $item['is_primary'] ? 'yes' : 'no',
        $item['start_date'],
        $item['end_date'],
        $item['assigned_by_name'],
        $item['assigned_at'],
    ]);
}
fclose($out);
