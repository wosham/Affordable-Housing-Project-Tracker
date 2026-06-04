<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager', 'contractor', 'clerk'],
    'csrf' => false,
]);

if (!project_progress_csrf_ok()) {
    Response::json(['success' => false, 'message' => 'Invalid security token.'], 419);
}

$input = project_progress_input();
$projectId = Security::cleanInt($input['project_id'] ?? 0);
$progress = percentage($input['pct_complete'] ?? $input['progress'] ?? null);
$milestone = trim(strip_tags((string)($input['current_milestone'] ?? '')));
$note = trim(strip_tags((string)($input['note'] ?? '')));

if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'Project id is required.'], 422);
}

$project = Project::findDetailed($projectId);
if (!$project) {
    Response::json(['success' => false, 'message' => 'Project could not be found.'], 404);
}

if (!project_progress_can_update((int)Auth::id(), (string)Auth::role(), $projectId, $project)) {
    Response::json(['success' => false, 'message' => 'You do not have access to update this project.'], 403);
}

$updates = ['pct_complete' => $progress];
if ($milestone !== '' && project_progress_column_exists('projects', 'current_milestone')) {
    $updates['current_milestone'] = $milestone;
}

try {
    Project::update($projectId, $updates);
    project_progress_audit('update_progress', 'projects', $projectId, [
        'from' => (int)($project['pct_complete'] ?? 0),
        'to' => $progress,
        'milestone' => $milestone,
        'note' => $note,
    ]);

    if ($progress >= 100 && (string)($project['status'] ?? '') !== 'completed') {
        Notification::pushRole('superadmin', 'project_progress', 'Project reached completion', ($project['name'] ?? 'A project') . ' has been marked 100% complete.', 'admin/superadmin/projects.php');
    }
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Project progress could not be updated.'], 500);
}

Response::json([
    'success' => true,
    'message' => 'Project progress updated.',
    'project' => Project::findDetailed($projectId),
]);

function project_progress_input(): array
{
    $json = Security::jsonInput();
    return $json !== [] ? $json : $_POST;
}

function project_progress_csrf_ok(): bool
{
    $token = Csrf::fromRequest();
    foreach (['manager_projects', 'manager_dashboard', 'contractor_project', 'contractor_progress', 'clerk_site_records', 'superadmin_projects', 'default'] as $form) {
        if (Csrf::verify($token, $form)) {
            return true;
        }
    }

    return false;
}

function project_progress_can_update(int $userId, string $role, int $projectId, array $project): bool
{
    if ($role === 'superadmin') {
        return true;
    }

    if ($role === 'contractor' && (int)($project['contractor_id'] ?? 0) === $userId) {
        return true;
    }

    return ProjectAssignment::canManageProject($userId, $projectId, $role);
}

function project_progress_column_exists(string $table, string $column): bool
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

function project_progress_audit(string $action, string $module, int $targetId, array $details = []): void
{
    try {
        Database::query(
            'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::id(),
                $action,
                $module,
                $targetId,
                json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
    } catch (Throwable) {
    }
}
