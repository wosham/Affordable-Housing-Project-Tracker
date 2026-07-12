<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'auth' => true,
    'csrf' => false,
]);

$userId = (int)Auth::id();
$role = (string)(Auth::role() ?: '');
$projectId = Security::cleanInt($_GET['project_id'] ?? 0);
$projectContext = $projectId > 0 ? $projectId : null;
$q = strtolower(trim(Security::cleanString((string)($_GET['q'] ?? ''))));

if ($projectContext !== null) {
    $canLink = $role === 'superadmin'
        || $role === 'finance'
        || ProjectAssignment::canManageProject($userId, $projectContext, $role)
        || ProjectAccess::canViewProject($userId, $role, $projectContext);
    if (!$canLink) {
        Response::json(['success' => false, 'message' => 'You cannot use this project for messaging.'], 403);
    }
}

$recipients = MessageThread::recipientOptions($userId, $role, $projectContext);
if ($q !== '') {
    $recipients = array_values(array_filter(
        $recipients,
        static function (array $row) use ($q): bool {
            $hay = strtolower(trim(implode(' ', [
                (string)($row['name'] ?? ''),
                (string)($row['email'] ?? ''),
                (string)($row['role_slug'] ?? ''),
                role_label((string)($row['role_slug'] ?? '')),
            ])));
            return str_contains($hay, $q);
        }
    ));
}

$audience = MessageThread::audienceOptions($userId, $role, $projectContext);

Response::json([
    'success' => true,
    'projectId' => $projectContext,
    'query' => $q,
    'policy' => [
        'role' => $role,
        'scoped' => $role !== 'superadmin',
        'recipientCount' => count($recipients),
    ],
    'recipients' => array_map(static fn (array $row): array => [
        'id' => (int)$row['id'],
        'name' => trim((string)($row['name'] ?? '')) ?: 'Staff User',
        'email' => (string)($row['email'] ?? ''),
        'role' => (string)($row['role_slug'] ?? ''),
        'roleLabel' => role_label((string)($row['role_slug'] ?? '')),
    ], $recipients),
    'audience' => $audience,
]);
