<?php

class ProgrammeTask extends Model
{
    protected static string $table = 'programme_tasks';

    public const STATUSES = ['pending', 'not_started', 'in_progress', 'delayed', 'complete', 'on_hold', 'cancelled'];
    public const DELAY_FILTERS = ['delayed', 'due', 'blocked', 'critical', 'clear'];

    public static function forProject(int $projectId): array
    {
        return self::items(['project_id' => $projectId]);
    }

    public static function items(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(
            self::selectSql() . $where . ' ORDER BY p.name ASC, pt.sort_order ASC, COALESCE(pt.planned_start, pt.start_date, pt.created_at) ASC, pt.id ASC' . $limitSql,
            $bindings
        );
    }

    public static function countItems(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch(
            "SELECT COUNT(*) AS total
             FROM programme_tasks pt
             JOIN projects p ON p.id = pt.project_id
             {$where}",
            $bindings
        );

        return (int)($row['total'] ?? 0);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE pt.id = ? LIMIT 1', [$id]);
    }

    public static function summary(array $filters = []): array
    {
        [$where, $bindings] = self::filterSql($filters, false);
        return Database::fetch(
            "SELECT
                COUNT(*) AS total_tasks,
                COALESCE(SUM(CASE WHEN pt.status IN ('pending','not_started') THEN 1 ELSE 0 END), 0) AS not_started,
                COALESCE(SUM(CASE WHEN pt.status = 'in_progress' THEN 1 ELSE 0 END), 0) AS in_progress,
                COALESCE(SUM(CASE WHEN pt.status = 'complete' THEN 1 ELSE 0 END), 0) AS complete,
                COALESCE(SUM(CASE WHEN pt.status = 'on_hold' THEN 1 ELSE 0 END), 0) AS on_hold,
                COALESCE(SUM(CASE WHEN pt.status <> 'complete' AND pt.status <> 'cancelled' AND pt.planned_end IS NOT NULL AND pt.planned_end < CURDATE() THEN 1 ELSE 0 END), 0) AS delayed_tasks,
                COALESCE(SUM(CASE WHEN pt.critical_path = 1 THEN 1 ELSE 0 END), 0) AS critical,
                COALESCE(AVG(pt.pct_complete), 0) AS avg_progress,
                MIN(COALESCE(pt.planned_start, pt.start_date)) AS timeline_start,
                MAX(COALESCE(pt.planned_end, pt.end_date)) AS timeline_end
             FROM programme_tasks pt
             JOIN projects p ON p.id = pt.project_id
             {$where}",
            $bindings
        ) ?: [];
    }

    public static function publicSummaryForProject(int $projectId): array
    {
        $summary = self::summary(['project_id' => $projectId]);
        $current = Database::fetch(
            "SELECT task_name, status, pct_complete, planned_end
             FROM programme_tasks
             WHERE project_id = ?
               AND status NOT IN ('complete', 'cancelled')
             ORDER BY critical_path DESC, planned_end IS NULL ASC, planned_end ASC, sort_order ASC, id ASC
             LIMIT 1",
            [$projectId]
        ) ?: [];

        return [
            'total_tasks' => (int)($summary['total_tasks'] ?? 0),
            'not_started' => (int)($summary['not_started'] ?? 0),
            'in_progress' => (int)($summary['in_progress'] ?? 0),
            'complete' => (int)($summary['complete'] ?? 0),
            'needs_attention' => (int)($summary['delayed_tasks'] ?? 0),
            'average_progress' => (int)round((float)($summary['avg_progress'] ?? 0)),
            'timeline_start' => (string)($summary['timeline_start'] ?? ''),
            'timeline_end' => (string)($summary['timeline_end'] ?? ''),
            'current_task' => [
                'name' => (string)($current['task_name'] ?? ''),
                'status' => (string)($current['status'] ?? ''),
                'pct_complete' => percentage($current['pct_complete'] ?? 0),
                'planned_end' => (string)($current['planned_end'] ?? ''),
            ],
        ];
    }

    public static function projectOptions(): array
    {
        return Database::fetchAll(
            "SELECT
                p.id,
                p.name,
                p.contractor_name,
                c.name AS constituency_name,
                COUNT(pt.id) AS task_count,
                COALESCE(AVG(pt.pct_complete), 0) AS avg_progress,
                COALESCE(SUM(CASE WHEN pt.status <> 'complete' AND pt.status <> 'cancelled' AND pt.planned_end IS NOT NULL AND pt.planned_end < CURDATE() THEN 1 ELSE 0 END), 0) AS delayed_count
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN programme_tasks pt ON pt.project_id = p.id
             GROUP BY p.id, p.name, p.contractor_name, c.name
             ORDER BY p.name ASC"
        );
    }

