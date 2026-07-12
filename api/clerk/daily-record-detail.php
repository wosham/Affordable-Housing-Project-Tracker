<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['clerk'],
]);

$type = trim((string)($_GET['type'] ?? ''));
$id = Security::cleanInt($_GET['id'] ?? 0);

try {
    $config = ClerkDailyRecord::config($type);
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Unsupported record type.'], 422);
}

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Record id is required.'], 422);
}

$row = ClerkDailyRecord::find($type, (int)Auth::id(), $id);
if (!$row) {
    Response::json(['success' => false, 'message' => 'Record not found or not assigned to you.'], 404);
}

$status = match ($type) {
    'diary' => (string)($row['status'] ?? 'submitted'),
    'weather' => (string)($row['impact_level'] ?? 'none'),
    'labour', 'materials' => (string)($row['verification_status'] ?? 'pending'),
    'equipment' => (string)($row['check_status'] ?? 'pending'),
    default => 'pending',
};

$date = match ($type) {
    'diary', 'labour' => (string)($row['diary_date'] ?? ''),
    'weather' => (string)($row['log_date'] ?? ''),
    'materials' => (string)($row['delivery_date'] ?? ''),
    'equipment' => (string)($row['date_on_site'] ?? $row['checked_at'] ?? ''),
    default => '',
};

$title = match ($type) {
    'diary' => (string)($row['report_title'] ?? 'Daily diary'),
    'weather' => 'Weather ' . ($date !== '' ? $date : 'log'),
    'labour' => 'Labour verification ' . ($date !== '' ? $date : ''),
    'materials' => (string)($row['material'] ?? 'Material delivery'),
    'equipment' => (string)($row['equipment_type'] ?? 'Equipment'),
    default => $config['title'],
};

$fields = match ($type) {
    'diary' => [
        ['label' => 'Work done', 'value' => (string)($row['work_done'] ?? '—'), 'wide' => true],
        ['label' => 'Issues', 'value' => (string)($row['issues_raised'] ?? '—'), 'wide' => true],
        ['label' => 'Safety', 'value' => (string)($row['safety_observations'] ?? '—'), 'wide' => true],
        ['label' => 'Visitors / instructions', 'value' => (string)($row['visitors_instructions'] ?? '—'), 'wide' => true],
        ['label' => 'Next day plan', 'value' => (string)($row['next_day_plan'] ?? '—'), 'wide' => true],
        ['label' => 'Weather summary', 'value' => (string)($row['weather_summary'] ?? '—')],
    ],
    'weather' => [
        ['label' => 'Morning', 'value' => status_label((string)($row['morning_condition'] ?? ''))],
        ['label' => 'Afternoon', 'value' => status_label((string)($row['afternoon_condition'] ?? ''))],
        ['label' => 'Rainfall mm', 'value' => number_format((float)($row['rainfall_mm'] ?? 0), 1)],
        ['label' => 'Temp min/max', 'value' => trim(($row['temperature_min'] ?? '—') . ' / ' . ($row['temperature_max'] ?? '—'))],
        ['label' => 'Working hours', 'value' => number_format((float)($row['working_hours'] ?? 0), 1)],
        ['label' => 'Hours lost', 'value' => number_format((float)($row['working_hours_lost'] ?? 0), 1)],
        ['label' => 'Remarks', 'value' => (string)($row['remarks'] ?? '—'), 'wide' => true],
    ],
    'labour' => [
        ['label' => 'Contractor skilled', 'value' => (string)(int)($row['skilled_count'] ?? 0)],
        ['label' => 'Contractor unskilled', 'value' => (string)(int)($row['unskilled_count'] ?? 0)],
        ['label' => 'Contractor supervisors', 'value' => (string)(int)($row['supervisor_count'] ?? 0)],
        ['label' => 'Contractor total', 'value' => (string)(int)($row['total'] ?? 0)],
        ['label' => 'Clerk skilled', 'value' => (string)(int)($row['clerk_skilled_count'] ?? 0)],
        ['label' => 'Clerk unskilled', 'value' => (string)(int)($row['clerk_unskilled_count'] ?? 0)],
        ['label' => 'Clerk supervisors', 'value' => (string)(int)($row['clerk_supervisor_count'] ?? 0)],
        ['label' => 'Clerk total', 'value' => (string)(int)($row['clerk_total'] ?? 0)],
        ['label' => 'Variance', 'value' => (string)(int)($row['variance_total'] ?? 0)],
        ['label' => 'Notes', 'value' => (string)($row['verification_notes'] ?? '—'), 'wide' => true],
    ],
    'materials' => [
        ['label' => 'Supplier', 'value' => (string)($row['supplier'] ?? '—')],
        ['label' => 'Delivery note', 'value' => (string)($row['delivery_note_no'] ?? '—')],
        ['label' => 'Delivered qty', 'value' => number_format((float)($row['quantity'] ?? 0), 3) . ' ' . (string)($row['unit'] ?? '')],
        ['label' => 'Verified qty', 'value' => number_format((float)($row['verified_quantity'] ?? 0), 3) . ' ' . (string)($row['unit'] ?? '')],
        ['label' => 'Condition', 'value' => status_label((string)($row['condition'] ?? ''))],
        ['label' => 'Workflow status', 'value' => status_label((string)($row['status'] ?? ''))],
        ['label' => 'Notes', 'value' => (string)($row['verification_notes'] ?? '—'), 'wide' => true],
    ],
    'equipment' => [
        ['label' => 'Registration', 'value' => (string)($row['registration'] ?? '—')],
        ['label' => 'Owner', 'value' => (string)($row['owner'] ?? '—')],
        ['label' => 'Condition', 'value' => status_label((string)($row['condition'] ?? ''))],
        ['label' => 'Site status', 'value' => status_label((string)($row['status'] ?? ''))],
        ['label' => 'On site', 'value' => (string)($row['date_on_site'] ?? '—')],
        ['label' => 'Off site', 'value' => (string)($row['date_off_site'] ?? '—')],
        ['label' => 'Check notes', 'value' => (string)($row['check_notes'] ?? '—'), 'wide' => true],
    ],
    default => [],
};

Response::json([
    'success' => true,
    'type' => $type,
    'record' => $row,
    'view' => [
        'id' => (int)$row['id'],
        'title' => $title,
        'project_name' => (string)($row['project_name'] ?? ''),
        'status' => $status,
        'date' => $date,
        'fields' => $fields,
    ],
]);
