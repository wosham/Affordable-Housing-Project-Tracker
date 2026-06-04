<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin', 'manager'],
    'csrf_form' => 'manager_projects',
]);

$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    $input = is_array($decoded) ? $decoded : [];
}

$projectId = Security::cleanInt($input['project_id'] ?? 0);
$title = Security::cleanString((string)($input['title'] ?? ''));
$body = Security::cleanString((string)($input['body'] ?? ''));

if ($projectId <= 0 || $title === '') {
    Response::json(['success' => false, 'message' => 'Project and note title are required.'], 422);
}
if (!ManagerProject::canView($projectId, (int)Auth::id(), (string)Auth::role())) {
    Response::json(['success' => false, 'message' => 'You cannot manage this project.'], 403);
}

try {
    $noteId = ManagerProject::saveNote($projectId, (int)Auth::id(), [
        'note_type' => Security::cleanString((string)($input['note_type'] ?? 'monitoring')),
        'title' => $title,
        'body' => $body,
        'severity' => Security::cleanString((string)($input['severity'] ?? 'normal')),
        'status' => Security::cleanString((string)($input['status'] ?? 'open')),
    ]);
    Logger::log('create', 'manager_project_notes', $noteId, ['project_id' => $projectId]);
} catch (Throwable $error) {
    Response::json(['success' => false, 'message' => $error->getMessage() ?: 'Project note could not be saved.'], 500);
}

Response::json(['success' => true, 'message' => 'Project note saved.', 'noteId' => $noteId]);
