<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager'],
    'csrf_form' => 'assignments',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$id = Security::cleanInt($input['id'] ?? 0);
$action = Security::cleanString((string)($input['action'] ?? 'revoke'));
$assignment = $id > 0 ? ProjectAssignment::findDetailed($id) : null;
if (!$assignment) {
    Response::json(['success' => false, 'message' => 'Assignment could not be found.'], 404);
}
if (!ProjectAssignment::canManageProject((int)Auth::id(), (int)$assignment['project_id'], (string)Auth::role())) {
    Response::json(['success' => false, 'message' => 'You cannot manage this project.'], 403);
}

Database::beginTransaction();
try {
    if ($action === 'reactivate') {
        ProjectAssignment::reactivate($id, (int)Auth::id());
        $message = 'Assignment reactivated.';
    } else {
        ProjectAssignment::revoke($id, (int)Auth::id());
        $message = 'Assignment revoked.';
    }
    Logger::log($action === 'reactivate' ? 'reactivate' : 'revoke', 'project_assignments', $id, ['project_id' => (int)$assignment['project_id'], 'user_id' => (int)$assignment['user_id']]);
    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Assignment status could not be changed.'], 500);
}

$updated = ProjectAssignment::findDetailed($id) ?: [];
Notification::push((int)$updated['user_id'], 'assignment', $message, 'Your assignment on ' . (string)$updated['project_name'] . ' changed.', RoleAccess::dashboardFor((string)$updated['role_slug']), 'normal', 'project_assignments', $id);
Response::json(['success' => true, 'message' => $message, 'assignment' => ProjectAssignment::payload($updated)]);
