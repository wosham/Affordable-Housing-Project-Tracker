<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

Guard::role('superadmin');

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'date_from' => subscribers_export_date($_GET['date_from'] ?? ''),
    'date_to' => subscribers_export_date($_GET['date_to'] ?? ''),
    'source_state' => Security::cleanString((string)($_GET['source_state'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== null);
$rows = Subscriber::exportRows($filters);

if (!headers_sent()) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Ymd-His') . '.csv"');
}

$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'email', 'name', 'status', 'subscribed_at', 'unsubscribed_at', 'reactivated_at', 'ip', 'source_url']);
foreach ($rows as $row) {
    fputcsv($out, [
        $row['id'] ?? '',
        $row['email'] ?? '',
        $row['name'] ?? '',
        $row['status'] ?? '',
        $row['subscribed_at'] ?? '',
        $row['unsubscribed_at'] ?? '',
        $row['reactivated_at'] ?? '',
        $row['ip'] ?? '',
        $row['source_url'] ?? '',
    ]);
}
fclose($out);

function subscribers_export_date(mixed $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}
