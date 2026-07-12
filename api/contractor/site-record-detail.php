<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['contractor', 'superadmin'],
    'csrf' => false,
]);

$type = strtolower(trim((string)($_GET['type'] ?? '')));
$id = Security::cleanInt($_GET['id'] ?? 0);

if (!isset(ContractorSiteRecord::types()[$type])) {
    Response::json(['success' => false, 'message' => 'Record type is invalid.'], 422);
}
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Record id is required.'], 422);
}

$detail = ContractorSiteRecord::detailForUser($type, $id, (int)Auth::id(), (string)Auth::role());
if (!$detail) {
    Response::json(['success' => false, 'message' => 'Record could not be found.'], 404);
}

$item = $detail['item'];
$config = $detail['config'];
$media = $detail['media'];
$path = (string)(($media['path'] ?? '') ?: ($item['filename'] ?? $item['attachment_path'] ?? ''));

Response::json([
    'success' => true,
    'type' => $type,
    'item' => self_site_record_public_item($type, $item, $config),
    'fields' => self_site_record_detail_fields($type, $item),
    'media' => $media ? [
        'id' => (int)$media['id'],
        'path' => (string)($media['path'] ?? ''),
        'title' => (string)($media['title'] ?? ''),
        'url' => Url::asset((string)($media['path'] ?? '')),
        'type' => (string)($media['type'] ?? ''),
        'extension' => (string)($media['extension'] ?? ''),
        'size' => (int)($media['size'] ?? 0),
    ] : ($path !== '' ? [
        'id' => 0,
        'path' => $path,
        'title' => basename($path),
        'url' => str_starts_with($path, 'uploads/') || str_starts_with($path, 'http') ? (str_starts_with($path, 'http') ? $path : Url::asset($path)) : '',
        'type' => '',
        'extension' => pathinfo($path, PATHINFO_EXTENSION),
        'size' => (int)($item['size'] ?? 0),
    ] : null),
    'edit' => self_site_record_edit_payload($type, $item),
]);

function self_site_record_public_item(string $type, array $item, array $config): array
{
    return [
        'id' => (int)$item['id'],
        'project_id' => (int)$item['project_id'],
        'project_name' => (string)($item['project_name'] ?? ''),
        'status' => (string)($item['status'] ?? $item['category'] ?? ''),
        'title' => self_site_record_title($type, $item),
        'summary' => self_site_record_summary($type, $item),
        'date' => (string)($item[$config['date']] ?? ''),
        'secondary' => self_site_record_secondary($type, $item),
    ];
}

function self_site_record_title(string $type, array $item): string
{
    return match ($type) {
        'documents' => (string)($item['original_name'] ?? 'Document'),
        'equipment' => (string)($item['equipment_type'] ?? 'Equipment'),
        'incidents' => status_label((string)($item['incident_type'] ?? 'incident')) . ' · ' . status_label((string)($item['severity'] ?? '')),
        'labour' => 'Labour ' . format_date($item['diary_date'] ?? null),
        'materials' => (string)($item['material'] ?? 'Delivery'),
        'subcontractors' => (string)($item['company'] ?? 'Subcontractor'),
        default => 'Record',
    };
}

function self_site_record_summary(string $type, array $item): string
{
    return match ($type) {
        'documents' => (string)($item['description'] ?? ''),
        'equipment' => trim((string)($item['owner'] ?? '') . ' · ' . status_label((string)($item['condition'] ?? ''))),
        'incidents' => (string)($item['description'] ?? ''),
        'labour' => 'Skilled ' . (int)($item['skilled_count'] ?? 0) . ' · Unskilled ' . (int)($item['unskilled_count'] ?? 0) . ' · Supervisors ' . (int)($item['supervisor_count'] ?? 0),
        'materials' => (string)($item['supplier'] ?? ''),
        'subcontractors' => (string)($item['scope_of_work'] ?? ''),
        default => '',
    };
}

function self_site_record_secondary(string $type, array $item): string
{
    return match ($type) {
        'documents' => status_label((string)($item['category'] ?? '')) . ' · v' . ($item['version'] ?? '1.0'),
        'equipment' => status_label((string)($item['status'] ?? '')),
        'incidents' => status_label((string)($item['status'] ?? '')),
        'labour' => 'Total ' . (int)($item['total'] ?? 0),
        'materials' => format_number($item['quantity'] ?? 0) . ' ' . ($item['unit'] ?? ''),
        'subcontractors' => format_money($item['contract_value'] ?? 0),
        default => '',
    };
}

