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

$projectId = Security::cleanInt($input['project_id'] ?? 0);
$userIds = array_values(array_unique(array_filter(array_map('intval', (array)($input['user_ids'] ?? [])))));
if ($projectId <= 0 || $userIds === []) {
    Response::json(['success' => false, 'message' => 'Project and at least one user are required.'], 422);
}
if (!ProjectAssignment::canManageProject((int)Auth::id(), $projectId, (string)Auth::role())) {
    Response::json(['success' => false, 'message' => 'You cannot manage this project.'], 403);
}

$created = 0;
$skipped = 0;
Database::beginTransaction();
try {
    foreach ($userIds as $userId) {
        try {
            $assignmentId = ProjectAssignment::assign([
                'project_id' => $projectId,
                'user_id' => $userId,
                'assignment_type' => Security::cleanString((string)($input['assignment_type'] ?? 'site')),
                'scope' => Security::cleanString((string)($input['scope'] ?? 'general')),
                'start_date' => $input['start_date'] ?? null,
                'end_date' => $input['end_date'] ?? null,
                'notes' => Security::cleanString((string)($input['notes'] ?? '')),
                'assigned_by' => (int)Auth::id(),
            ]);
            Logger::log('create', 'project_assignments', $assignmentId, ['bulk' => true, 'project_id' => $projectId, 'user_id' => $userId]);
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
            $created++;
        } catch (Throwable) {
            $skipped++;
        }
    }
    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Bulk assignment could not be completed.'], 500);
}

Response::json(['success' => true, 'message' => "{$created} assignment(s) created, {$skipped} skipped.", 'created' => $created, 'skipped' => $skipped]);