    public static function assignees(?int $projectId = null): array
    {
        $bindings = [];
        $where = '';
        if ($projectId !== null && $projectId > 0) {
            $where = ' WHERE pt.project_id = ?';
            $bindings[] = $projectId;
        }

        return Database::fetchAll(
            "SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, r.slug AS role
             FROM programme_tasks pt
             JOIN users u ON u.id = pt.assigned_to
             LEFT JOIN roles r ON r.id = u.role_id
             {$where}
             ORDER BY u.first_name, u.last_name",
            $bindings
        );
    }

    public static function assignableUsers(): array
    {
        return Database::fetchAll(
            "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, r.slug AS role
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.status = 'active'
             ORDER BY r.slug, u.first_name, u.last_name"
        );
    }

    public static function dependenciesForProject(int $projectId): array
    {
        return Database::fetchAll(
            'SELECT id, task_name FROM programme_tasks WHERE project_id = ? ORDER BY sort_order ASC, task_name ASC',
            [$projectId]
        );
    }

    public static function updateTask(int $id, array $data, int $userId): bool
    {
        $current = self::findDetailed($id);
        if (!$current) {
            return false;
        }

        $status = (string)($data['status'] ?? $current['status'] ?? 'pending');
        $pct = percentage((float)($data['pct_complete'] ?? $current['pct_complete'] ?? 0));
        if ($pct >= 100 && !in_array($status, ['cancelled', 'on_hold'], true)) {
            $status = 'complete';
            $pct = 100;
        }
        if ($status === 'complete') {
            $pct = 100;
        }
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'pending';
        }

        $updates = [
            'task_name' => trim((string)($data['task_name'] ?? $current['task_name'])),
            'planned_start' => self::normaliseDate($data['planned_start'] ?? $current['planned_start']),
            'planned_end' => self::normaliseDate($data['planned_end'] ?? $current['planned_end']),
            'start_date' => self::normaliseDate($data['start_date'] ?? $current['start_date']),
            'end_date' => self::normaliseDate($data['end_date'] ?? $current['end_date']),
            'pct_complete' => $pct,
            'status' => $status,
            'assigned_to' => !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
            'depends_on_task_id' => !empty($data['depends_on_task_id']) ? (int)$data['depends_on_task_id'] : null,
            'critical_path' => !empty($data['critical_path']) ? 1 : 0,
            'notes' => trim((string)($data['notes'] ?? '')) ?: null,
            'updated_by' => $userId,
        ];

