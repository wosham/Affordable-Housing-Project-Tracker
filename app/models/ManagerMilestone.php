<?php

class ManagerMilestone
{
    public const STATUSES = ['pending', 'current', 'done'];
    public const PRIORITIES = ['normal', 'high', 'critical'];

    public static function projects(int $userId, string $role): array
    {
        return ProjectAssignment::managerProjects($userId, $role);
    }

    public static function list(int $userId, string $role, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $limitSql = ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return Database::fetchAll(self::selectSql() . $where . self::orderSql((string)($filters['view'] ?? '')) . $limitSql, $bindings);
    }

    public static function count(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($userId, $role, $filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM milestones m
             INNER JOIN projects p ON p.id = m.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             {$where}",
            $bindings
        );

        return (int)($row['aggregate'] ?? 0);
    }

    public static function summary(int $userId, string $role): array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        $where = $scopeSql !== '' ? 'WHERE ' . $scopeSql : '';
        $dueWindow = SystemConfig::int('programme.due_soon_days', 7);

        $row = Database::fetch(
            "SELECT
                COUNT(m.id) AS total,
                SUM(CASE WHEN m.status = 'current' THEN 1 ELSE 0 END) AS current_count,
                SUM(CASE WHEN m.status = 'done' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN m.status <> 'done' AND m.target_date IS NOT NULL AND m.target_date < CURDATE() THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN m.status <> 'done' AND m.target_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) THEN 1 ELSE 0 END) AS due_soon,
                SUM(CASE WHEN COALESCE(m.priority, 'normal') = 'critical' THEN 1 ELSE 0 END) AS critical,
                ROUND(AVG(COALESCE(m.progress_percent, CASE WHEN m.status = 'done' THEN 100 ELSE 0 END))) AS average_progress
             FROM projects p
             LEFT JOIN milestones m ON m.project_id = p.id
             {$where}",
            array_merge([$dueWindow], $bindings)
        ) ?: [];

        $missing = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM projects p
             {$where}" . ($where === '' ? 'WHERE' : ' AND') . " NOT EXISTS (
                SELECT 1 FROM milestones m
                WHERE m.project_id = p.id AND m.status = 'current'
             )",
            $bindings
        );

        return [
            'total' => (int)($row['total'] ?? 0),
            'current' => (int)($row['current_count'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0),
            'overdue' => (int)($row['overdue'] ?? 0),
            'due_soon' => (int)($row['due_soon'] ?? 0),
            'critical' => (int)($row['critical'] ?? 0),
            'average_progress' => (int)($row['average_progress'] ?? 0),
            'without_current' => (int)($missing['total'] ?? 0),
        ];
    }

    public static function insights(int $userId, string $role): array
    {
        return [
            'overdue' => array_map([self::class, 'payload'], self::list($userId, $role, ['view' => 'overdue'], 6)),
            'dueSoon' => array_map([self::class, 'payload'], self::list($userId, $role, ['view' => 'due-soon'], 6)),
            'recent' => array_map([self::class, 'payload'], self::list($userId, $role, ['view' => 'completed'], 6)),
            'withoutCurrent' => self::projectsWithoutCurrent($userId, $role, 6),
        ];
    }

    public static function projectsWithoutCurrent(int $userId, string $role, int $limit = 6): array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        $where = $scopeSql !== '' ? 'WHERE ' . $scopeSql . ' AND' : 'WHERE';

        return Database::fetchAll(
            "SELECT p.id, p.name, p.status, p.pct_complete, c.name AS constituency_name, w.name AS ward_name
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             {$where} NOT EXISTS (
                SELECT 1 FROM milestones m
                WHERE m.project_id = p.id AND m.status = 'current'
             )
             ORDER BY p.updated_at DESC, p.name ASC
             LIMIT " . max(1, $limit),
            $bindings
        );
    }

    public static function findScoped(int $milestoneId, int $userId, string $role): ?array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        array_unshift($bindings, $milestoneId);
        $where = 'WHERE m.id = ?' . ($scopeSql !== '' ? ' AND ' . $scopeSql : '');

        return Database::fetch(self::selectSql() . $where . ' LIMIT 1', $bindings);
    }

    public static function recordUpdate(int $milestoneId, int $projectId, int $userId, array $before, array $after, string $note = ''): void
    {
        try {
            Database::query(
                'INSERT INTO milestone_updates
                    (milestone_id, project_id, user_id, old_status, new_status, old_target_date, new_target_date, old_progress_percent, new_progress_percent, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $milestoneId,
                    $projectId,
                    $userId,
                    $before['status'] ?? null,
                    $after['status'] ?? null,
                    $before['target_date'] ?? null,
                    $after['target_date'] ?? null,
                    $before['progress_percent'] ?? null,
                    $after['progress_percent'] ?? null,
                    $note !== '' ? $note : null,
                ]
            );
        } catch (Throwable) {
        }
    }

    public static function payload(array $row): array
    {
        $target = $row['target_date'] ?? null;
        $actual = $row['actual_date'] ?? null;
        $status = (string)($row['status'] ?? 'pending');
        $days = $target ? (int)floor((strtotime((string)$target) - strtotime(date('Y-m-d'))) / 86400) : null;
        $isDone = $status === 'done';
        $isOverdue = !$isDone && $days !== null && $days < 0;
        $isDueSoon = !$isDone && $days !== null && $days >= 0 && $days <= SystemConfig::int('programme.due_soon_days', 7);
        $progress = percentage($row['progress_percent'] ?? ($isDone ? 100 : 0));

        return [
            'id' => (int)($row['id'] ?? 0),
            'project_id' => (int)($row['project_id'] ?? 0),
            'project_name' => (string)($row['project_name'] ?? ''),
            'project_status' => (string)($row['project_status'] ?? ''),
            'constituency_name' => (string)($row['constituency_name'] ?? ''),
            'ward_name' => (string)($row['ward_name'] ?? ''),
            'label' => (string)($row['label'] ?? ''),
            'description' => (string)($row['description'] ?? ''),
            'status' => $status,
            'status_label' => status_label($status),
            'priority' => (string)($row['priority'] ?? 'normal'),
            'priority_label' => status_label((string)($row['priority'] ?? 'normal')),
            'target_date' => (string)($target ?? ''),
            'actual_date' => (string)($actual ?? ''),
            'target_label' => format_date($target),
            'actual_label' => format_date($actual),
            'days' => $days,
            'time_label' => self::timeLabel($days, $isDone),
            'is_overdue' => $isOverdue,
            'is_due_soon' => $isDueSoon,
            'progress' => $progress,
            'sequence' => (int)($row['sequence'] ?? 0),
            'notes' => (string)($row['notes'] ?? ''),
            'updated_by_name' => trim((string)($row['updated_by_name'] ?? '')),
            'completed_by_name' => trim((string)($row['completed_by_name'] ?? '')),
            'updated_at' => (string)($row['updated_at'] ?? ''),
            'updated_label' => format_datetime($row['updated_at'] ?? null),
        ];
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                m.*,
                p.name AS project_name,
                p.status AS project_status,
                p.pct_complete AS project_progress,
                c.name AS constituency_name,
                w.name AS ward_name,
                CONCAT(COALESCE(updater.first_name, ''), ' ', COALESCE(updater.last_name, '')) AS updated_by_name,
                CONCAT(COALESCE(completer.first_name, ''), ' ', COALESCE(completer.last_name, '')) AS completed_by_name
            FROM milestones m
            INNER JOIN projects p ON p.id = m.project_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            LEFT JOIN users updater ON updater.id = m.updated_by
            LEFT JOIN users completer ON completer.id = m.completed_by
        ";
    }

    private static function filterSql(int $userId, string $role, array $filters): array
    {
        $where = [];
        $bindings = [];
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, 'p');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }

        if (!empty($filters['project_id'])) {
            $where[] = 'm.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'm.status = ?';
            $bindings[] = (string)$filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = 'COALESCE(m.priority, "normal") = ?';
            $bindings[] = (string)$filters['priority'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'm.target_date >= ?';
            $bindings[] = (string)$filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'm.target_date <= ?';
            $bindings[] = (string)$filters['date_to'];
        }

        $view = (string)($filters['view'] ?? '');
        if ($view === 'overdue') {
            $where[] = "m.status <> 'done' AND m.target_date IS NOT NULL AND m.target_date < CURDATE()";
        } elseif ($view === 'due-soon') {
            $where[] = "m.status <> 'done' AND m.target_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
            $bindings[] = SystemConfig::int('programme.due_soon_days', 7);
        } elseif ($view === 'completed') {
            $where[] = "m.status = 'done'";
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(m.label LIKE ? OR m.description LIKE ? OR m.notes LIKE ? OR p.name LIKE ? OR c.name LIKE ? OR w.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function scopeSql(int $userId, string $role, string $projectAlias): array
    {
        if ($role === 'superadmin') {
            return ['', []];
        }

        return [
            "EXISTS (
                SELECT 1 FROM project_assignments scope_pa
                WHERE scope_pa.project_id = {$projectAlias}.id
                  AND scope_pa.user_id = ?
                  AND scope_pa.status = 'active'
            )",
            [$userId],
        ];
    }

    private static function orderSql(string $view): string
    {
        if ($view === 'completed') {
            return ' ORDER BY m.actual_date DESC, m.updated_at DESC, m.id DESC';
        }

        return " ORDER BY
            CASE WHEN m.status <> 'done' AND m.target_date IS NOT NULL AND m.target_date < CURDATE() THEN 0 ELSE 1 END,
            CASE m.status WHEN 'current' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END,
            m.target_date IS NULL ASC,
            m.target_date ASC,
            m.sequence ASC,
            m.id ASC";
    }

    private static function timeLabel(?int $days, bool $isDone): string
    {
        if ($isDone) {
            return 'Completed';
        }

        if ($days === null) {
            return 'No target date';
        }

        if ($days < 0) {
            return abs($days) . ' day' . (abs($days) === 1 ? '' : 's') . ' overdue';
        }

        if ($days === 0) {
            return 'Due today';
        }

        return $days . ' day' . ($days === 1 ? '' : 's') . ' remaining';
    }
}
