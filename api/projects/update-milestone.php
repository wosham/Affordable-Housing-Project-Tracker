<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager', 'clerk'],
    'csrf' => false,
]);

if (!milestone_update_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = milestone_update_input();
$actorId = (int)Auth::id();
$role = (string)Auth::role();
$milestoneId = Security::cleanInt($input['milestone_id'] ?? $input['id'] ?? 0);
$projectId = Security::cleanInt($input['project_id'] ?? 0);
$status = trim((string)($input['status'] ?? ''));
$label = trim(strip_tags((string)($input['label'] ?? '')));
$descriptionInputProvided = array_key_exists('description', $input);
$description = trim(strip_tags((string)($input['description'] ?? '')));
$targetInputProvided = array_key_exists('target_date', $input);
$targetDate = milestone_update_date($input['target_date'] ?? null);
$actualInputProvided = array_key_exists('actual_date', $input);
$actualDate = milestone_update_date($input['actual_date'] ?? null);
$priorityInputProvided = array_key_exists('priority', $input);
$priority = trim((string)($input['priority'] ?? 'normal'));
$progressInputProvided = array_key_exists('progress_percent', $input) || array_key_exists('progress', $input);
$progress = percentage($input['progress_percent'] ?? $input['progress'] ?? ($status === 'done' ? 100 : 0));
$sequence = Security::cleanInt($input['sequence'] ?? 0);
$notesInputProvided = array_key_exists('notes', $input) || array_key_exists('note', $input);
$notes = trim(strip_tags((string)($input['notes'] ?? $input['note'] ?? '')));

if (!in_array($status, ManagerMilestone::STATUSES, true)) {
    Response::json(['success' => false, 'message' => 'Choose a valid milestone status.'], 422);
}

if (!in_array($priority, ManagerMilestone::PRIORITIES, true)) {
    Response::json(['success' => false, 'message' => 'Choose a valid milestone priority.'], 422);
}

if ($milestoneId <= 0 && ($projectId <= 0 || $label === '')) {
    Response::json(['success' => false, 'message' => 'Project and milestone label are required.'], 422);
}

$existing = $milestoneId > 0 ? Milestone::find($milestoneId) : null;
if ($milestoneId > 0 && !$existing) {
    Response::json(['success' => false, 'message' => 'Milestone could not be found.'], 404);
}

if ($existing && !$progressInputProvided && $status !== 'done') {
    $progress = percentage($existing['progress_percent'] ?? ($existing['status'] === 'done' ? 100 : 0));
}

if ($existing && !$priorityInputProvided) {
    $priority = (string)($existing['priority'] ?? 'normal');
}

$projectId = $existing ? (int)$existing['project_id'] : $projectId;
$project = Project::findDetailed($projectId);
if (!$project) {
    Response::json(['success' => false, 'message' => 'Project could not be found.'], 404);
}

if (!milestone_update_can_access($actorId, $role, $projectId)) {
    Response::json(['success' => false, 'message' => 'You do not have access to update milestones for this project.'], 403);
}

if ($actualDate !== null && strtotime($actualDate) < strtotime((string)$project['start_date'] ?: '1970-01-01')) {
    Response::json(['success' => false, 'message' => 'Completion date cannot be before the project start date.'], 422);
}

if ($status === 'done') {
    $progress = 100;
    $actualDate ??= date('Y-m-d');
} elseif ($progress >= 100) {
    $status = 'done';
    $actualDate ??= date('Y-m-d');
} elseif ($status !== 'done' && !$actualInputProvided) {
    $actualDate = null;
}

try {
    Database::beginTransaction();

    if ($status === 'current') {
        Database::query("UPDATE milestones SET status = 'pending', updated_by = ? WHERE project_id = ? AND status = 'current' AND id <> ?", [$actorId, $projectId, $milestoneId]);
    }

    if ($existing) {
        $data = [
            'status' => $status,
            'actual_date' => $actualDate,
            'progress_percent' => $progress,
            'priority' => $priority,
            'updated_by' => $actorId,
            'completed_by' => $status === 'done' ? $actorId : null,
        ];

        if ($label !== '') {
            $data['label'] = $label;
        }
        if ($descriptionInputProvided) {
            $data['description'] = $description !== '' ? $description : null;
        }
        if ($targetInputProvided) {
            $data['target_date'] = $targetDate;
        }
        if ($sequence > 0) {
            $data['sequence'] = $sequence;
        }
        if ($notesInputProvided) {
            $data['notes'] = $notes !== '' ? $notes : null;
        }

        Milestone::update($milestoneId, $data);
    } else {
        $milestoneId = (int)Milestone::create([
            'project_id' => $projectId,
            'label' => $label,
            'description' => $description !== '' ? $description : null,
            'target_date' => $targetDate,
            'actual_date' => $actualDate,
            'status' => $status,
            'progress_percent' => $progress,
            'priority' => $priority,
            'sequence' => $sequence,
            'updated_by' => $actorId,
            'completed_by' => $status === 'done' ? $actorId : null,
            'notes' => $notes !== '' ? $notes : null,
        ]);
    }

    $saved = Milestone::find($milestoneId) ?: [];
    if (project_milestone_column_exists('projects', 'current_milestone') && in_array($status, ['current', 'done'], true)) {
        Project::update($projectId, ['current_milestone' => (string)($saved['label'] ?? $label)]);
    }

    ManagerMilestone::recordUpdate($milestoneId, $projectId, $actorId, $existing ?: [], $saved, $notes);
    Logger::log($existing ? 'update_milestone' : 'create_milestone', 'milestones', $milestoneId, [
        'project' => $project['name'] ?? '',
        'status' => $status,
        'label' => $saved['label'] ?? $label,
        'priority' => $priority,
        'progress' => $progress,
    ]);

    milestone_update_notifications($project, $saved, $existing ?: [], $role);

    Database::commit();
} catch (Throwable) {
    Database::rollBack();
    Response::json(['success' => false, 'message' => 'Milestone could not be updated.'], 500);
}

$detail = ManagerMilestone::findScoped($milestoneId, $actorId, $role);
Response::json([
    'success' => true,
    'message' => 'Milestone updated.',
    'milestone' => $detail ? ManagerMilestone::payload($detail) : Milestone::find($milestoneId),
]);

function milestone_update_input(): array
{
    $json = Security::jsonInput();
    return $json !== [] ? $json : $_POST;
}

function milestone_update_csrf_ok(): bool
{
    $token = Csrf::fromRequest();
    foreach (['manager_milestones', 'manager_projects', 'manager_dashboard', 'clerk_site_records', 'superadmin_projects', 'default'] as $form) {
        if (Csrf::verify($token, $form)) {
            return true;
        }
    }

    return false;
}

function milestone_update_date(mixed $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

function milestone_update_can_access(int $userId, string $role, int $projectId): bool
{
    if ($role === 'superadmin') {
        return true;
    }

    return ProjectAssignment::canManageProject($userId, $projectId, $role);
}

function project_milestone_column_exists(string $table, string $column): bool
{
    try {
        return Database::fetch(
            'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        ) !== null;
    } catch (Throwable) {
        return false;
    }
}

function milestone_update_notifications(array $project, array $saved, array $existing, string $role): void
{
    $label = (string)($saved['label'] ?? 'Milestone');
    $projectName = (string)($project['name'] ?? 'a project');
    $status = (string)($saved['status'] ?? '');
    $priority = (string)($saved['priority'] ?? 'normal');
    $targetChanged = $existing && (string)($existing['target_date'] ?? '') !== (string)($saved['target_date'] ?? '');

    if ($role !== 'superadmin' && ($status === 'done' || $priority === 'critical' || $targetChanged)) {
        Notification::pushRole(
            'superadmin',
            'milestone',
            'Milestone updated',
            $label . ' for ' . $projectName . ' was updated to ' . status_label($status) . '.',
            'admin/superadmin/programme-of-works.php?project_id=' . (int)($project['id'] ?? 0)
        );
    }

    if ($status === 'done') {
        Notification::pushRole(
            'contractor',
            'milestone',
            'Milestone completed',
            $label . ' for ' . $projectName . ' has been completed.',
            'admin/contractor/programme-of-works.php'
        );
    }
}
