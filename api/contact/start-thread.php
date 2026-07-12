<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern'],
    'csrf_form' => 'contact_inbox',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$id = Security::cleanInt($input['id'] ?? 0);
$userId = (int)Auth::id();
$role = (string)(Auth::role() ?: '');

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Contact message id is required.'], 422);
}

$contact = ContactSubmission::findDetailed($id);
if (!$contact) {
    Response::json(['success' => false, 'message' => 'Contact message could not be found.'], 404);
}

if (!ContactSubmission::canAccess($contact, $userId, $role)) {
    Response::json(['success' => false, 'message' => 'This contact message is not assigned to you.'], 403);
}

$existingThreadId = (int)($contact['internal_thread_id'] ?? 0);
if ($existingThreadId > 0) {
    MessageParticipant::add($existingThreadId, $userId, $role, false);
    Response::json([
        'success' => true,
        'message' => 'Internal discussion opened.',
        'threadId' => $existingThreadId,
        'threadUrl' => Url::to('admin/' . ($role ?: 'superadmin') . '/messages.php?thread=' . $existingThreadId),
    ]);
}

$participantIds = [$userId];
if (!empty($contact['assigned_to'])) {
    $participantIds[] = (int)$contact['assigned_to'];
}

$superadmins = Database::fetchAll(
    "SELECT u.id
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     WHERE u.status = 'active' AND r.slug = 'superadmin'"
);
foreach ($superadmins as $superadmin) {
    $participantIds[] = (int)$superadmin['id'];
}
$participantIds = array_values(array_unique(array_filter($participantIds, static fn (int $id): bool => $id > 0)));

try {
    Database::beginTransaction();

    $subject = 'Public enquiry: ' . (trim((string)($contact['subject'] ?? '')) ?: 'Contact message #' . $id);
    $threadId = MessageThread::createThread($subject, 'group', $userId, null, 'normal');

    foreach ($participantIds as $participantId) {
        $participant = User::findDetailed($participantId) ?: [];
        MessageParticipant::add(
            $threadId,
            $participantId,
            (string)($participant['role_slug'] ?? ''),
            $participantId === $userId
        );
    }

    $body = "Internal discussion for public contact message #" . $id . "\n\n"
        . "Sender: " . trim((string)($contact['name'] ?? 'Public user')) . "\n"
        . "Email: " . trim((string)($contact['email'] ?? '')) . "\n"
        . "Phone: " . trim((string)($contact['phone'] ?? '')) . "\n"
        . "Subject: " . trim((string)($contact['subject'] ?? 'No subject')) . "\n\n"
        . "Message:\n" . trim((string)($contact['message'] ?? ''));

    $messageId = Message::createForThread($threadId, $userId, $body);
    MessageThread::touchLastMessage($threadId, $messageId);
    MessageRead::markThread($threadId, $userId);
    ContactSubmission::linkInternalThread($id, $threadId, $userId);

    Logger::log('start-internal-thread', 'contact_submissions', $id, [
        'thread_id' => $threadId,
        'participants' => $participantIds,
    ]);

    Database::commit();
} catch (Throwable $error) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Internal discussion could not be created.'], 500);
}

MessageNotificationService::notifyNewMessage($threadId, $messageId, $userId);

Response::json([
    'success' => true,
    'message' => 'Internal discussion created.',
    'threadId' => $threadId,
    'threadUrl' => Url::to('admin/' . ($role ?: 'superadmin') . '/messages.php?thread=' . $threadId),
]);
