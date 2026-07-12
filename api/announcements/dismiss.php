<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'auth' => true,
    'csrf_form' => (string)($_SERVER['HTTP_X_CSRF_FORM'] ?? ($_POST['csrf_form'] ?? 'default')),
]);

$input = Security::jsonInput();
if ($input === []) {
    $input = $_POST;
}

$announcementId = Security::cleanInt($input['announcement_id'] ?? $input['id'] ?? 0);
$userId = (int)Auth::id();

if ($announcementId <= 0) {
    Response::json(['success' => false, 'message' => 'Announcement id is required.'], 422);
}

if (!Announcement::dismissForUser($announcementId, $userId)) {
    Response::json(['success' => false, 'message' => 'Announcement could not be dismissed.'], 404);
}

Logger::log('dismiss', 'announcements', $announcementId, ['user_id' => $userId]);

Response::json([
    'success' => true,
    'message' => 'Announcement dismissed.',
    'announcement_id' => $announcementId,
]);
