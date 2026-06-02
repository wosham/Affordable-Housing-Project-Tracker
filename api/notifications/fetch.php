<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'auth' => true,
    'csrf' => false,
]);

$userId = (int)(Auth::id() ?? 0);
$limit = min(30, max(5, Security::cleanInt($_GET['limit'] ?? 10)));

try {
    $unread = Notification::unreadCount($userId);
    $items = array_map([Notification::class, 'payload'], Notification::forUser($userId, $limit));
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Notifications could not be loaded.'], 500);
}

Response::json([
    'success' => true,
    'unread' => $unread,
    'items' => $items,
]);
