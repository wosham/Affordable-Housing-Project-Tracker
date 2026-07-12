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

try {
    $updated = Notification::markAllRead($userId);

    Response::json([
        'success' => true,
        'updated' => $updated,
        'unread' => 0,
    ]);
} catch (Throwable $exception) {
    Logger::error('Notification mark-all-read failed', [
        'user_id' => $userId,
        'error' => $exception->getMessage(),
    ]);

    Response::json([
        'success' => false,
        'message' => 'Unable to update notifications.',
    ], 500);
}
