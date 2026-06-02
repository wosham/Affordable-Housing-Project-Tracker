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

$action = Security::cleanString((string)($input['action'] ?? 'delete-message'));
$userId = (int)Auth::id();

if (in_array($action, ['archive-thread', 'restore-thread'], true)) {
    $threadId = Security::cleanInt($input['thread_id'] ?? 0);
    if ($threadId <= 0 || !MessageParticipant::canAccess($threadId, $userId)) {
        Response::json(['success' => false, 'message' => 'Thread was not found or access is denied.'], 404);
    }
    MessageParticipant::archiveForUser($threadId, $userId, $action === 'archive-thread');
    Logger::log($action, 'message_threads', $threadId);
    Response::json(['success' => true, 'message' => $action === 'archive-thread' ? 'Thread archived.' : 'Thread restored.']);
}

$messageId = Security::cleanInt($input['message_id'] ?? 0);
if ($messageId <= 0) {
    Response::json(['success' => false, 'message' => 'Message id is required.'], 422);
}

$message = Message::find($messageId);
if (!$message || !MessageParticipant::canAccess((int)$message['thread_id'], $userId)) {
    Response::json(['success' => false, 'message' => 'Message was not found or access is denied.'], 404);
}

$force = (string)Auth::role() === 'superadmin';
if (!Message::softDelete($messageId, $userId, $force)) {
    Response::json(['success' => false, 'message' => 'Only the sender or superadmin can delete this message.'], 403);
}

Logger::log('delete', 'messages', $messageId, ['thread_id' => (int)$message['thread_id']]);
Response::json(['success' => true, 'message' => 'Message deleted.']);
