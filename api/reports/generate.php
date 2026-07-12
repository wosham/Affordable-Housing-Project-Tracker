<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

ApiMiddleware::handle([
    'methods' => ['GET', 'POST'],
    'roles' => ['superadmin', 'manager', 'finance'],
    'csrf' => $method === 'POST',
    'csrf_form' => 'reports',
]);

$input = array_merge($_GET, $_POST);
if ($method === 'POST' && $input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$type = Security::cleanString((string)($input['type'] ?? 'executive_summary'));
$format = Security::cleanString((string)($input['format'] ?? 'json'));
$format = in_array($format, ReportBuilder::FORMATS, true) ? $format : 'json';
$filters = ReportBuilder::filters($input);
$role = (string)(Auth::role() ?? '');

if (!ReportBuilder::canGenerate($role, $type)) {
    Response::json(['success' => false, 'message' => 'This report is not available for your role.'], 403);
}

try {
    $scopedFilters = ManagerReport::scopeFilters((int)Auth::id(), $role, $filters);
    $report = ReportBuilder::generate($type, $scopedFilters);
    ReportBuilder::recordRun(
        (int)Auth::id(),
        $type,
        $format,
        $filters,
        (int)($report['row_count'] ?? 0),
        'generated',
        $role === 'manager' ? 'manager' : null,
        $role === 'manager' ? (int)Auth::id() : null
    );
} catch (Throwable $e) {
    ReportBuilder::recordRun(
        (int)Auth::id(),
        $type ?: 'unknown',
        $format,
        $filters,
        0,
        'failed',
        $role === 'manager' ? 'manager' : null,
        $role === 'manager' ? (int)Auth::id() : null
    );
    $status = $role === 'manager' && str_contains($e->getMessage(), 'project') ? 403 : 500;
    Response::json(['success' => false, 'message' => $status === 403 ? $e->getMessage() : 'Report could not be generated.'], $status);
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
