<?php

class ContractorProject
{
    public static function projectIds(int $userId, string $role): array
    {
        return ContractorDashboard::projectIds($userId, $role);
    }

    public static function projects(int $userId, string $role): array
    {
        $projectIds = self::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }

        [$in, $bindings] = self::inClause($projectIds);
        return Database::fetchAll(
            "SELECT p.id, p.name, p.status, p.pct_complete, p.contract_sum, p.est_delivery, p.current_milestone,
                    c.name AS constituency_name, w.name AS ward_name
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             WHERE p.id IN ({$in})
             ORDER BY p.status = 'active' DESC, p.name ASC",
            $bindings
        );
    }

    public static function defaultProjectId(int $userId, string $role, int $requestedId = 0): int
    {
        $projectIds = self::projectIds($userId, $role);
        if ($requestedId > 0 && in_array($requestedId, $projectIds, true)) {
            return $requestedId;
        }

        return (int)($projectIds[0] ?? 0);
    }

    public static function canAccess(int $userId, string $role, int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        if (strtolower($role) === 'superadmin') {
            return Project::findDetailed($projectId) !== null;
        }

        return in_array($projectId, self::projectIds($userId, $role), true);
    }

    public static function detail(int $projectId, int $userId, string $role): ?array
    {
        if (!self::canAccess($userId, $role, $projectId)) {
            return null;
        }

        return Database::fetch(
            "SELECT p.*, pc.name AS category_name, c.name AS constituency_name, w.name AS ward_name,
                    CONCAT(COALESCE(consultant.first_name, ''), ' ', COALESCE(consultant.last_name, '')) AS consultant_name,
                    consultant.email AS consultant_email,
                    consultant.phone AS consultant_phone,
                    CONCAT(COALESCE(contractor.first_name, ''), ' ', COALESCE(contractor.last_name, '')) AS contractor_name,
                    contractor.email AS contractor_email
             FROM projects p
             LEFT JOIN project_categories pc ON pc.id = p.category_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             LEFT JOIN users consultant ON consultant.id = p.consultant_id
             LEFT JOIN users contractor ON contractor.id = p.contractor_id
             WHERE p.id = ?
             LIMIT 1",
            [$projectId]
        ) ?: null;
    }

    public static function summary(int $projectId, int $userId, string $role): array
    {
        if (!self::canAccess($userId, $role, $projectId)) {
            return self::emptySummary();
        }

        $boq = Database::fetch(
            "SELECT
                COUNT(*) AS items,
                COALESCE(SUM(COALESCE(NULLIF(amount, 0), quantity * rate)), 0) AS contract_value,
                COALESCE(SUM(certified_qty * rate), 0) AS certified_value,
                COALESCE(SUM(paid_qty * rate), 0) AS paid_value,
                COALESCE(SUM(CASE WHEN risk_status IN ('watch','high','critical') THEN 1 ELSE 0 END), 0) AS risk_items
             FROM boq_items
             WHERE project_id = ?",
            [$projectId]
        ) ?: [];

        $ipc = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN status NOT IN ('paid','rejected') THEN 1 ELSE 0 END), 0) AS active,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN net_amount ELSE 0 END), 0) AS approved_unpaid,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END), 0) AS paid
             FROM ipcs
             WHERE project_id = ? AND contractor_id = ?",
            [$projectId, $userId]
        ) ?: [];

        $programme = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN status IN ('in_progress','active') THEN 1 ELSE 0 END), 0) AS in_progress,
                COALESCE(SUM(CASE WHEN status IN ('done','completed','complete') THEN 1 ELSE 0 END), 0) AS completed,
                COALESCE(SUM(CASE WHEN COALESCE(status, '') NOT IN ('done','completed','complete','cancelled') AND COALESCE(planned_end, end_date) < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue,
                ROUND(AVG(COALESCE(pct_complete, 0))) AS average_progress
             FROM programme_tasks
             WHERE project_id = ?",
            [$projectId]
        ) ?: [];

        $submissions = Database::fetch(
            "SELECT
                (SELECT COUNT(*) FROM rfis WHERE project_id = ? AND raised_by = ? AND status = 'open') AS rfis,
                (SELECT COUNT(*) FROM material_approvals WHERE project_id = ? AND submitted_by = ? AND status = 'pending') AS materials,
                (SELECT COUNT(*) FROM shop_drawings WHERE project_id = ? AND submitted_by = ? AND status IN ('under-review','resubmit')) AS drawings,
                (SELECT COUNT(*) FROM eot_requests WHERE project_id = ? AND submitted_by = ? AND status = 'pending') AS eots,
                (SELECT COUNT(*) FROM variations WHERE project_id = ? AND submitted_by = ? AND status = 'pending') AS variations",
            [$projectId, $userId, $projectId, $userId, $projectId, $userId, $projectId, $userId, $projectId, $userId]
        ) ?: [];

        return [
            'boq_items' => (int)($boq['items'] ?? 0),
            'boq_value' => (float)($boq['contract_value'] ?? 0),
            'boq_certified' => (float)($boq['certified_value'] ?? 0),
            'boq_paid' => (float)($boq['paid_value'] ?? 0),
            'boq_risk_items' => (int)($boq['risk_items'] ?? 0),
            'ipc_total' => (int)($ipc['total'] ?? 0),
            'ipc_active' => (int)($ipc['active'] ?? 0),
            'ipc_approved_unpaid' => (float)($ipc['approved_unpaid'] ?? 0),
            'ipc_paid' => (float)($ipc['paid'] ?? 0),
            'programme_total' => (int)($programme['total'] ?? 0),
            'programme_in_progress' => (int)($programme['in_progress'] ?? 0),
            'programme_completed' => (int)($programme['completed'] ?? 0),
            'programme_overdue' => (int)($programme['overdue'] ?? 0),
            'programme_average' => (int)($programme['average_progress'] ?? 0),
            'rfis' => (int)($submissions['rfis'] ?? 0),
            'materials' => (int)($submissions['materials'] ?? 0),
            'drawings' => (int)($submissions['drawings'] ?? 0),
            'eots' => (int)($submissions['eots'] ?? 0),
            'variations' => (int)($submissions['variations'] ?? 0),
        ];
    }

    public static function progressHistory(int $projectId, int $userId, string $role, int $limit = 10): array
    {
        if (!self::canAccess($userId, $role, $projectId)) {
            return [];
        }

        try {
            return Database::fetchAll(
                "SELECT ppu.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS submitted_by_name,
                        CONCAT(COALESCE(rv.first_name, ''), ' ', COALESCE(rv.last_name, '')) AS reviewed_by_name
                 FROM project_progress_updates ppu
                 LEFT JOIN users u ON u.id = ppu.submitted_by
                 LEFT JOIN users rv ON rv.id = ppu.reviewed_by
                 WHERE ppu.project_id = ?
                 ORDER BY ppu.created_at DESC, ppu.id DESC
                 LIMIT " . max(1, $limit),
                [$projectId]
            );
        } catch (Throwable) {
            return [];
        }
    }

    public static function programmeTasks(int $projectId, int $userId, string $role, int $limit = 8): array
    {
        if (!self::canAccess($userId, $role, $projectId)) {
            return [];
        }

        return Database::fetchAll(
            "SELECT *
             FROM programme_tasks
             WHERE project_id = ?
             ORDER BY FIELD(COALESCE(status, 'pending'), 'in_progress', 'pending', 'not_started', 'delayed', 'on_hold', 'complete', 'cancelled'),
                      COALESCE(planned_end, end_date) ASC, sort_order ASC
             LIMIT " . max(1, $limit),
            [$projectId]
        );
    }

    public static function latestIpcs(int $projectId, int $userId, string $role, int $limit = 5): array
    {
        if (!self::canAccess($userId, $role, $projectId)) {
            return [];
        }

        return Database::fetchAll(
            "SELECT *
             FROM ipcs
             WHERE project_id = ? AND contractor_id = ?
             ORDER BY updated_at DESC, id DESC
             LIMIT " . max(1, $limit),
            [$projectId, $userId]
        );
    }

    public static function recordProgress(array $payload): int
    {
        $projectId = (int)($payload['project_id'] ?? 0);
        $userId = (int)($payload['submitted_by'] ?? 0);
        $role = (string)($payload['role'] ?? '');
        $project = self::detail($projectId, $userId, $role);
        if (!$project) {
            throw new RuntimeException('Project could not be found.');
        }

        $oldProgress = percentage($project['pct_complete'] ?? 0);
        $newProgress = percentage($payload['new_progress'] ?? 0);
        $milestone = trim((string)($payload['current_milestone'] ?? ''));
        $note = trim((string)($payload['note'] ?? ''));
        $workSummary = trim((string)($payload['work_summary'] ?? ''));
        $blockers = trim((string)($payload['blockers'] ?? ''));
        $weather = trim((string)($payload['weather_note'] ?? ''));
        $photoPath = trim((string)($payload['photo_path'] ?? ''));
        $mediaId = (int)($payload['media_id'] ?? 0);

        Database::beginTransaction();
        try {
            Database::query(
                "INSERT INTO project_progress_updates
                    (project_id, submitted_by, old_progress, new_progress, current_milestone, note, photo_path, media_id, weather_note, work_summary, blockers, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())",
                [$projectId, $userId, $oldProgress, $newProgress, $milestone ?: null, $note ?: null, $photoPath ?: null, $mediaId > 0 ? $mediaId : null, $weather ?: null, $workSummary ?: null, $blockers ?: null]
            );
            $id = (int)Database::lastInsertId();

            $updates = ['pct_complete' => $newProgress];
            if ($milestone !== '') {
                $updates['current_milestone'] = $milestone;
            }
            Project::update($projectId, $updates);

            Database::query(
                'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    'contractor_progress_submitted',
                    'projects',
                    $projectId,
                    json_encode(['from' => $oldProgress, 'to' => $newProgress, 'milestone' => $milestone, 'progress_update_id' => $id], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                ]
            );

            Database::commit();
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }

        self::notifyProgress($project, $newProgress);
        return $id;
    }

    private static function notifyProgress(array $project, int $progress): void
    {
        $projectName = (string)($project['name'] ?? 'Project');
        $body = $projectName . ' progress has been updated to ' . $progress . '%.';
        Notification::pushRole('manager', 'project_progress', 'Project progress submitted', $body, 'admin/manager/projects.php');
        Notification::pushRole('consultant', 'project_progress', 'Project progress submitted', $body, 'admin/consultant/dashboard.php');
        Notification::pushRole('superadmin', 'project_progress', 'Project progress submitted', $body, 'admin/superadmin/projects.php');
    }

    private static function emptySummary(): array
    {
        return [
            'boq_items' => 0,
            'boq_value' => 0,
            'boq_certified' => 0,
            'boq_paid' => 0,
            'boq_risk_items' => 0,
            'ipc_total' => 0,
            'ipc_active' => 0,
            'ipc_approved_unpaid' => 0,
            'ipc_paid' => 0,
            'programme_total' => 0,
            'programme_in_progress' => 0,
            'programme_completed' => 0,
            'programme_overdue' => 0,
            'programme_average' => 0,
            'rfis' => 0,
            'materials' => 0,
            'drawings' => 0,
            'eots' => 0,
            'variations' => 0,
        ];
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
