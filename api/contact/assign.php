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
$assignedTo = Security::cleanInt($input['assigned_to'] ?? 0);

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Contact message id is required.'], 422);
}

$message = ContactSubmission::findDetailed($id);
if (!$message) {
    Response::json(['success' => false, 'message' => 'Contact message could not be found.'], 404);
}

if ($assignedTo > 0) {
    $user = Database::fetch('SELECT id FROM users WHERE id = ? AND status = ? LIMIT 1', [$assignedTo, 'active']);
    if (!$user) {
        Response::json(['success' => false, 'message' => 'Assigned user must be an active staff account.'], 422);
    }
} else {
    $assignedTo = 0;
}

Database::beginTransaction();
try {
    ContactSubmission::assign($id, $assignedTo > 0 ? $assignedTo : null, (int)Auth::id());
    Logger::log('assign', 'contact_submissions', $id, [
        'old_assigned_to' => $message['assigned_to'] ?? null,
        'assigned_to' => $assignedTo > 0 ? $assignedTo : null,
    ]);
    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Contact assignment could not be saved.'], 500);
}

$updated = ContactSubmission::payload(ContactSubmission::findDetailed($id) ?: []);
Response::json([
    'success' => true,
    'message' => 'Contact assignment saved.',
    'contact' => [
        'id' => (int)$updated['id'],
        'assignedTo' => $updated['assigned_to'],
        'assigneeName' => $updated['assignee_name'] ?? '',
    ],
]);
