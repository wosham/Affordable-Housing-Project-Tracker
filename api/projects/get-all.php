<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

$filters = [
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'constituency' => Security::cleanString((string)($_GET['constituency'] ?? '')),
    'category' => Security::cleanString((string)($_GET['category'] ?? '')),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
];

if (isset($_GET['featured']) && $_GET['featured'] !== '') {
    $filters['featured'] = (string)(int)$_GET['featured'];
}

$filters = array_filter($filters, static fn ($value): bool => $value !== '');
$limit = min(100, max(1, Security::cleanInt($_GET['limit'] ?? 10)));
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = Project::countWithFilters($filters);
$totalPages = max(1, (int)ceil($total / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;
$projects = Project::withRelations($filters, $limit, $offset);

Response::json([
    'success' => true,
    'count' => count($projects),
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'totalPages' => $totalPages,
    'filters' => $filters,
    'projects' => array_map('project_api_payload', $projects),
]);

function project_api_payload(array $project): array
{
    $images = json_decode((string)($project['images_json'] ?? '[]'), true);
    $images = is_array($images) ? array_values(array_filter($images)) : [];

    return [
        'id' => (int)$project['id'],
        'slug' => $project['slug'],
        'name' => $project['name'],
        'status' => $project['status'],
        'statusLabel' => status_label($project['status']),
        'progress' => percentage($project['pct_complete']),
        'units' => (int)($project['units'] ?? 0),
        'contractSum' => (float)($project['contract_sum'] ?? 0),
        'contractSumFormatted' => format_money($project['contract_sum'] ?? 0),
        'startDate' => $project['start_date'],
        'estimatedDelivery' => $project['est_delivery'],
        'currentMilestone' => $project['current_milestone'],
        'location' => $project['location_label'],
        'description' => $project['description'],
        'heroImage' => $project['hero_image'] ?: ($images[0] ?? null),
        'images' => $images,
        'fundingSource' => $project['funding_source'],
        'leadAgency' => $project['lead_agency'],
        'contractor' => $project['contractor_name'] ?? null,
        'siteEngineer' => $project['site_engineer'],
        'isFeatured' => (bool)$project['is_featured'],
        'category' => [
            'name' => $project['category_name'],
            'slug' => $project['category_slug'],
            'icon' => $project['category_icon'],
        ],
        'constituency' => [
            'name' => $project['constituency_name'],
            'slug' => $project['constituency_slug'],
        ],
        'ward' => [
            'name' => $project['ward_name'],
            'slug' => $project['ward_slug'],
        ],
        'links' => [
            'public' => Url::to('project-detail.php?id=' . urlencode((string)$project['slug'])),
            'api' => Url::to('api/projects/get-project.php?slug=' . urlencode((string)$project['slug'])),
        ],
        'updatedAt' => $project['updated_at'] ?? null,
    ];
}
