<?php

class ManagerReport
{
    public static function projects(int $userId, string $role): array
    {
        return ProjectAssignment::managerProjects($userId, $role);
    }

    public static function projectIds(int $userId, string $role): array
    {
        return array_map(static fn (array $project): int => (int)$project['id'], self::projects($userId, $role));
    }

    public static function constituencies(int $userId, string $role): array
    {
        $ids = self::projectIds($userId, $role);
        if ($ids === []) {
            return [];
        }
        [$in, $bindings] = self::inClause($ids);
        return Database::fetchAll(
            "SELECT DISTINCT c.id, c.name
             FROM projects p
             INNER JOIN constituencies c ON c.id = p.constituency_id
             WHERE p.id IN ({$in})
             ORDER BY c.name",
            $bindings
        );
    }

    public static function scopeFilters(int $userId, string $role, array $filters): array
    {
        if ($role !== 'manager') {
            return $filters;
        }

        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            throw new RuntimeException('No assigned projects are available for reporting.');
        }

        $selectedProject = (int)($filters['project_id'] ?? 0);
        if ($selectedProject > 0) {
            if (!in_array($selectedProject, $projectIds, true)) {
                throw new RuntimeException('You cannot generate reports for this project.');
            }
            $filters['project_ids'] = [$selectedProject];
            return $filters;
        }

        $filters['project_ids'] = $projectIds;
        return $filters;
    }

    public static function recentRuns(int $userId, int $limit = 8): array
    {
        try {
            return Database::fetchAll(
                'SELECT rr.*, COALESCE(CONCAT(u.first_name, " ", u.last_name), "System") AS user_name
                 FROM report_runs rr
                 LEFT JOIN users u ON u.id = rr.user_id
                 WHERE rr.scope_role = ? AND rr.scope_user_id = ?
                 ORDER BY rr.created_at DESC, rr.id DESC
                 LIMIT ' . max(1, min(30, $limit)),
                ['manager', $userId]
            );
        } catch (Throwable) {
            return [];
        }
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
