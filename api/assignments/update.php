<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager'],
    'csrf_form' => 'assignments',
]);

$input = assignment_input();
$id = Security::cleanInt($input['id'] ?? 0);
$assignment = $id > 0 ? ProjectAssignment::findDetailed($id) : null;
if (!$assignment) {
    Response::json(['success' => false, 'message' => 'Assignment could not be found.'], 404);
}
if (!ProjectAssignment::canManageProject((int)Auth::id(), (int)$assignment['project_id'], (string)Auth::role())) {
    Response::json(['success' => false, 'message' => 'You cannot manage this project.'], 403);
}

Database::beginTransaction();
try {
    ProjectAssignment::updateAssignment($id, [
        'assignment_type' => Security::cleanString((string)($input['assignment_type'] ?? 'site')),
        'scope' => Security::cleanString((string)($input['scope'] ?? 'general')),
        'status' => Security::cleanString((string)($input['status'] ?? 'active')),
        'start_date' => $input['start_date'] ?? null,
        'end_date' => $input['end_date'] ?? null,
        'is_primary' => !empty($input['is_primary']),
        'notes' => Security::cleanString((string)($input['notes'] ?? '')),
        'updated_by' => (int)Auth::id(),
    ]);
    Logger::log('update', 'project_assignments', $id, ['project_id' => (int)$assignment['project_id'], 'user_id' => (int)$assignment['user_id']]);
    Database::commit();
} catch (Throwable $error) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => $error->getMessage() ?: 'Assignment could not be updated.'], 422);
}

$updated = ProjectAssignment::findDetailed($id) ?: [];
Notification::push((int)$updated['user_id'], 'assignment', 'Project assignment updated', 'Your assignment on ' . (string)$updated['project_name'] . ' was updated.', RoleAccess::dashboardFor((string)$updated['role_slug']), 'normal', 'project_assignments', $id);
Response::json(['success' => true, 'message' => 'Assignment updated.', 'assignment' => ProjectAssignment::payload($updated)]);

function assignment_input(): array
{
    $input = $_POST;
    if ($input === []) {
        $decoded = json_decode(file_get_contents('php://input') ?: '', true);
        $input = is_array($decoded) ? $decoded : [];
    }
    return $input;
}
