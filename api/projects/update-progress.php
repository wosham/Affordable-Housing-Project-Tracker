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
$workSummary = trim(strip_tags((string)($input['work_summary'] ?? '')));
$blockers = trim(strip_tags((string)($input['blockers'] ?? '')));
$weather = trim(strip_tags((string)($input['weather_note'] ?? '')));
$mediaId = Security::cleanInt($input['progress_media_id'] ?? 0);
$role = (string)Auth::role();
$userId = (int)Auth::id();

if ($projectId <= 0) {
    Response::json(['success' => false, 'message' => 'Project id is required.'], 422);
}

$project = Project::findDetailed($projectId);
if (!$project) {
    Response::json(['success' => false, 'message' => 'Project could not be found.'], 404);
}

if (!project_progress_can_update($userId, $role, $projectId, $project)) {
    Response::json(['success' => false, 'message' => 'You do not have access to update this project.'], 403);
}

if ($role === 'contractor') {
    if ($workSummary === '' || $note === '') {
        Response::json(['success' => false, 'message' => 'Work summary and progress note are required.'], 422);
    }
    $oldProgress = percentage($project['pct_complete'] ?? 0);
    $change = $progress - $oldProgress;
    if ($change < 0 && strlen($note) < 20) {
        Response::json(['success' => false, 'message' => 'A clear correction note is required when reducing progress.'], 422);
    }
    if (abs($change) >= 20 && strlen($workSummary) < 40) {
        Response::json(['success' => false, 'message' => 'A fuller work summary is required for large progress changes.'], 422);
    }
    if ($mediaId <= 0 && !isset($_FILES['progress_photo'])) {
        Response::json(['success' => false, 'message' => 'A site photo is required for contractor progress updates.'], 422);
    }
}

$photoPath = '';
$selectedMedia = null;
if ($mediaId > 0) {
    $selectedMedia = MediaLibrary::findDetailed($mediaId);
    if (!$selectedMedia) {
        Response::json(['success' => false, 'message' => 'Selected site photo could not be found.'], 404);
    }
    if ($role === 'contractor' && ((int)($selectedMedia['uploaded_by'] ?? 0) !== $userId || (string)($selectedMedia['folder'] ?? '') !== 'site-photos' || MediaLibrary::typeGroup((string)($selectedMedia['type'] ?? ''), (string)($selectedMedia['extension'] ?? '')) !== 'image')) {
        Response::json(['success' => false, 'message' => 'Selected site photo is not available for this account.'], 403);
    }
    $photoPath = (string)($selectedMedia['path'] ?? '');
}

if (isset($_FILES['progress_photo']) && (int)($_FILES['progress_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $folder = 'uploads/progress/' . date('Y') . '/' . date('m');
    $upload = Uploader::image($_FILES['progress_photo'], $folder);
    if (empty($upload['success'])) {
        Response::json(['success' => false, 'message' => $upload['error'] ?? 'Progress photo could not be uploaded.'], 422);
    }
    $photoPath = (string)$upload['path'];
    $mediaId = 0;
}

try {
    if ($role === 'contractor') {
        $updateId = ContractorProject::recordProgress([
            'project_id' => $projectId,
            'submitted_by' => $userId,
            'role' => $role,
            'new_progress' => $progress,
            'current_milestone' => $milestone,
            'note' => $note,
            'work_summary' => $workSummary,
            'blockers' => $blockers,
            'weather_note' => $weather,
            'photo_path' => $photoPath,
            'media_id' => $mediaId,
        ]);
    } else {
        $updates = ['pct_complete' => $progress];
        if ($milestone !== '' && project_progress_column_exists('projects', 'current_milestone')) {
            $updates['current_milestone'] = $milestone;
        }
        Project::update($projectId, $updates);
        $updateId = project_progress_log_update($project, $progress, $milestone, $note, $workSummary, $blockers, $weather, $photoPath, $userId, $mediaId);
    }

    if ($progress >= 100 && (string)($project['status'] ?? '') !== 'completed') {
        Notification::pushRole('superadmin', 'project_progress', 'Project reached completion', ($project['name'] ?? 'A project') . ' has been marked 100% complete.', 'admin/superadmin/projects.php');
    }
} catch (Throwable) {
    Response::json(['success' => false, 'message' => 'Project progress could not be updated.'], 500);
}

Response::json([
    'success' => true,
    'message' => 'Project progress submitted.',
    'update_id' => $updateId,
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

    if ($role === 'contractor') {
        return ContractorProject::canAccess($userId, $role, $projectId);
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

function project_progress_log_update(array $project, int $progress, string $milestone, string $note, string $workSummary, string $blockers, string $weather, string $photoPath, int $userId, int $mediaId = 0): int
{
    $projectId = (int)($project['id'] ?? 0);
    $oldProgress = percentage($project['pct_complete'] ?? 0);
    try {
        Database::query(
            "INSERT INTO project_progress_updates
                (project_id, submitted_by, old_progress, new_progress, current_milestone, note, photo_path, media_id, weather_note, work_summary, blockers, status, reviewed_by, reviewed_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'accepted', ?, NOW(), NOW())",
            [$projectId, $userId, $oldProgress, $progress, $milestone ?: null, $note ?: null, $photoPath ?: null, $mediaId > 0 ? $mediaId : null, $weather ?: null, $workSummary ?: null, $blockers ?: null, $userId]
        );
        $id = (int)Database::lastInsertId();
        Database::query(
            'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                'update_progress',
                'projects',
                $projectId,
                json_encode(['from' => $oldProgress, 'to' => $progress, 'milestone' => $milestone, 'progress_update_id' => $id], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
        return $id;
    } catch (Throwable) {
        return 0;
    }
}
