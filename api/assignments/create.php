<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager'],
    'csrf_form' => 'assignments',
]);

$input = assignment_input();
$projectId = Security::cleanInt($input['project_id'] ?? 0);
$userId = Security::cleanInt($input['user_id'] ?? 0);

if ($projectId <= 0 || $userId <= 0) {
    Response::json(['success' => false, 'message' => 'Project and user are required.'], 422);
}
if (!ProjectAssignment::canManageProject((int)Auth::id(), $projectId, (string)Auth::role())) {
    Response::json(['success' => false, 'message' => 'You cannot manage this project.'], 403);
}

Database::beginTransaction();
try {
    $assignmentId = ProjectAssignment::assign([
        'project_id' => $projectId,
        'user_id' => $userId,
        'assignment_type' => Security::cleanString((string)($input['assignment_type'] ?? 'site')),
        'scope' => Security::cleanString((string)($input['scope'] ?? 'general')),
        'status' => 'active',
        'start_date' => $input['start_date'] ?? null,
        'end_date' => $input['end_date'] ?? null,
        'is_primary' => !empty($input['is_primary']),
        'notes' => Security::cleanString((string)($input['notes'] ?? '')),
        'assigned_by' => (int)Auth::id(),
    ]);
    Logger::log('create', 'project_assignments', $assignmentId, ['project_id' => $projectId, 'user_id' => $userId]);
    Database::commit();
} catch (Throwable $error) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => $error->getMessage() ?: 'Assignment could not be created.'], 422);
}

$assignment = ProjectAssignment::findDetailed($assignmentId) ?: [];
Notification::push(
    $userId,
    'assignment',
    'New project assignment',
    'You have been assigned to ' . (string)($assignment['project_name'] ?? 'a project') . '.',
    RoleAccess::dashboardFor((string)($assignment['role_slug'] ?? '')),
    'normal',
    'project_assignments',
    $assignmentId
);

Response::json(['success' => true, 'message' => 'Assignment created.', 'assignment' => ProjectAssignment::payload($assignment)]);

function assignment_input(): array
{
    $input = $_POST;
    if ($input === []) {
        $decoded = json_decode(file_get_contents('php://input') ?: '', true);
        $input = is_array($decoded) ? $decoded : [];
    }
    return $input;
}
