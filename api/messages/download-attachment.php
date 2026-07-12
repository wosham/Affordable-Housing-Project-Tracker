<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'auth' => true,
    'csrf' => false,
]);

$attachmentId = Security::cleanInt($_GET['id'] ?? 0);
if ($attachmentId <= 0) {
    Response::json(['success' => false, 'message' => 'Attachment id is required.'], 422);
}

$attachment = MessageAttachment::find($attachmentId);
if (!$attachment || empty($attachment['message_id'])) {
    Response::json(['success' => false, 'message' => 'Attachment was not found.'], 404);
}

$message = Message::find((int)$attachment['message_id']);
if (!$message || !MessageParticipant::canAccess((int)$message['thread_id'], (int)Auth::id())) {
    Response::json(['success' => false, 'message' => 'Attachment access is denied.'], 403);
}

$base = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'secure-uploads' . DIRECTORY_SEPARATOR . 'message-attachments');
$path = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$attachment['path']));
if (!$base || !$path || strncmp($path, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) !== 0 || !is_file($path)) {
    Response::json(['success' => false, 'message' => 'Attachment file is unavailable.'], 404);
}

Database::query('UPDATE message_attachments SET download_count = download_count + 1 WHERE id = ?', [$attachmentId]);

$name = preg_replace('/[^A-Za-z0-9._ -]+/', '_', (string)($attachment['original_name'] ?? $attachment['filename'] ?? 'attachment')) ?: 'attachment';
$mime = (string)($attachment['mime_type'] ?? $attachment['type'] ?? 'application/octet-stream');
if (!headers_sent()) {
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
    header('X-Content-Type-Options: nosniff');
}
readfile($path);
exit;
