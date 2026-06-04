<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager'],
    'csrf' => false,
]);

$filters = manager_project_filters($_GET);
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$perPage = min(80, max(10, Security::cleanInt($_GET['per_page'] ?? 20)));
$userId = (int)Auth::id();
$role = (string)Auth::role();
$total = ManagerProject::count($userId, $role, $filters);
$projects = array_map(
    [ManagerProject::class, 'payload'],
    ManagerProject::list($userId, $role, $filters, $perPage, ($page - 1) * $perPage)
);

Response::json([
    'success' => true,
    'projects' => $projects,
    'pagination' => [
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => max(1, (int)ceil($total / $perPage)),
    ],
]);

function manager_project_filters(array $input): array
{
    return array_filter([
        'q' => Security::cleanString((string)($input['q'] ?? '')),
        'status' => Security::cleanString((string)($input['status'] ?? '')),
        'constituency_id' => Security::cleanInt($input['constituency_id'] ?? 0),
        'progress' => Security::cleanString((string)($input['progress'] ?? '')),
        'risk' => Security::cleanString((string)($input['risk'] ?? '')),
        'coverage' => Security::cleanString((string)($input['coverage'] ?? '')),
    ], static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
}
