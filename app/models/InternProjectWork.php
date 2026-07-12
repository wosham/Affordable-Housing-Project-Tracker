<?php

class InternProjectWork
{
    public const ENTRY_STATUSES = ['draft', 'submitted', 'reviewed', 'needs-correction'];
    public const PHOTO_CATEGORIES = ['progress', 'material', 'equipment', 'safety', 'issue', 'general'];

    public static function projects(int $userId): array
    {
        return InternAttendance::projects($userId);
    }

    public static function defaultProjectId(int $userId, int $requestedId = 0): int
    {
        return InternAttendance::defaultProjectId($userId, $requestedId);
    }

    public static function canAccessProject(int $userId, int $projectId): bool
    {
        return InternAttendance::canAccessProject($userId, $projectId);
    }

    public static function projectOverview(int $userId, int $projectId): array
    {
        if ($projectId <= 0 || !self::canAccessProject($userId, $projectId)) {
            return [];
        }

        $project = Database::fetch(
            "SELECT p.*,
                    c.name AS constituency_name,
                    w.name AS ward_name,
                    pc.name AS category_name,
                    contractor.first_name AS contractor_first_name,
                    contractor.last_name AS contractor_last_name,
                    consultant.first_name AS consultant_first_name,
                    consultant.last_name AS consultant_last_name
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             LEFT JOIN project_categories pc ON pc.id = p.category_id
             LEFT JOIN users contractor ON contractor.id = p.contractor_id
             LEFT JOIN users consultant ON consultant.id = p.consultant_id
             WHERE p.id = ?
             LIMIT 1",
            [$projectId]
        ) ?: [];

        $project['contractor_label'] = trim((string)($project['contractor_name'] ?? '')) ?:
            trim((string)($project['contractor_first_name'] ?? '') . ' ' . (string)($project['contractor_last_name'] ?? ''));
        $project['consultant_label'] = trim((string)($project['consultant_first_name'] ?? '') . ' ' . (string)($project['consultant_last_name'] ?? ''));
        $project['progress_label'] = percentage((float)($project['pct_complete'] ?? 0)) . '%';
        $project['location_label'] = trim((string)($project['constituency_name'] ?? '') . ' / ' . (string)($project['ward_name'] ?? ''), ' /') ?: 'Assigned site';

        return $project;
    }

    public static function contacts(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        return Database::fetchAll(
            "SELECT pa.role, pa.scope, u.id, u.first_name, u.last_name, u.email, u.phone, u.job_title, r.name AS role_name
             FROM project_assignments pa
             JOIN users u ON u.id = pa.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE pa.project_id = ? AND pa.status = 'active'
             ORDER BY FIELD(pa.role, 'manager', 'clerk', 'consultant', 'contractor', 'intern'), u.first_name ASC",
            [$projectId]
        );
    }

    public static function summary(int $userId, int $projectId): array
    {
        $entries = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(entry_date = CURDATE()) AS today,
                    SUM(status = 'draft') AS drafts,
                    SUM(status = 'needs-correction') AS corrections
             FROM intern_site_entries
             WHERE user_id = ? AND project_id = ?",
            [$userId, $projectId]
        ) ?: [];