function self_site_record_detail_fields(string $type, array $item): array
{
    $rows = match ($type) {
        'documents' => [
            ['label' => 'Category', 'value' => status_label((string)($item['category'] ?? ''))],
            ['label' => 'Version', 'value' => (string)($item['version'] ?? '1.0')],
            ['label' => 'Consultant review', 'value' => status_label((string)($item['consultant_review_status'] ?? 'pending'))],
            ['label' => 'File path', 'value' => (string)($item['filename'] ?? '')],
            ['label' => 'Description', 'value' => (string)($item['description'] ?? ''), 'wide' => true],
            ['label' => 'Review note', 'value' => (string)($item['consultant_review_note'] ?? ''), 'wide' => true],
        ],
        'equipment' => [
            ['label' => 'Registration', 'value' => (string)($item['registration'] ?? '')],
            ['label' => 'Owner', 'value' => (string)($item['owner'] ?? '')],
            ['label' => 'Condition', 'value' => status_label((string)($item['condition'] ?? ''))],
            ['label' => 'Status', 'value' => status_label((string)($item['status'] ?? ''))],
            ['label' => 'On site', 'value' => format_date($item['date_on_site'] ?? null)],
            ['label' => 'Off site', 'value' => format_date($item['date_off_site'] ?? null)],
        ],
        'incidents' => [
            ['label' => 'Date', 'value' => format_date($item['incident_date'] ?? null)],
            ['label' => 'Type', 'value' => status_label((string)($item['incident_type'] ?? ''))],
            ['label' => 'Severity', 'value' => status_label((string)($item['severity'] ?? ''))],
            ['label' => 'Status', 'value' => status_label((string)($item['status'] ?? ''))],
            ['label' => 'Follow-up', 'value' => format_date($item['follow_up_date'] ?? null)],
            ['label' => 'Reported by', 'value' => trim((string)($item['reported_by_name'] ?? ''))],
            ['label' => 'Description', 'value' => (string)($item['description'] ?? ''), 'wide' => true],
            ['label' => 'Persons involved', 'value' => (string)($item['persons_involved'] ?? ''), 'wide' => true],
            ['label' => 'Cause', 'value' => (string)($item['cause'] ?? ''), 'wide' => true],
            ['label' => 'Corrective action', 'value' => (string)($item['corrective_action'] ?? ''), 'wide' => true],
        ],
        'labour' => [
            ['label' => 'Date', 'value' => format_date($item['diary_date'] ?? null)],
            ['label' => 'Skilled', 'value' => (string)(int)($item['skilled_count'] ?? 0)],
            ['label' => 'Unskilled', 'value' => (string)(int)($item['unskilled_count'] ?? 0)],
            ['label' => 'Supervisors', 'value' => (string)(int)($item['supervisor_count'] ?? 0)],
            ['label' => 'Total', 'value' => (string)(int)($item['total'] ?? 0)],
            ['label' => 'Status', 'value' => status_label((string)($item['status'] ?? 'submitted'))],
            ['label' => 'Verification', 'value' => status_label((string)($item['verification_status'] ?? 'pending'))],
        ],
        'materials' => [
            ['label' => 'Supplier', 'value' => (string)($item['supplier'] ?? '')],
            ['label' => 'Delivery date', 'value' => format_date($item['delivery_date'] ?? null)],
            ['label' => 'Quantity', 'value' => format_number($item['quantity'] ?? 0) . ' ' . ($item['unit'] ?? '')],
            ['label' => 'Delivery note', 'value' => (string)($item['delivery_note_no'] ?? '')],
            ['label' => 'Condition', 'value' => status_label((string)($item['condition'] ?? ''))],
            ['label' => 'Status', 'value' => status_label((string)($item['status'] ?? ''))],
            ['label' => 'Verification', 'value' => status_label((string)($item['verification_status'] ?? 'pending'))],
        ],
        'subcontractors' => [
            ['label' => 'Contact', 'value' => (string)($item['contact_person'] ?? '')],
            ['label' => 'Phone', 'value' => (string)($item['phone'] ?? '')],
            ['label' => 'Email', 'value' => (string)($item['email'] ?? '')],
            ['label' => 'Contract value', 'value' => format_money($item['contract_value'] ?? 0)],
            ['label' => 'Compliance', 'value' => status_label((string)($item['compliance_status'] ?? ''))],
            ['label' => 'Risk', 'value' => status_label((string)($item['risk_status'] ?? ''))],
            ['label' => 'Status', 'value' => status_label((string)($item['status'] ?? ''))],
            ['label' => 'Scope', 'value' => (string)($item['scope_of_work'] ?? ''), 'wide' => true],
            ['label' => 'Performance note', 'value' => (string)($item['performance_note'] ?? ''), 'wide' => true],
        ],
        default => [],
    };

    return array_values(array_filter($rows, static fn (array $row): bool => trim((string)($row['value'] ?? '')) !== '' && (string)$row['value'] !== '-'));
}

function self_site_record_edit_payload(string $type, array $item): array
{
    $fields = ContractorSiteRecord::fields($type);
    $payload = ['id' => (int)$item['id'], 'project_id' => (int)$item['project_id']];
    foreach ($fields as $field) {
        $name = (string)($field['name'] ?? '');
        if ($name === '') {
            continue;
        }
        if ($name === 'delivery_note_ref') {
            // Optional media field for materials — seed path from note text if present.
            $payload[$name] = (string)($item['delivery_note_no'] ?? '');
            continue;
        }
        if ($name === 'attachment_path' || $name === 'filename') {
            $payload[$name] = (string)($item[$name] ?? $item['attachment_path'] ?? $item['filename'] ?? '');
            $payload['media_id'] = (int)($item['evidence_media_id'] ?? 0);
            continue;
        }
        $value = $item[$name] ?? '';
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            $value = substr($value, 0, 10);
        }
        $payload[$name] = $value;
    }
    return $payload;
}
