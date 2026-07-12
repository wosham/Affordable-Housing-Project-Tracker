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
fputcsv($out, ['id', 'email', 'name', 'status', 'subscribed_at', 'unsubscribed_at', 'reactivated_at', 'ip', 'source_url', 'unsubscribe_url']);
foreach ($rows as $row) {
    fputcsv($out, [
        subscribers_csv_value($row['id'] ?? ''),
        subscribers_csv_value($row['email'] ?? ''),
        subscribers_csv_value($row['name'] ?? ''),
        subscribers_csv_value($row['status'] ?? ''),
        subscribers_csv_value($row['subscribed_at'] ?? ''),
        subscribers_csv_value($row['unsubscribed_at'] ?? ''),
        subscribers_csv_value($row['reactivated_at'] ?? ''),
        subscribers_csv_value($row['ip'] ?? ''),
        subscribers_csv_value($row['source_url'] ?? ''),
        subscribers_csv_value(Subscriber::unsubscribeUrl($row)),
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

function subscribers_csv_value(mixed $value): string
{
    $value = (string)$value;
    return preg_match('/^[=+\-@]/', $value) === 1 ? "'" . $value : $value;
}
