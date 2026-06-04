<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager'],
    'csrf' => false,
]);

$projectId = Security::cleanInt($_GET['project_id'] ?? 0);
$requestRole = Security::cleanString((string)($_GET['role'] ?? ''));
$q = Security::cleanString((string)($_GET['q'] ?? ''));

if ($projectId > 0 && !ProjectAssignment::canManageProject((int)Auth::id(), $projectId, (string)Auth::role())) {
    Response::json(['success' => false, 'message' => 'You cannot manage this project.'], 403);
}

$users = array_map(static fn (array $user): array => [
    'id' => (int)$user['id'],
    'name' => trim((string)$user['name']) ?: 'Staff User',
    'email' => (string)$user['email'],
    'phone' => (string)($user['phone'] ?? ''),
    'jobTitle' => (string)($user['job_title'] ?? ''),
    'role' => (string)$user['role_slug'],
    'roleLabel' => (string)$user['role_name'],
], ProjectAssignment::availableUsers($requestRole, $projectId, $q));

Response::json(['success' => true, 'users' => $users]);
