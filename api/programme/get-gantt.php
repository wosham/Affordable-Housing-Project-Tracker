<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager', 'consultant', 'contractor', 'clerk', 'finance'],
    'csrf' => false,
]);

$filters = [
    'project_id' => Security::cleanInt($_GET['project_id'] ?? 0),
    'q' => Security::cleanString((string)($_GET['q'] ?? '')),
    'status' => Security::cleanString((string)($_GET['status'] ?? '')),
    'assigned_to' => Security::cleanInt($_GET['assigned_to'] ?? 0),
    'delay' => Security::cleanString((string)($_GET['delay'] ?? '')),
    'date_from' => ProgrammeTask::normaliseDate($_GET['date_from'] ?? ''),
    'date_to' => ProgrammeTask::normaliseDate($_GET['date_to'] ?? ''),
];
$filters = array_filter($filters, static fn ($value): bool => $value !== '' && $value !== 0 && $value !== null);

if (empty($filters['project_id'])) {
    Response::json(['success' => false, 'message' => 'Choose a project before loading the programme.'], 422);
}

$project = Project::findDetailed((int)$filters['project_id']);
if (!$project) {
    Response::json(['success' => false, 'message' => 'Project could not be found.'], 404);
}

$role = (string)Auth::role();
$userId = (int)Auth::id();
if ($role !== 'superadmin' && !ManagerProgramme::canAccessProject($userId, $role, (int)$filters['project_id'])) {
    Response::json(['success' => false, 'message' => 'You do not have access to this project programme.'], 403);
}

$limit = min(150, max(1, Security::cleanInt($_GET['limit'] ?? 100)));
$page = max(1, Security::cleanInt($_GET['page'] ?? 1));
$total = ProgrammeTask::countItems($filters);
$totalPages = max(1, (int)ceil($total / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;
$tasks = array_map([ProgrammeTask::class, 'payload'], ProgrammeTask::items($filters, $limit, $offset));
$summary = ProgrammeTask::summary($filters);
$timeline = ProgrammeTask::timeline($tasks);

Response::json([
    'success' => true,
    'project' => [
        'id' => (int)$project['id'],
        'name' => $project['name'],
        'contractor' => $project['contractor_name'] ?? '',
        'constituency' => $project['constituency_name'] ?? '',
    ],
    'summary' => programme_api_summary($summary),
    'timeline' => $timeline,
    'tasks' => array_map('programme_api_task', $tasks),
    'dependencies' => array_map(static fn (array $task): array => [
        'id' => (int)$task['id'],
        'taskName' => $task['task_name'],
    ], ProgrammeTask::dependenciesForProject((int)$filters['project_id'])),
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'totalPages' => $totalPages,
    ],
    'filters' => $filters,
]);

function programme_api_summary(array $summary): array
{
    return array_merge($summary, [
        'avg_progress_percent' => percentage($summary['avg_progress'] ?? 0),
        'duration_days' => ProgrammeTask::durationDays($summary['timeline_start'] ?? null, $summary['timeline_end'] ?? null),
    ]);
}

function programme_api_task(array $task): array
{
    return [
        'id' => (int)$task['id'],
        'projectId' => (int)$task['project_id'],
        'projectName' => $task['project_name'],
        'taskName' => $task['task_name'],
        'plannedStart' => $task['planned_start'],
        'plannedEnd' => $task['planned_end'],
        'actualStart' => $task['start_date'],
        'actualEnd' => $task['end_date'],
        'progress' => (int)$task['pct_complete'],
        'status' => $task['status'],
        'statusLabel' => status_label($task['status']),
        'assignedTo' => $task['assigned_to'],
        'assigneeName' => $task['assignee_name'],
        'dependsOnTaskId' => $task['depends_on_task_id'],
        'dependencyName' => $task['dependency_name'],
        'criticalPath' => (bool)$task['critical_path'],
        'plannedDurationDays' => (int)$task['planned_duration_days'],
        'actualDurationDays' => (int)$task['actual_duration_days'],
        'startVarianceDays' => (int)$task['start_variance_days'],
        'finishVarianceDays' => (int)$task['finish_variance_days'],
        'isDelayed' => (bool)$task['is_delayed'],
        'isDue' => (bool)$task['is_due'],
        'isBlocked' => (bool)$task['is_blocked'],
        'delayState' => $task['delay_state'],
        'notes' => $task['notes'] ?? '',
    ];
}
