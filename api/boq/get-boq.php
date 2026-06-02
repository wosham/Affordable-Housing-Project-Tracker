<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager', 'consultant', 'contractor', 'finance', 'clerk'],
    'csrf' => false,
]);

$filters = [
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'section' => Security::cleanString((string)($_GET['section'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'risk' => Security::cleanString((string)($_GET['risk'] ?? '')),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0);

if (empty($filters['project_id'])) {
    Response::json(['success' => false, 'message' => 'Choose a project before loading BOQ items.'], 422);
}

$project = Project::findDetailed((int)$filters['project_id']);
if (!$project) {
    Response::json(['success' => false, 'message' => 'Project could not be found.'], 404);
}

$limit = min(100, max(1, Security::cleanInt($_GET['limit'] ?? 20)));
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = BOQItem::countItems($filters);
$totalPages = max(1, (int)ceil($total / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;
$items = array_map([BOQItem::class, 'payload'], BOQItem::items($filters, $limit, $offset));
$summary = BOQItem::summary($filters);

Response::json([
    'success' => true,
    'project' => [
        'id' => (int)$project['id'],
        'name' => $project['name'],
        'contractor' => $project['contractor_name'] ?? '',
        'constituency' => $project['constituency_name'] ?? '',
    ],
    'summary' => boq_api_summary($summary),
    'sections' => BOQItem::sections((int)$filters['project_id']),
    'items' => array_map('boq_api_item', $items),
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'totalPages' => $totalPages,
    ],
    'filters' => $filters,
]);

function boq_api_summary(array $summary): array
{
    $contractValue = (float)($summary['contract_value'] ?? 0);
    $certifiedValue = (float)($summary['certified_value'] ?? 0);
    $paidValue = (float)($summary['paid_value'] ?? 0);

    return array_merge($summary, [
        'contract_value_formatted' => format_money($contractValue),
        'certified_value_formatted' => format_money($certifiedValue),
        'paid_value_formatted' => format_money($paidValue),
        'certified_percent' => $contractValue > 0 ? percentage(($certifiedValue / $contractValue) * 100) : 0,
        'paid_percent' => $certifiedValue > 0 ? percentage(($paidValue / $certifiedValue) * 100) : 0,
    ]);
}

function boq_api_item(array $item): array
{
    return [
        'id' => (int)$item['id'],
        'projectId' => (int)$item['project_id'],
        'projectName' => $item['project_name'],
        'section' => $item['section'],
        'itemNo' => $item['item_no'],
        'description' => $item['description'],
        'unit' => $item['unit'],
        'quantity' => (float)$item['quantity'],
        'rate' => (float)$item['rate'],
        'amount' => (float)$item['amount'],
        'certifiedQty' => (float)$item['certified_qty'],
        'paidQty' => (float)$item['paid_qty'],
        'remainingQty' => (float)$item['remaining_qty'],
        'certifiedValue' => (float)$item['certified_value'],
        'paidValue' => (float)$item['paid_value'],
        'remainingValue' => (float)$item['remaining_value'],
        'paymentRemaining' => (float)$item['payment_remaining'],
        'certifiedPercent' => (int)$item['certified_percent'],
        'paidPercent' => (int)$item['paid_percent'],
        'status' => $item['status'],
        'statusLabel' => status_label($item['status']),
        'risks' => $item['risks'],
        'updatedAt' => $item['updated_at'] ?? null,
    ];
}
