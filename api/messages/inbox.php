<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'auth' => true,
    'csrf' => false,
]);

$userId = (int)Auth::id();
$filters = [
    'box' => Security::cleanString((string)($_GET['box'] ?? 'inbox')),
    'type' => Security::cleanString((string)($_GET['type'] ?? '')),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
];
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$perPage = min(50, max(10, Security::cleanInt($_GET['per_page'] ?? 20)));
$total = MessageThread::countForUser($userId, $filters);
$threads = array_map(
    [MessageThread::class, 'payload'],
    MessageThread::forUser($userId, $filters, $perPage, ($page - 1) * $perPage)
);

Response::json([
    'success' => true,
    'threads' => $threads,
    'pagination' => [
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => max(1, (int)ceil($total / $perPage)),
    ],
]);
