<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'auth' => true,
    'csrf' => false,
]);

$userId = (int)Auth::id();
$threadId = Security::cleanInt($_GET['thread_id'] ?? $_GET['id'] ?? 0);
if ($threadId <= 0) {
    Response::json(['success' => false, 'message' => 'Thread id is required.'], 422);
}

$thread = MessageThread::findForUser($threadId, $userId);
if (!$thread) {
    Response::json(['success' => false, 'message' => 'Thread was not found or access is denied.'], 404);
}

MessageRead::markThread($threadId, $userId);
$messages = Message::forThread($threadId, 200);
$attachments = MessageAttachment::forMessages(array_column($messages, 'id'));

Response::json([
    'success' => true,
    'thread' => MessageThread::payload($thread),
    'participants' => array_map(static fn (array $row): array => [
        'id' => (int)$row['user_id'],
        'name' => trim((string)$row['name']) ?: 'Staff User',
        'email' => (string)$row['email'],
        'role' => (string)$row['role_slug'],
        'roleLabel' => (string)$row['role_name'],
        'isAdmin' => (int)$row['is_admin'] === 1,
    ], MessageParticipant::forThread($threadId)),
    'messages' => array_map(static fn (array $row): array => Message::payload($row, $attachments[(int)$row['id']] ?? []), $messages),
]);
