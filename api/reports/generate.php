<?php
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

Guard::auth();

$role = (string)(Auth::role() ?? '');
if (!in_array($role, ['superadmin', 'manager', 'finance'], true)) {
    Response::json(['success' => false, 'message' => 'You are not allowed to generate reports.'], 403);
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    CsrfMiddleware::handleApi('reports');
}

$input = array_merge($_GET, $_POST);
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$type = Security::cleanString((string)($input['type'] ?? 'executive_summary'));
$format = Security::cleanString((string)($input['format'] ?? 'json'));
$format = in_array($format, ReportBuilder::FORMATS, true) ? $format : 'json';
$filters = ReportBuilder::filters($input);

if (!ReportBuilder::canGenerate($role, $type)) {
    Response::json(['success' => false, 'message' => 'This report is not available for your role.'], 403);
}

try {
    $report = ReportBuilder::generate($type, $filters);
    ReportBuilder::recordRun((int)Auth::id(), $type, $format, $filters, (int)($report['row_count'] ?? 0));
} catch (Throwable $e) {
    ReportBuilder::recordRun((int)Auth::id(), $type ?: 'unknown', $format, $filters, 0, 'failed');
    Response::json(['success' => false, 'message' => 'Report could not be generated.'], 500);
}

$filename = 'ahptc-' . preg_replace('/[^a-z0-9_-]+/i', '-', $type) . '-' . date('Ymd-His');

if ($format === 'csv') {
    if (!headers_sent()) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    }
    echo ReportBuilder::csv($report);
    exit;
}

if ($format === 'html') {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $filename . '.html"');
    }
    echo ReportBuilder::html($report);
    exit;
}

Response::json([
    'success' => true,
    'report' => $report,
]);
