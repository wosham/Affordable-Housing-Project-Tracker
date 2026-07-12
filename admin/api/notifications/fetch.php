<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'auth' => true,
    'methods' => ['GET'],
    'csrf' => false,
]);

$userId = (int)(Auth::id() ?? 0);
$limit = max(1, min(20, (int)($_GET['limit'] ?? 10)));

try {
    $items = array_map(
        [Notification::class, 'payload'],
        Notification::forUser($userId, $limit)
    );

    Response::json([
        'success' => true,
        'unread' => Notification::unreadCount($userId),
        'items' => $items,
    ]);
} catch (Throwable $exception) {
    Logger::error('Notification fetch failed', [
        'user_id' => $userId,
        'error' => $exception->getMessage(),
    ]);

    Response::json([
        'success' => false,
        'message' => 'Unable to load notifications.',
        'unread' => 0,
        'items' => [],
    ], 500);
}