        return self::update($id, $updates);
    }

    public static function payload(array $task): array
    {
        $plannedStart = self::normaliseDate($task['planned_start'] ?? $task['start_date'] ?? null);
        $plannedEnd = self::normaliseDate($task['planned_end'] ?? $task['end_date'] ?? null);
        $actualStart = self::normaliseDate($task['start_date'] ?? null);
        $actualEnd = self::normaliseDate($task['end_date'] ?? null);
        $pct = percentage($task['pct_complete'] ?? 0);
        $status = (string)($task['status'] ?? 'pending');
        $today = date('Y-m-d');
        $isDelayed = $status !== 'complete' && $status !== 'cancelled' && $plannedEnd !== null && $plannedEnd < $today;
        $isDue = $status !== 'complete' && $status !== 'cancelled' && $plannedEnd !== null && $plannedEnd >= $today && $plannedEnd <= date('Y-m-d', strtotime('+7 days'));
        $isBlocked = !empty($task['depends_on_task_id']) && (string)($task['dependency_status'] ?? '') !== '' && (string)$task['dependency_status'] !== 'complete';

        return array_merge($task, [
            'id' => (int)$task['id'],
            'project_id' => (int)$task['project_id'],
            'assigned_to' => !empty($task['assigned_to']) ? (int)$task['assigned_to'] : null,
            'depends_on_task_id' => !empty($task['depends_on_task_id']) ? (int)$task['depends_on_task_id'] : null,
            'critical_path' => (int)($task['critical_path'] ?? 0),
            'pct_complete' => $pct,
            'planned_start' => $plannedStart,
            'planned_end' => $plannedEnd,
            'start_date' => $actualStart,
            'end_date' => $actualEnd,
            'planned_duration_days' => self::durationDays($plannedStart, $plannedEnd),
            'actual_duration_days' => self::durationDays($actualStart, $actualEnd),
            'start_variance_days' => self::varianceDays($plannedStart, $actualStart),
            'finish_variance_days' => self::varianceDays($plannedEnd, $actualEnd),
            'is_delayed' => $isDelayed,
            'is_due' => $isDue,
            'is_blocked' => $isBlocked,
            'delay_state' => $isBlocked ? 'blocked' : ($isDelayed ? 'delayed' : ($isDue ? 'due' : 'clear')),
        ]);
    }

    public static function timeline(array $tasks): array
    {
        $dates = [];
        foreach ($tasks as $task) {
            foreach (['planned_start', 'planned_end', 'start_date', 'end_date'] as $key) {
                if (!empty($task[$key]) && strtotime((string)$task[$key]) !== false) {
                    $dates[] = (string)$task[$key];
                }
            }
        }

        if ($dates === []) {
            $start = date('Y-m-01');
            $end = date('Y-m-t', strtotime('+2 months'));
        } else {
            sort($dates);
            $start = date('Y-m-d', strtotime($dates[0] . ' -7 days'));
            $end = date('Y-m-d', strtotime($dates[count($dates) - 1] . ' +14 days'));
        }

        $days = max(1, self::durationDays($start, $end));
        return ['start' => $start, 'end' => $end, 'days' => $days];
    }

    public static function statusOptions(): array
    {
        return self::STATUSES;
    }

    public static function delayOptions(): array
    {
        return self::DELAY_FILTERS;
    }

    public static function normaliseDate(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    public static function durationDays(?string $start, ?string $end): int
    {
        if (!$start || !$end || strtotime($start) === false || strtotime($end) === false) {
            return 0;
        }

        return max(0, (int)floor((strtotime($end) - strtotime($start)) / 86400) + 1);
    }

    private static function varianceDays(?string $planned, ?string $actual): int
    {
        if (!$planned || !$actual || strtotime($planned) === false || strtotime($actual) === false) {
            return 0;
        }

        return (int)floor((strtotime($actual) - strtotime($planned)) / 86400);
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                pt.*,
                p.name AS project_name,
                p.contractor_name,
                c.name AS constituency_name,
                COALESCE(CONCAT(a.first_name, ' ', a.last_name), '') AS assignee_name,
                ar.slug AS assignee_role,
                dep.task_name AS dependency_name,
                dep.status AS dependency_status,
                COALESCE(CONCAT(up.first_name, ' ', up.last_name), '') AS updated_by_name
            FROM programme_tasks pt
            JOIN projects p ON p.id = pt.project_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN users a ON a.id = pt.assigned_to
            LEFT JOIN roles ar ON ar.id = a.role_id
            LEFT JOIN programme_tasks dep ON dep.id = pt.depends_on_task_id
            LEFT JOIN users up ON up.id = pt.updated_by
        ";
    }

    private static function filterSql(array $filters, bool $allowDelay = true): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['project_id'])) {
            $where[] = 'pt.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'pt.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = 'pt.assigned_to = ?';
            $bindings[] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['date_from']) && self::normaliseDate($filters['date_from']) !== null) {
            $where[] = 'COALESCE(pt.planned_end, pt.end_date, pt.planned_start, pt.start_date) >= ?';
            $bindings[] = self::normaliseDate($filters['date_from']);
        }
        if (!empty($filters['date_to']) && self::normaliseDate($filters['date_to']) !== null) {
            $where[] = 'COALESCE(pt.planned_start, pt.start_date, pt.planned_end, pt.end_date) <= ?';
            $bindings[] = self::normaliseDate($filters['date_to']);
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(pt.task_name LIKE ? OR p.name LIKE ? OR pt.notes LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term);
        }

        $delay = (string)($filters['delay'] ?? '');
        if ($allowDelay && in_array($delay, self::DELAY_FILTERS, true)) {
            $where[] = match ($delay) {
                'delayed' => "pt.status <> 'complete' AND pt.status <> 'cancelled' AND pt.planned_end IS NOT NULL AND pt.planned_end < CURDATE()",
                'due' => "pt.status <> 'complete' AND pt.status <> 'cancelled' AND pt.planned_end IS NOT NULL AND pt.planned_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
                'blocked' => "pt.depends_on_task_id IS NOT NULL",
                'critical' => 'pt.critical_path = 1',
                'clear' => "pt.status = 'complete' OR pt.planned_end IS NULL OR pt.planned_end >= CURDATE()",
                default => '1 = 1',
            };
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
