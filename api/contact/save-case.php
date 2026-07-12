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

$fields = [];
if (array_key_exists('internal_note', $input)) {
    $fields['internal_note'] = Security::cleanString((string)$input['internal_note']);
}
if (array_key_exists('follow_up_at', $input)) {
    $fields['follow_up_at'] = Security::cleanString((string)$input['follow_up_at']);
}
if (array_key_exists('priority', $input)) {
    $fields['priority'] = Security::cleanString((string)$input['priority']);
}
if (array_key_exists('status', $input) && trim((string)$input['status']) !== '') {
    $fields['status'] = Security::cleanString((string)$input['status']);
}

if ($fields === []) {
    Response::json(['success' => false, 'message' => 'No case fields provided.'], 422);
}

try {
    ContactSubmission::saveCaseFields($id, $userId, $fields);
    Logger::log('save-case', 'contact_submissions', $id, [
        'fields' => array_keys($fields),
    ]);
} catch (Throwable $e) {
    Response::json(['success' => false, 'message' => $e->getMessage() ?: 'Case could not be saved.'], 500);
}

$updated = ContactSubmission::payload(ContactSubmission::findDetailed($id) ?: []);
Response::json([
    'success' => true,
    'message' => 'Case updated.',
    'contact' => [
        'id' => (int)$updated['id'],
        'status' => $updated['status'],
        'priority' => $updated['priority'] ?? 'normal',
        'internalNote' => $updated['internal_note'] ?? '',
        'followUpAt' => $updated['follow_up_at'] ?? '',
        'isOverdue' => (int)($updated['is_overdue'] ?? 0) === 1,
        'isRead' => (int)$updated['is_read'] === 1,
    ],
]);
