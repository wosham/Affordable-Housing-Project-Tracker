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

$userId = (int)Auth::id();
$role = (string)(Auth::role() ?: '');
$threadId = Security::cleanInt($input['thread_id'] ?? 0);
$body = trim((string)($input['body'] ?? ''));
$subject = Security::cleanString((string)($input['subject'] ?? ''));
$type = Security::cleanString((string)($input['type'] ?? 'direct'));
$priority = Security::cleanString((string)($input['priority'] ?? 'normal'));
$projectId = Security::cleanInt($input['project_id'] ?? 0);
$recipients = array_values(array_unique(array_filter(array_map('intval', (array)($input['recipients'] ?? [])))));
$audienceTargets = array_values(array_filter(array_map(
    static fn ($target): string => Security::cleanString((string)$target),
    (array)($input['audience_targets'] ?? [])
)));
$attachmentTokens = array_values(array_filter(array_map('strval', (array)($input['attachment_tokens'] ?? []))));

if ($body === '') {
    Response::json(['success' => false, 'message' => 'Message body is required.'], 422);
}
if ($projectId > 0 && $role !== 'superadmin' && !ProjectAssignment::canManageProject($userId, $projectId, $role)) {
    Response::json(['success' => false, 'message' => 'You cannot link messages to this project.'], 403);
}

try {
    Database::beginTransaction();

    if ($threadId > 0) {
        if (!MessageParticipant::canAccess($threadId, $userId)) {
            throw new RuntimeException('You cannot reply to this thread.');
        }
    } else {
        $recipients = MessageThread::expandAudienceTargets(
            $userId,
            $role,
            $recipients,
            $audienceTargets,
            $projectId > 0 ? $projectId : null
        );
        if ($recipients === []) {
            throw new RuntimeException('Choose at least one allowed active recipient, role group or project team.');
        }

        $threadId = MessageThread::createThread($subject, $type, $userId, $projectId > 0 ? $projectId : null, $priority);
        MessageParticipant::add($threadId, $userId, $role, true);
        foreach ($recipients as $recipientId) {
            $recipient = User::findDetailed($recipientId) ?: [];
            MessageParticipant::add($threadId, $recipientId, (string)($recipient['role_slug'] ?? ''), false);
        }
    }

    $messageId = Message::createForThread($threadId, $userId, $body);
    MessageAttachment::attachTokens($messageId, $userId, $attachmentTokens);
    MessageThread::touchLastMessage($threadId, $messageId);
    MessageRead::markThread($threadId, $userId);

    Database::commit();
} catch (Throwable $error) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => $error->getMessage() ?: 'Message could not be sent.'], 422);
}

MessageNotificationService::notifyNewMessage($threadId, $messageId, $userId);

Response::json([
    'success' => true,
    'message' => 'Message sent.',
    'threadId' => $threadId,
    'messageId' => $messageId,
]);
