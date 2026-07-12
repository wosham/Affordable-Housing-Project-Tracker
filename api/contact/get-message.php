<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern'],
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

$userId = (int)Auth::id();
$role = (string)(Auth::role() ?: '');
if (!ContactSubmission::canAccess($message, $userId, $role)) {
    Response::json(['success' => false, 'message' => 'This contact message is not assigned to you.'], 403);
}

if ((int)($message['is_read'] ?? 0) === 0) {
    ContactSubmission::markRead($id, $userId);
    $message = ContactSubmission::findDetailed($id) ?: $message;
}

Response::json([
    'success' => true,
    'message' => contact_message_payload(ContactSubmission::payload($message)),
]);

function contact_message_payload(array $message): array
{
    $role = (string)(Auth::role() ?: 'superadmin');
    $threadId = (int)($message['internal_thread_id'] ?? 0);
    return [
        'id' => (int)$message['id'],
        'name' => $message['name'],
        'email' => $message['email'],
        'phone' => $message['phone'],
        'subject' => $message['subject'],
        'body' => $message['message'],
        'status' => $message['status'],
        'priority' => ((string)($message['priority'] ?? 'normal')) === 'urgent' ? 'urgent' : 'normal',
        'isRead' => (int)$message['is_read'] === 1,
        'assignedTo' => $message['assigned_to'],
        'assigneeName' => $message['assignee_name'],
        'internalThreadId' => $threadId ?: null,
        'internalThreadUrl' => $threadId > 0 ? Url::to('admin/' . $role . '/messages.php?thread=' . $threadId) : '',
        'attachmentPath' => $message['attachment_path'],
        'attachmentUrl' => $message['attachment_url'],
        'responseNote' => $message['response_note'] ?? '',
        'internalNote' => $message['internal_note'] ?? '',
        'followUpAt' => $message['follow_up_at'] ?? '',
        'isOverdue' => !empty($message['is_overdue']),
        'isDueToday' => !empty($message['is_due_today']),
        'repliedAt' => $message['replied_at'],
        'repliedAtFormatted' => !empty($message['replied_at']) ? format_datetime($message['replied_at']) : '',
        'readAt' => $message['read_at'],
        'archivedAt' => $message['archived_at'],
        'assignedAt' => $message['assigned_at'] ?? '',
        'assignedAtFormatted' => !empty($message['assigned_at']) ? format_datetime($message['assigned_at']) : '',
        'ipAddress' => $message['ip_address'] ?? '',
        'sourceUrl' => $message['source_url'] ?? '',
        'createdAt' => $message['created_at'],
        'createdAtFormatted' => format_datetime($message['created_at']),
        'updatedByName' => $message['updated_by_name'] ?? '',
        'replies' => array_map([ContactReply::class, 'payload'], ContactReply::forSubmission((int)$message['id'])),
    ];
}
