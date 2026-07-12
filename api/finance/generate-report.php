<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET', 'POST'],
    'roles' => ['finance'],
    'csrf_form' => 'finance_reports',
]);

if ((string)Auth::role() !== 'finance') {
    Response::json(['success' => false, 'message' => 'This finance action is restricted.'], 403);
}

$input = array_merge($_GET, $_POST);
if (($input === [] || strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') && str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
    $json = Security::jsonInput();
    $input = array_merge($input, $json);
}

$clean = FinanceReports::cleanInput($input);

try {
    $report = FinanceReports::generate($clean['type'], $clean['filters']);
    $format = $clean['format'];
    if (!empty($clean['auto_print']) && $format === 'html') {
        $format = 'print';
    }
    FinanceReports::recordRun((int)Auth::id(), $clean['type'], $format, $clean['filters'], (int)$report['row_count']);
} catch (Throwable) {
    FinanceReports::recordRun((int)Auth::id(), $clean['type'], $clean['format'], $clean['filters'], 0, 'failed');
    Response::json(['success' => false, 'message' => 'Finance report could not be generated.'], 500);
}

$filename = 'finance-' . preg_replace('/[^a-z0-9_-]+/i', '-', $clean['type']) . '-' . date('Ymd-His');

if ($clean['format'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    echo FinanceReports::csv($report);
    exit;
}

if ($clean['format'] === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '.html"');
    echo FinanceReports::html($report, !empty($clean['auto_print']));
    exit;
}

Response::json(['success' => true, 'report' => $report]);
