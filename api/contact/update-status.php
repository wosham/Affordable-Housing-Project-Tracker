<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => 'contact_inbox',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$id = Security::cleanInt($input['id'] ?? 0);
$action = Security::cleanString((string)($input['action'] ?? ''));
$responseNote = Security::cleanString((string)($input['response_note'] ?? ''));

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Contact message id is required.'], 422);
}

$message = ContactSubmission::findDetailed($id);
if (!$message) {
    Response::json(['success' => false, 'message' => 'Contact message could not be found.'], 404);
}

$userId = (int)Auth::id();
$status = match ($action) {
    'read' => 'read',
    'unread' => 'new',
    'replied' => 'replied',
    'archive' => 'archived',
    'restore' => 'read',
    default => '',
};

if ($status === '') {
    Response::json(['success' => false, 'message' => 'Invalid contact action.'], 422);
}

Database::beginTransaction();
try {
    if ($action === 'unread') {
        ContactSubmission::markUnread($id, $userId);
    } elseif ($action === 'read') {
        ContactSubmission::markRead($id, $userId);
    } elseif ($action === 'replied') {
        ContactSubmission::recordResponse($id, $responseNote, $userId);
    } else {
        ContactSubmission::updateStatus($id, $status, $userId, $responseNote);
    }

    Logger::log('update-status', 'contact_submissions', $id, [
        'action' => $action,
        'old_status' => $message['status'] ?? '',
        'response_note' => $responseNote !== '',
    ]);

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Contact status could not be updated.'], 500);
}

$updated = ContactSubmission::payload(ContactSubmission::findDetailed($id) ?: []);
Response::json([
    'success' => true,
    'message' => 'Contact status updated.',
    'contact' => [
        'id' => (int)$updated['id'],
        'status' => $updated['status'],
        'isRead' => (int)$updated['is_read'] === 1,
        'responseNote' => $updated['response_note'] ?? '',
    ],
]);
