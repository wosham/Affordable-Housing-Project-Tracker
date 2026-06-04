<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager'],
    'csrf' => false,
]);

$filters = [
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'role' => Security::cleanString((string)($_GET['role'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'constituency_id' => Security::cleanInt($_GET['constituency_id'] ?? 0),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$perPage = min(80, max(10, Security::cleanInt($_GET['per_page'] ?? 20)));
$userId = (int)Auth::id();
$role = (string)Auth::role();

$total = ProjectAssignment::countForManager($userId, $role, $filters);
$rows = ProjectAssignment::listForManager($userId, $role, $filters, $perPage, ($page - 1) * $perPage);

Response::json([
    'success' => true,
    'assignments' => array_map([ProjectAssignment::class, 'payload'], $rows),
    'pagination' => [
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => max(1, (int)ceil($total / $perPage)),
    ],
]);
