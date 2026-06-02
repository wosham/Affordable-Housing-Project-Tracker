<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'auth' => true,
    'csrf_form' => 'messages',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$threadId = Security::cleanInt($input['thread_id'] ?? 0);
$userId = (int)Auth::id();
if ($threadId <= 0 || !MessageParticipant::canAccess($threadId, $userId)) {
    Response::json(['success' => false, 'message' => 'Thread was not found or access is denied.'], 404);
}

$count = MessageRead::markThread($threadId, $userId);
Response::json(['success' => true, 'marked' => $count]);
