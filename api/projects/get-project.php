<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

$id = Security::cleanString((string)($_GET['id'] ?? ''));
$slug = Security::cleanString((string)($_GET['slug'] ?? $_GET['project'] ?? ''));
$lookup = $id !== '' ? $id : $slug;

if ($lookup === '') {
    Response::json([
        'success' => false,
        'message' => 'Provide a project id or slug.',
    ], 422);
}

$project = Project::findDetailed($lookup);
if (!$project) {
    Response::json([
        'success' => false,
        'message' => 'Project not found.',
    ], 404);
}

$milestones = Milestone::forProject((int)$project['id']);
$related = Project::withRelations([
    'constituency_id' => (int)$project['constituency_id'],
], 4);
$related = array_values(array_filter(
    $related,
    static fn (array $item): bool => (int)$item['id'] !== (int)$project['id']
));

Response::json([
    'success' => true,
    'project' => project_detail_payload($project, $milestones),
    'related' => array_map('project_summary_payload', $related),
]);

function project_detail_payload(array $project, array $milestones): array
{
    $summary = project_summary_payload($project);
    $summary['milestones'] = array_map(static fn (array $milestone): array => [
        'id' => (int)$milestone['id'],
        'label' => $milestone['label'],
        'targetDate' => $milestone['target_date'],
        'actualDate' => $milestone['actual_date'],
        'status' => $milestone['status'],
        'statusLabel' => status_label($milestone['status']),
        'sequence' => (int)$milestone['sequence'],
    ], $milestones);

    return $summary;
}

function project_summary_payload(array $project): array
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
