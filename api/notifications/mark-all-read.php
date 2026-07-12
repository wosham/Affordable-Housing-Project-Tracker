<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'auth' => true,
    'csrf_form' => (string)($_SERVER['HTTP_X_CSRF_FORM'] ?? 'default'),
]);

$userId = (int)(Auth::id() ?? 0);
$marked = Notification::markAllRead($userId);

Response::json([
    'success' => true,
    'unread' => 0,
    'marked' => $marked,
]);
