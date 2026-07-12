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

if (!isset(ContractorSubmission::types()[$type])) {
    Response::json(['success' => false, 'message' => 'Submission type is invalid.'], 422);
}
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Submission id is required.'], 422);
}

$detail = ContractorSubmission::detailForUser($type, $id, (int)Auth::id(), (string)Auth::role());
if (!$detail) {
    Response::json(['success' => false, 'message' => 'Submission could not be found.'], 404);
}

$item = $detail['item'];
$config = $detail['config'];

Response::json([
    'success' => true,
    'type' => $type,
    'item' => [
        'id' => (int)$item['id'],
        'project_id' => (int)$item['project_id'],
        'project_name' => (string)($item['project_name'] ?? ''),
        'constituency_name' => (string)($item['constituency_name'] ?? ''),
        'ward_name' => (string)($item['ward_name'] ?? ''),
        'status' => (string)($item['status'] ?? ''),
        'reference' => contractor_submission_api_reference($type, $item),
        'title' => contractor_submission_api_title($type, $item),
        'description' => contractor_submission_api_description($type, $item),
        'impact' => contractor_submission_api_impact($type, $item),
        'secondary' => contractor_submission_api_secondary($type, $item),
        'submitted_at' => (string)($item[$config['date']] ?? ''),
        'attachment_count' => (int)($item['attachment_count'] ?? 0),
        'fields' => contractor_submission_api_fields($type, $item),
    ],
    'attachments' => array_map(static function (array $row): array {
        $path = (string)(($row['media_path'] ?? '') ?: ($row['path'] ?? ''));
        $title = (string)(($row['title'] ?? '') ?: ($row['media_title'] ?? 'Support record'));
        return [
            'id' => (int)$row['id'],
            'title' => $title,
            'path' => $path,
            'url' => $path !== '' ? Url::asset($path) : '',
            'media_id' => (int)($row['media_id'] ?? 0),
            'media_type' => (string)($row['media_type'] ?? ''),
            'extension' => (string)($row['media_extension'] ?? pathinfo($path, PATHINFO_EXTENSION)),
            'created_at' => (string)($row['created_at'] ?? ''),
        ];
    }, $detail['attachments']),
]);

function contractor_submission_api_reference(string $type, array $item): string
{
    return match ($type) {
        'eot' => 'EOT #' . (int)($item['eot_number'] ?? 0),
        'variation' => 'VAR #' . (int)($item['vo_number'] ?? 0),
        'rfi' => 'RFI #' . (int)($item['rfi_number'] ?? 0),
        'shop_drawing' => (string)($item['drawing_no'] ?? 'Drawing'),
        'material' => 'MAT #' . (int)($item['id'] ?? 0),
        default => '#' . (int)($item['id'] ?? 0),
    };
}

function contractor_submission_api_title(string $type, array $item): string
{
    return match ($type) {
        'eot' => status_label((string)($item['delay_category'] ?? 'other')),
        'variation' => (string)($item['description'] ?? 'Variation request'),
        'rfi' => (string)($item['subject'] ?? 'RFI'),
        'shop_drawing' => (string)($item['title'] ?? 'Shop drawing'),
        'material' => (string)($item['material'] ?? 'Material'),
        default => 'Submission',
    };
}

function contractor_submission_api_description(string $type, array $item): string
{
    return match ($type) {
        'eot' => (string)(($item['impact_summary'] ?? '') ?: ($item['reason'] ?? '')),
        'variation' => (string)($item['reason'] ?? ''),
        'rfi' => (string)($item['description'] ?? ''),
        'shop_drawing' => (string)($item['document_path'] ?? 'Drawing record'),
        'material' => (string)(($item['specification'] ?? '') ?: ($item['notes'] ?? '')),
        default => '',
    };
}

function contractor_submission_api_impact(string $type, array $item): string
{
    return match ($type) {
        'eot' => format_number($item['days_requested'] ?? 0) . ' days',
        'variation' => format_money($item['amount'] ?? 0) . ' / ' . format_number($item['impact_on_time_days'] ?? 0) . ' days',
        'rfi' => status_label((string)($item['urgency'] ?? 'normal')),
        'shop_drawing' => 'Rev ' . ($item['revision'] ?? 'A'),
        'material' => safe_truncate((string)($item['specification'] ?? '-'), 80),
        default => '-',
    };
}

function contractor_submission_api_secondary(string $type, array $item): string
{
    return match ($type) {
        'shop_drawing' => 'Revision ' . ($item['revision'] ?? 'A'),
        'material' => (string)($item['material'] ?? ''),
        'rfi' => status_label((string)($item['urgency'] ?? 'normal')),
        default => status_label((string)($item['status'] ?? '')),
    };
}

function contractor_submission_api_fields(string $type, array $item): array
{
    return match ($type) {
        'eot' => [
            ['label' => 'Delay category', 'value' => status_label((string)($item['delay_category'] ?? 'other'))],
            ['label' => 'Days requested', 'value' => format_number($item['days_requested'] ?? 0)],
            ['label' => 'Granted days', 'value' => format_number($item['granted_days'] ?? 0)],
            ['label' => 'Impact summary', 'value' => (string)($item['impact_summary'] ?? '')],
            ['label' => 'Reason', 'value' => (string)($item['reason'] ?? '')],
            ['label' => 'Supporting evidence', 'value' => (string)($item['supporting_evidence'] ?? '')],
            ['label' => 'Consultant review', 'value' => status_label((string)($item['consultant_review_status'] ?? 'pending'))],
        ],
        'variation' => [
            ['label' => 'Description', 'value' => (string)($item['description'] ?? '')],
            ['label' => 'Reason', 'value' => (string)($item['reason'] ?? '')],
            ['label' => 'Estimated cost', 'value' => format_money($item['amount'] ?? 0)],
            ['label' => 'Time impact (days)', 'value' => format_number($item['impact_on_time_days'] ?? 0)],
            ['label' => 'Consultant review', 'value' => status_label((string)($item['consultant_review_status'] ?? 'pending'))],
        ],
        'rfi' => [
            ['label' => 'Subject', 'value' => (string)($item['subject'] ?? '')],
            ['label' => 'Priority', 'value' => status_label((string)($item['urgency'] ?? 'normal'))],
            ['label' => 'Question', 'value' => (string)($item['description'] ?? '')],
            ['label' => 'Response', 'value' => (string)($item['response'] ?? '')],
            ['label' => 'Response date', 'value' => (string)($item['response_date'] ?? '')],
        ],
        'shop_drawing' => [
            ['label' => 'Drawing number', 'value' => (string)($item['drawing_no'] ?? '')],
            ['label' => 'Title', 'value' => (string)($item['title'] ?? '')],
            ['label' => 'Revision', 'value' => (string)($item['revision'] ?? 'A')],
            ['label' => 'Document reference', 'value' => (string)($item['document_path'] ?? '')],
        ],
        'material' => [
            ['label' => 'Material', 'value' => (string)($item['material'] ?? '')],
            ['label' => 'Specification', 'value' => (string)($item['specification'] ?? '')],
            ['label' => 'Notes', 'value' => (string)($item['notes'] ?? '')],
        ],
        default => [],
    };
}
