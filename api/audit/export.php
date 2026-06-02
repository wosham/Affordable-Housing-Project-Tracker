<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

Guard::role('superadmin');

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'user_id' => Security::cleanInt($_GET['user_id'] ?? 0),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'action' => Security::cleanString((string)($_GET['action'] ?? '')),
    'module' => Security::cleanString((string)($_GET['module'] ?? '')),
    'severity' => Security::cleanString((string)($_GET['severity'] ?? '')),
    'target_id' => Security::cleanInt($_GET['target_id'] ?? 0),
    'ip' => Security::cleanString((string)($_GET['ip'] ?? '')),
    'quick' => Security::cleanString((string)($_GET['quick'] ?? '')),
    'date_from' => audit_export_date($_GET['date_from'] ?? ''),
    'date_to' => audit_export_date($_GET['date_to'] ?? ''),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== null && $value !== 0);
$rows = AuditLog::exportRows($filters);
$filename = 'ahptc-audit-log-' . date('Ymd-His') . '.csv';

if (!headers_sent()) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}

$handle = fopen('php://temp', 'r+');
fwrite($handle, "\xEF\xBB\xBF");
fputcsv($handle, ['id', 'created_at', 'actor', 'email', 'role', 'action', 'module', 'target_id', 'severity', 'ip', 'route', 'request_method', 'details']);
foreach ($rows as $row) {
    fputcsv($handle, array_values($row));
}
rewind($handle);
echo stream_get_contents($handle);
fclose($handle);
exit;

function audit_export_date(mixed $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}
