<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin'],
    'csrf' => false,
]);

$id = Security::cleanInt($_GET['id'] ?? 0);
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Contact message id is required.'], 422);
}

$message = ContactSubmission::findDetailed($id);
if (!$message) {
    Response::json(['success' => false, 'message' => 'Contact message could not be found.'], 404);
}

if ((int)($message['is_read'] ?? 0) === 0) {
    ContactSubmission::markRead($id, (int)Auth::id());
    $message = ContactSubmission::findDetailed($id) ?: $message;
}

Response::json([
    'success' => true,
    'message' => contact_message_payload(ContactSubmission::payload($message)),
]);

function contact_message_payload(array $message): array
{
    return [
        'id' => (int)$message['id'],
        'name' => $message['name'],
        'email' => $message['email'],
        'phone' => $message['phone'],
        'subject' => $message['subject'],
        'body' => $message['message'],
        'status' => $message['status'],
        'isRead' => (int)$message['is_read'] === 1,
        'assignedTo' => $message['assigned_to'],
        'assigneeName' => $message['assignee_name'],
        'attachmentPath' => $message['attachment_path'],
        'attachmentUrl' => $message['attachment_url'],
        'responseNote' => $message['response_note'] ?? '',
        'repliedAt' => $message['replied_at'],
        'readAt' => $message['read_at'],
        'archivedAt' => $message['archived_at'],
        'ipAddress' => $message['ip_address'] ?? '',
        'sourceUrl' => $message['source_url'] ?? '',
        'createdAt' => $message['created_at'],
        'createdAtFormatted' => format_datetime($message['created_at']),
    ];
}
