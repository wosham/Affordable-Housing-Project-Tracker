<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager', 'contractor'],
    'csrf' => false,
]);

ApiCsrf::requireAny(ApiCsrf::forms('programme_task'));

$input = $_POST;
if ($input === []) {
    $input = Security::jsonInput();
}

$id = Security::cleanInt($input['id'] ?? $input['task_id'] ?? 0);
if ($id <= 0) {
    Response::json(['success' => false, 'message' => 'Programme task id is required.'], 422);
}

$task = ProgrammeTask::findDetailed($id);
if (!$task) {
    Response::json(['success' => false, 'message' => 'Programme task could not be found.'], 404);
}

$role = (string)Auth::role();
$userId = (int)Auth::id();
if ($role !== 'superadmin' && !ManagerProgramme::canAccessTask($userId, $role, $task)) {
    Response::json(['success' => false, 'message' => 'You do not have access to update this programme task.'], 403);
}

$taskName = Security::cleanString((string)($input['task_name'] ?? ''));
if ($taskName === '') {
    Response::json(['success' => false, 'message' => 'Task name is required.'], 422);
}

$plannedStart = ProgrammeTask::normaliseDate($input['planned_start'] ?? null);
$plannedEnd = ProgrammeTask::normaliseDate($input['planned_end'] ?? null);
$actualStart = ProgrammeTask::normaliseDate($input['start_date'] ?? null);
$actualEnd = ProgrammeTask::normaliseDate($input['end_date'] ?? null);
$pct = percentage($input['pct_complete'] ?? 0);
$status = Security::cleanString((string)($input['status'] ?? 'pending'));
$assignedTo = Security::cleanInt($input['assigned_to'] ?? 0);
$dependsOn = Security::cleanInt($input['depends_on_task_id'] ?? 0);
$notes = Security::cleanString((string)($input['notes'] ?? ''));

if (!in_array($status, ProgrammeTask::statusOptions(), true)) {
    Response::json(['success' => false, 'message' => 'Invalid programme task status.'], 422);
}
if ($plannedStart && $plannedEnd && strtotime($plannedEnd) < strtotime($plannedStart)) {
    Response::json(['success' => false, 'message' => 'Planned end cannot be before planned start.'], 422);
}
if ($actualStart && $actualEnd && strtotime($actualEnd) < strtotime($actualStart)) {
    Response::json(['success' => false, 'message' => 'Actual end cannot be before actual start.'], 422);
}
if ($dependsOn === $id) {
    Response::json(['success' => false, 'message' => 'A task cannot depend on itself.'], 422);
}
if ($dependsOn > 0) {
    $dependency = ProgrammeTask::findDetailed($dependsOn);
    if (!$dependency || (int)$dependency['project_id'] !== (int)$task['project_id']) {
        Response::json(['success' => false, 'message' => 'Dependency must be another task in the same project.'], 422);
    }
}
if ($assignedTo > 0 && $role !== 'superadmin') {
    $assignment = Database::fetch(
        "SELECT pa.id
         FROM project_assignments pa
         JOIN users u ON u.id = pa.user_id
         WHERE pa.project_id = ? AND pa.user_id = ? AND pa.status = 'active' AND u.status = 'active'
         LIMIT 1",
        [(int)$task['project_id'], $assignedTo]
    );
    if (!$assignment) {
        Response::json(['success' => false, 'message' => 'Assigned user must belong to this project team.'], 422);
    }
}

Database::beginTransaction();
try {
    ProgrammeTask::updateTask($id, [
        'task_name' => $taskName,
        'planned_start' => $plannedStart,
        'planned_end' => $plannedEnd,
        'start_date' => $actualStart,
        'end_date' => $actualEnd,
        'pct_complete' => $pct,
        'status' => $status,
        'assigned_to' => $assignedTo,
        'depends_on_task_id' => $dependsOn,
        'critical_path' => !empty($input['critical_path']),
        'notes' => $notes,
    ], $userId);

    $updatedRaw = ProgrammeTask::findDetailed($id) ?: [];
    $signals = ManagerProgramme::updateSignals($task, $updatedRaw);

    Logger::log('update', 'programme_tasks', $id, [
        'old' => [
            'task_name' => $task['task_name'] ?? '',
            'status' => $task['status'] ?? '',
            'pct_complete' => (int)($task['pct_complete'] ?? 0),
            'planned_start' => $task['planned_start'] ?? null,
            'planned_end' => $task['planned_end'] ?? null,
        ],
        'new' => [
            'task_name' => $taskName,
            'status' => $updatedRaw['status'] ?? $status,
            'pct_complete' => (int)($updatedRaw['pct_complete'] ?? $pct),
            'planned_start' => $plannedStart,
            'planned_end' => $plannedEnd,
        ],
        'signals' => $signals,
    ]);

    if ($signals !== [] && $role === 'manager') {
        Notification::pushRole(
            'superadmin',
            'programme_task_update',
            'Programme task needs attention',
            ($task['project_name'] ?? 'A project') . ': ' . $taskName . ' - ' . implode(', ', $signals) . '.',
            'admin/superadmin/programme-of-works.php?project_id=' . (int)$task['project_id']
        );
    }

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Programme task could not be updated.'], 500);
}

$updated = ProgrammeTask::payload(ProgrammeTask::findDetailed($id) ?: []);
Response::json([
    'success' => true,
    'message' => 'Programme task updated successfully.',
    'task' => programme_update_payload($updated),
]);

function programme_update_payload(array $task): array
{
    return [
        'id' => (int)$task['id'],
        'taskName' => $task['task_name'],
        'status' => $task['status'],
        'progress' => (int)$task['pct_complete'],
        'plannedStart' => $task['planned_start'],
        'plannedEnd' => $task['planned_end'],
        'actualStart' => $task['start_date'],
        'actualEnd' => $task['end_date'],
        'delayState' => $task['delay_state'],
    ];
}