        $photos = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(DATE(created_at) = CURDATE()) AS today
             FROM intern_site_photos
             WHERE user_id = ? AND project_id = ? AND status <> 'deleted'",
            [$userId, $projectId]
        ) ?: [];

        $tasks = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status IN ('complete','completed','done') THEN 1 ELSE 0 END) AS done,
                    SUM(CASE WHEN status NOT IN ('complete','completed','done','cancelled') AND COALESCE(planned_end, end_date) < CURDATE() THEN 1 ELSE 0 END) AS delayed_tasks
             FROM programme_tasks
             WHERE project_id = ?",
            [$projectId]
        ) ?: [];

        return [
            'entries_total' => (int)($entries['total'] ?? 0),
            'entries_today' => (int)($entries['today'] ?? 0),
            'drafts' => (int)($entries['drafts'] ?? 0),
            'corrections' => (int)($entries['corrections'] ?? 0),
            'photos_total' => (int)($photos['total'] ?? 0),
            'photos_today' => (int)($photos['today'] ?? 0),
            'tasks_total' => (int)($tasks['total'] ?? 0),
            'tasks_done' => (int)($tasks['done'] ?? 0),
            'tasks_delayed' => (int)($tasks['delayed_tasks'] ?? 0),
        ];
    }

    public static function currentTasks(int $projectId, int $limit = 6): array
    {
        return Database::fetchAll(
            "SELECT id, task_name, status, pct_complete, COALESCE(planned_end, end_date) AS due_date, critical_path
             FROM programme_tasks
             WHERE project_id = ?
             ORDER BY FIELD(status, 'in_progress', 'pending', 'complete', 'completed', 'done'), COALESCE(planned_end, end_date) ASC, sort_order ASC
             LIMIT " . max(1, $limit),
            [$projectId]
        );
    }

    public static function documents(int $projectId, int $limit = 6): array
    {
        return Database::fetchAll(
            "SELECT id, original_name, category, size, created_at, consultant_review_status
             FROM documents
             WHERE project_id = ? AND is_confidential = 0
             ORDER BY created_at DESC, id DESC
             LIMIT " . max(1, $limit),
            [$projectId]
        );
    }

    public static function entries(int $userId, int $projectId, int $limit = 12): array
    {
        return Database::fetchAll(
            "SELECT e.*, p.name AS project_name, m.url AS media_url, m.title AS media_title,
                    CONCAT(rv.first_name, ' ', rv.last_name) AS reviewed_by_name
             FROM intern_site_entries e
             JOIN projects p ON p.id = e.project_id
             LEFT JOIN media_library m ON m.id = e.media_id
             LEFT JOIN users rv ON rv.id = e.reviewed_by
             WHERE e.user_id = ? AND e.project_id = ?
             ORDER BY e.entry_date DESC, e.updated_at DESC
             LIMIT " . max(1, $limit),
            [$userId, $projectId]
        );
    }

    public static function photos(int $userId, int $projectId, int $limit = 24): array
    {
        return Database::fetchAll(
            "SELECT sp.*, p.name AS project_name, m.url, m.path, m.title, m.original_name, m.size, m.width, m.height
             FROM intern_site_photos sp
             JOIN projects p ON p.id = sp.project_id
             JOIN media_library m ON m.id = sp.media_id
             WHERE sp.user_id = ? AND sp.project_id = ? AND sp.status <> 'deleted'
             ORDER BY sp.created_at DESC, sp.id DESC
             LIMIT " . max(1, $limit),
            [$userId, $projectId]
        );
    }

    public static function recentActivity(int $userId, int $projectId): array
    {
        $entries = self::entries($userId, $projectId, 4);
        $photos = self::photos($userId, $projectId, 4);

        $activity = [];
        foreach ($entries as $entry) {
            $activity[] = [
                'type' => 'Site note',
                'title' => $entry['entry_title'] ?: 'Site note',
                'meta' => format_date($entry['entry_date']) . ' / ' . status_label($entry['status']),
                'date' => $entry['updated_at'] ?? $entry['created_at'],
            ];
        }
        foreach ($photos as $photo) {
            $activity[] = [
                'type' => 'Photo',
                'title' => $photo['caption'] ?: ($photo['title'] ?: 'Site photo'),
                'meta' => status_label($photo['category']) . ' / ' . format_datetime($photo['created_at']),
                'date' => $photo['created_at'],
            ];
        }

        usort($activity, static fn (array $a, array $b): int => strcmp((string)$b['date'], (string)$a['date']));
        return array_slice($activity, 0, 8);
    }

    public static function saveEntry(int $userId, array $input): int
    {
        $projectId = Security::cleanInt($input['project_id'] ?? 0);
        if (!self::canAccessProject($userId, $projectId)) {
            throw new InvalidArgumentException('You are not assigned to this project.');
        }

        $entryId = Security::cleanInt($input['entry_id'] ?? 0);
        $status = self::choice($input['status'] ?? 'submitted', self::ENTRY_STATUSES, 'submitted');
        $entryDate = self::dateValue($input['entry_date'] ?? date('Y-m-d'));
        $payload = [
            'project_id' => $projectId,
            'user_id' => $userId,
            'entry_date' => $entryDate,
            'entry_title' => Security::cleanString((string)($input['entry_title'] ?? 'Site observation')),
            'work_observed' => trim((string)($input['work_observed'] ?? '')),
            'labour_observed' => trim((string)($input['labour_observed'] ?? '')),
            'materials_observed' => trim((string)($input['materials_observed'] ?? '')),
            'equipment_observed' => trim((string)($input['equipment_observed'] ?? '')),
            'weather_condition' => Security::cleanString((string)($input['weather_condition'] ?? '')),
            'issues' => trim((string)($input['issues'] ?? '')),
            'safety_observations' => trim((string)($input['safety_observations'] ?? '')),
            'progress_note' => trim((string)($input['progress_note'] ?? '')),
            // Column remains milestone_id; form sends programme_task_id (programme task PK).
            'milestone_id' => Security::cleanInt($input['programme_task_id'] ?? $input['milestone_id'] ?? 0) ?: null,
            'media_id' => Security::cleanInt($input['media_id'] ?? 0) ?: null,
            'status' => $status,
        ];

        if ($payload['work_observed'] === '' && $payload['progress_note'] === '') {
            throw new InvalidArgumentException('Add a work observation or progress note before saving.');
        }

        if ($entryId > 0) {
            $existing = Database::fetch('SELECT id, status FROM intern_site_entries WHERE id = ? AND user_id = ? LIMIT 1', [$entryId, $userId]);
            if (!$existing) {
                throw new InvalidArgumentException('Site note could not be found.');
            }
            if ((string)$existing['status'] === 'reviewed') {
                throw new InvalidArgumentException('Reviewed site notes cannot be changed.');
            }

            Database::query(
                'UPDATE intern_site_entries
                 SET project_id = ?, entry_date = ?, entry_title = ?, work_observed = ?, labour_observed = ?, materials_observed = ?,
                     equipment_observed = ?, weather_condition = ?, issues = ?, safety_observations = ?, progress_note = ?,
                     milestone_id = ?, media_id = ?, status = ?, updated_at = NOW()
                 WHERE id = ? AND user_id = ?',
                [
                    $payload['project_id'], $payload['entry_date'], $payload['entry_title'], $payload['work_observed'],
                    $payload['labour_observed'], $payload['materials_observed'], $payload['equipment_observed'],
                    $payload['weather_condition'], $payload['issues'], $payload['safety_observations'], $payload['progress_note'],
                    $payload['milestone_id'], $payload['media_id'], $payload['status'], $entryId, $userId,
                ]
            );
            return $entryId;
        }

        Database::query(
            'INSERT INTO intern_site_entries
                (project_id, user_id, entry_date, entry_title, work_observed, labour_observed, materials_observed, equipment_observed,
                 weather_condition, issues, safety_observations, progress_note, milestone_id, media_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $payload['project_id'], $payload['user_id'], $payload['entry_date'], $payload['entry_title'], $payload['work_observed'],
                $payload['labour_observed'], $payload['materials_observed'], $payload['equipment_observed'],
                $payload['weather_condition'], $payload['issues'], $payload['safety_observations'], $payload['progress_note'],
                $payload['milestone_id'], $payload['media_id'], $payload['status'],
            ]
        );

        return (int)Database::lastInsertId();
    }

    public static function createPhoto(int $userId, int $projectId, int $mediaId, array $input): int
    {
        if (!self::canAccessProject($userId, $projectId)) {
            throw new InvalidArgumentException('You are not assigned to this project.');
        }

        $category = self::choice($input['category'] ?? 'general', self::PHOTO_CATEGORIES, 'general');
        Database::query(
            'INSERT INTO intern_site_photos
                (project_id, user_id, media_id, caption, category, latitude, longitude, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $projectId,
                $userId,
                $mediaId,
                Security::cleanString((string)($input['caption'] ?? '')),
                $category,
                self::floatValue($input['latitude'] ?? null),
                self::floatValue($input['longitude'] ?? null),
                'submitted',
            ]
        );

        return (int)Database::lastInsertId();
    }

    public static function deletePhoto(int $userId, int $photoId): bool
    {
        $photo = Database::fetch('SELECT id, status FROM intern_site_photos WHERE id = ? AND user_id = ? LIMIT 1', [$photoId, $userId]);
        if (!$photo || (string)$photo['status'] === 'reviewed') {
            return false;
        }

        Database::query('UPDATE intern_site_photos SET status = "deleted", deleted_at = NOW() WHERE id = ? AND user_id = ?', [$photoId, $userId]);
        return true;
    }

    public static function choice(mixed $value, array $allowed, string $fallback): string
    {
        $value = strtolower(trim((string)$value));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function dateValue(mixed $date): string
    {
        $date = trim((string)$date);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
    }

    private static function floatValue(mixed $value): ?float
    {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $float === false ? null : (float)$float;
    }
}
