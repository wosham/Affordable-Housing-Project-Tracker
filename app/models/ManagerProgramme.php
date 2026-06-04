<?php

class ManagerProgramme
{
    public static function projects(int $userId, string $role): array
    {
        $projects = ProjectAssignment::managerProjects($userId, $role);
        if ($projects === []) {
            return [];
        }

        $ids = array_map(static fn (array $project): int => (int)$project['id'], $projects);
        [$in, $bindings] = self::inClause($ids);
        $rows = Database::fetchAll(
            "SELECT p.id, COUNT(pt.id) AS task_count,
                    COALESCE(AVG(pt.pct_complete), 0) AS avg_progress,
                    COALESCE(SUM(CASE WHEN pt.status <> 'complete' AND pt.status <> 'cancelled' AND pt.planned_end IS NOT NULL AND pt.planned_end < CURDATE() THEN 1 ELSE 0 END), 0) AS delayed_count
             FROM projects p
             LEFT JOIN programme_tasks pt ON pt.project_id = p.id
             WHERE p.id IN ({$in})
             GROUP BY p.id",
            $bindings
        );

        $meta = [];
        foreach ($rows as $row) {
            $meta[(int)$row['id']] = $row;
        }

        foreach ($projects as &$project) {
            $row = $meta[(int)$project['id']] ?? [];
            $project['task_count'] = (int)($row['task_count'] ?? 0);
            $project['avg_progress'] = (float)($row['avg_progress'] ?? 0);
            $project['delayed_count'] = (int)($row['delayed_count'] ?? 0);
        }
        unset($project);

        return $projects;
    }

    public static function canAccessProject(int $userId, string $role, int $projectId): bool
    {
        return ProjectAssignment::canManageProject($userId, $projectId, $role);
    }

    public static function canAccessTask(int $userId, string $role, array $task): bool
    {
        return self::canAccessProject($userId, $role, (int)($task['project_id'] ?? 0));
    }

    public static function assignableUsers(int $userId, string $role, int $projectId): array
    {
        if (!self::canAccessProject($userId, $role, $projectId)) {
            return [];
        }

        return Database::fetchAll(
            "SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, r.slug AS role
             FROM project_assignments pa
             JOIN users u ON u.id = pa.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE pa.project_id = ? AND pa.status = 'active' AND u.status = 'active'
             ORDER BY r.slug ASC, u.first_name ASC, u.last_name ASC",
            [$projectId]
        );
    }

    public static function dueSoonCount(array $filters): int
    {
        $filters['delay'] = 'due';
        return ProgrammeTask::countItems($filters);
    }

    public static function updateSignals(array $old, array $new): array
    {
        $signals = [];
        if (($old['status'] ?? '') !== 'delayed' && ($new['status'] ?? '') === 'delayed') {
            $signals[] = 'Task marked delayed';
        }
        if ((int)($old['critical_path'] ?? 0) !== 1 && (int)($new['critical_path'] ?? 0) === 1) {
            $signals[] = 'Task marked critical path';
        }
        if ((int)($new['pct_complete'] ?? 0) < (int)($old['pct_complete'] ?? 0)) {
            $signals[] = 'Task progress reduced';
        }
        if (($old['status'] ?? '') !== 'on_hold' && ($new['status'] ?? '') === 'on_hold') {
            $signals[] = 'Task placed on hold';
        }

        return $signals;
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return ['0', []];
        }

        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
