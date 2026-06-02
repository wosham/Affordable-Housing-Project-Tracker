<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => 'subscribers',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$id = Security::cleanInt($input['id'] ?? 0);
$action = Security::cleanString((string)($input['action'] ?? ''));

if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Subscriber id is required.'], 422);
}

$subscriber = Subscriber::findDetailed($id);
if (!$subscriber) {
    Response::json(['success' => false, 'message' => 'Subscriber could not be found.'], 404);
}

if (!in_array($action, ['unsubscribe', 'reactivate'], true)) {
    Response::json(['success' => false, 'message' => 'Invalid subscriber action.'], 422);
}

Database::beginTransaction();
try {
    if ($action === 'unsubscribe') {
        Subscriber::unsubscribe($id, (int)Auth::id());
    } else {
        Subscriber::reactivate($id, (int)Auth::id());
    }

    Logger::log('update-status', 'subscribers', $id, [
        'action' => $action,
        'old_status' => $subscriber['status'] ?? '',
    ]);

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Subscriber status could not be updated.'], 500);
}

$updated = Subscriber::payload(Subscriber::findDetailed($id) ?: []);
Response::json([
    'success' => true,
    'message' => 'Subscriber status updated.',
    'subscriber' => [
        'id' => (int)$updated['id'],
        'status' => $updated['status'],
    ],
]);
