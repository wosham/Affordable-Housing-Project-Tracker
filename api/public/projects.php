<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'csrf' => false,
]);

$limit = max(1, min(30, (int)($_GET['limit'] ?? 6)));
$page = max(1, min(100, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $limit;

$filters = [
    'q' => Security::cleanString((string)($_GET['search'] ?? $_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'constituency' => Security::cleanString((string)($_GET['constituency'] ?? '')),
    'category' => Security::cleanString((string)($_GET['category'] ?? '')),
    'sort' => Security::cleanString((string)($_GET['sort'] ?? 'pct-desc')),
];

try {
    PublicApi::sessionRateLimit('public_projects_' . PublicApi::clientIp(), SystemConfig::int('public.projects_rate_limit_1m', 60) ?: 60, 60, 'Please wait a moment before loading more projects.');

    $total = Project::publicCount($filters);
    $projects = array_map('public_project_api_item', Project::publicListing($filters, $limit, $offset));
    $stats = Project::publicStats($filters);
    $totalPages = max(1, (int)ceil($total / $limit));

    PublicApi::ok([
        'items' => $projects,
        'stats' => $stats,
        'filters' => [
            'search' => $filters['q'],
            'status' => $filters['status'],
            'constituency' => $filters['constituency'],
            'category' => $filters['category'],
            'sort' => $filters['sort'],
        ],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1,
        ],
    ]);
} catch (Throwable $e) {
    PublicApi::log('Public projects API failed', ['error' => $e->getMessage()]);
    PublicApi::fail('Unable to load projects right now.', 500);
}

function public_project_api_item(array $project): array
{
    return [
        'name' => (string)($project['name'] ?? ''),
        'slug' => (string)($project['slug'] ?? ''),
        'location_label' => (string)($project['location_label'] ?? ''),
        'ward_name' => (string)($project['ward_name'] ?? ''),
        'ward_slug' => (string)($project['ward_slug'] ?? ''),
        'status' => (string)($project['status'] ?? ''),
        'public_status' => (string)($project['public_status'] ?? ''),
        'status_label' => (string)($project['status_label'] ?? ''),
        'pct_complete' => (int)($project['pct_complete'] ?? 0),
        'units' => (int)($project['units'] ?? 0),
        'output_label' => public_project_output_label($project),
        'current_milestone' => (string)($project['current_milestone'] ?? ''),
        'contractor_name' => (string)($project['contractor_name'] ?? ''),
        'description' => (string)($project['description'] ?? ''),
        'hero_image' => (string)($project['hero_image'] ?? ''),
        'start_date' => (string)($project['start_date'] ?? ''),
        'est_delivery' => (string)($project['est_delivery'] ?? ''),
        'constituency_name' => (string)($project['constituency_name'] ?? ''),
        'constituency_slug' => (string)($project['constituency_slug'] ?? ''),
        'category_name' => (string)($project['category_name'] ?? ''),
        'category_slug' => (string)($project['category_slug'] ?? ''),
        'updated_at' => (string)($project['updated_at'] ?? ''),
    ];
}
function public_project_output_label(array $project): string
{
    $slug = strtolower((string)($project['category_slug'] ?? ''));
    $name = strtolower((string)($project['category_name'] ?? ''));
    if ($slug === 'modern-market' || strpos($name, 'market') !== false) {
        return 'Stalls';
    }
    if ($slug === 'esps' || strpos($name, 'esp') !== false) {
        return 'Site';
    }
    return 'Units';
}