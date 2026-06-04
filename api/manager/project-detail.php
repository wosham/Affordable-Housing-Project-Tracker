<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager'],
    'csrf' => false,
]);

$projectId = Security::cleanInt($_GET['project_id'] ?? $_GET['id'] ?? 0);
if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'Project id is required.'], 422);
}

$detail = ManagerProject::detail($projectId, (int)Auth::id(), (string)Auth::role());
if (!$detail) {
    Response::json(['success' => false, 'message' => 'Project was not found or access is denied.'], 404);
}

Response::json(['success' => true, 'detail' => manager_project_detail_payload($detail)]);

function manager_project_detail_payload(array $detail): array
{
    $project = $detail['project'];

    return [
        'project' => $project,
        'team' => $detail['team'],
        'milestones' => array_map(static fn (array $row): array => [
            'id' => (int)$row['id'],
            'label' => (string)$row['label'],
            'targetDate' => (string)($row['target_date'] ?? ''),
            'targetLabel' => format_date($row['target_date'] ?? null),
            'status' => (string)$row['status'],
            'statusLabel' => status_label((string)$row['status']),
        ], $detail['milestones']),
        'programme' => [
            'total' => (int)($detail['programme']['total'] ?? 0),
            'completed' => (int)($detail['programme']['completed'] ?? 0),
            'overdue' => (int)($detail['programme']['overdue'] ?? 0),
            'avgProgress' => (int)($detail['programme']['avg_progress'] ?? 0),
        ],
        'ipcs' => array_map(static fn (array $row): array => [
            'id' => (int)$row['id'],
            'number' => (int)$row['ipc_number'],
            'amount' => format_money($row['net_amount'] ?? 0),
            'status' => (string)$row['status'],
            'statusLabel' => status_label((string)$row['status']),
            'submittedAt' => format_datetime($row['submitted_at'] ?? null),
        ], $detail['ipcs']),
        'boq' => [
            'items' => (int)($detail['boq']['items'] ?? 0),
            'contractValue' => format_money($detail['boq']['contract_value'] ?? 0),
            'certifiedValue' => format_money($detail['boq']['certified_value'] ?? 0),
            'paidValue' => format_money($detail['boq']['paid_value'] ?? 0),
        ],
        'attendance' => [
            'total' => (int)($detail['attendance']['total'] ?? 0),
            'present' => (int)($detail['attendance']['present'] ?? 0),
            'flags' => (int)($detail['attendance']['flags'] ?? 0),
        ],
        'notes' => array_map(static fn (array $row): array => [
            'id' => (int)$row['id'],
            'type' => (string)$row['note_type'],
            'title' => (string)$row['title'],
            'body' => (string)($row['body'] ?? ''),
            'severity' => (string)$row['severity'],
            'status' => (string)$row['status'],
            'manager' => trim((string)($row['manager_name'] ?? '')) ?: 'Manager',
            'createdAt' => format_datetime($row['created_at'] ?? null),
        ], $detail['notes']),
        'risks' => array_map(static fn (array $row): array => [
            'type' => (string)$row['item_type'],
            'title' => safe_truncate((string)$row['title'], 90),
            'status' => (string)$row['status'],
            'createdAt' => format_datetime($row['created_at'] ?? null),
        ], $detail['risks']),
    ];
}
