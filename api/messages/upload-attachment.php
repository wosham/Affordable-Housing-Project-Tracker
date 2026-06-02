<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'auth' => true,
    'csrf_form' => 'messages',
]);

if (empty($_FILES['attachment']) || !is_array($_FILES['attachment'])) {
    Response::json(['success' => false, 'message' => 'Attachment is required.'], 422);
}

try {
    $attachment = MessageAttachment::createPending($_FILES['attachment'], (int)Auth::id());
} catch (Throwable $error) {
    Response::json(['success' => false, 'message' => $error->getMessage() ?: 'Attachment could not be uploaded.'], 422);
}

Logger::log('upload', 'message_attachments', (int)$attachment['id'], ['name' => $attachment['name']]);
Response::json(['success' => true, 'attachment' => $attachment]);
