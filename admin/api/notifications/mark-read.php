<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'auth' => true,
    'methods' => ['POST'],
    'csrf' => false,
]);

$csrfForm = trim((string)($_SERVER['HTTP_X_CSRF_FORM'] ?? 'default')) ?: 'default';
if (!Csrf::verify(Csrf::fromRequest(), $csrfForm)) {
    Response::json([
        'success' => false,
        'message' => 'Your session token expired. Please reload and try again.',
    ], 419);
}

$userId = (int)(Auth::id() ?? 0);
$input = Security::jsonInput();
$id = Security::cleanInt($input['id'] ?? 0);

if ($id <= 0) {
    Response::json([
        'success' => false,
        'message' => 'Notification id is required.',
    ], 422);
}

try {
    Notification::markRead($id, $userId);

    Response::json([
        'success' => true,
        'unread' => Notification::unreadCount($userId),
    ]);
} catch (Throwable $exception) {
    Logger::error('Notification mark-read failed', [
        'user_id' => $userId,
        'notification_id' => $id,
        'error' => $exception->getMessage(),
    ]);

    Response::json([
        'success' => false,
        'message' => 'Unable to update notification.',
    ], 500);
}
