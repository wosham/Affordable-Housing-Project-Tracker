<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'auth' => true,
    'csrf_form' => 'default',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$id = Security::cleanInt($input['id'] ?? 0);
$userId = (int)(Auth::id() ?? 0);

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Notification id is required.'], 422);
}

Notification::markRead($id, $userId);
Response::json([
    'success' => true,
    'unread' => Notification::unreadCount($userId),
]);
