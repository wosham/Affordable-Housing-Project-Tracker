<?php

class ConsultantTechnicalReview
{
    public const REVIEW_STATUSES = ['pending', 'reviewed', 'needs-revision', 'approved', 'rejected', 'flagged'];
    public const DRAWING_STATUSES = ['under-review', 'approved', 'rejected', 'resubmit'];
    public const MATERIAL_STATUSES = ['pending', 'approved', 'rejected'];

    public static function projects(int $userId, string $role): array
    {
        [$scopeSql, $bindings] = self::scopeSql($userId, $role, 'p');
        $where = $scopeSql === '' ? '' : ' WHERE ' . $scopeSql;

        return Database::fetchAll(
            "SELECT p.id, p.name, p.status, p.pct_complete, c.name AS constituency_name, w.name AS ward_name
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN wards w ON w.id = p.ward_id
             {$where}
             ORDER BY p.name ASC",
            $bindings
        );
    }

    public static function boqSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::boqFilterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN COALESCE(bi.review_status, 'pending') = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                COALESCE(SUM(CASE WHEN COALESCE(bi.review_status, 'pending') IN ('reviewed','approved') THEN 1 ELSE 0 END), 0) AS reviewed,
                COALESCE(SUM(CASE WHEN COALESCE(bi.review_status, 'pending') IN ('needs-review','needs-revision','escalated') THEN 1 ELSE 0 END), 0) AS needs_revision,
                COALESCE(SUM(CASE WHEN COALESCE(bi.risk_status, 'normal') IN ('watch','high','critical') THEN 1 ELSE 0 END), 0) AS risks,
                COALESCE(SUM(COALESCE(NULLIF(bi.amount, 0), COALESCE(bi.quantity, 0) * COALESCE(bi.rate, 0))), 0) AS contract_value,
                COALESCE(SUM(COALESCE(bi.certified_qty, 0) * COALESCE(bi.rate, 0)), 0) AS certified_value
             FROM boq_items bi
             JOIN projects p ON p.id = bi.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numericSummary($row, ['total', 'pending', 'reviewed', 'needs_revision', 'risks', 'contract_value', 'certified_value']);
    }

    public static function boqItems(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::boqFilterSql($userId, $role, $filters);
        return Database::fetchAll(
            "SELECT bi.*, p.name AS project_name, c.name AS constituency_name,
                    COALESCE(CONCAT(rv.first_name, ' ', rv.last_name), '') AS reviewed_by_name
             FROM boq_items bi
             JOIN projects p ON p.id = bi.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN users rv ON rv.id = bi.last_reviewed_by
             {$where}
             ORDER BY FIELD(COALESCE(bi.review_status, 'pending'), 'pending', 'needs-review', 'needs-revision', 'escalated', 'reviewed', 'approved'),
                      FIELD(COALESCE(bi.risk_status, 'normal'), 'critical', 'high', 'watch', 'normal'),
                      p.name ASC, bi.section ASC, bi.item_no ASC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function boqCount(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::boqFilterSql($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM boq_items bi JOIN projects p ON p.id = bi.project_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Portfolio risk items (not limited to current page). */
    public static function boqRiskItems(int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        $riskFilters = $filters;
        // Prefer risk-ranked list across portfolio (optional risk filter still applied).
        if (empty($riskFilters['risk'])) {
            // No single risk filter — still only elevated risks.
            [$where, $bindings] = self::boqFilterSql($userId, $role, $riskFilters);
            $where = $where === ''
                ? " WHERE COALESCE(bi.risk_status, 'normal') IN ('watch','high','critical')"
                : $where . " AND COALESCE(bi.risk_status, 'normal') IN ('watch','high','critical')";
        } else {
            [$where, $bindings] = self::boqFilterSql($userId, $role, $riskFilters);
        }

        return Database::fetchAll(
            "SELECT bi.id, bi.item_no, bi.section, bi.risk_status, bi.review_status, bi.description, p.name AS project_name
             FROM boq_items bi
             JOIN projects p ON p.id = bi.project_id
             {$where}
             ORDER BY FIELD(COALESCE(bi.risk_status, 'normal'), 'critical', 'high', 'watch', 'normal'),
                      p.name ASC, bi.item_no ASC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function programmeSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::programmeFilterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN COALESCE(pt.consultant_review_status, 'pending') = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                COALESCE(SUM(CASE WHEN COALESCE(pt.consultant_review_status, 'pending') = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
                COALESCE(SUM(CASE WHEN COALESCE(pt.consultant_review_status, 'pending') = 'needs-revision' THEN 1 ELSE 0 END), 0) AS needs_revision,
                COALESCE(SUM(CASE WHEN pt.status NOT IN ('complete','cancelled') AND COALESCE(pt.planned_end, pt.end_date) < CURDATE() THEN 1 ELSE 0 END), 0) AS delayed_tasks,
                COALESCE(SUM(CASE WHEN pt.critical_path = 1 THEN 1 ELSE 0 END), 0) AS critical,
                COALESCE(AVG(pt.pct_complete), 0) AS avg_progress
             FROM programme_tasks pt
             JOIN projects p ON p.id = pt.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numericSummary($row, ['total', 'pending', 'approved', 'needs_revision', 'delayed_tasks', 'critical', 'avg_progress']);
    }

    public static function programmeTasks(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::programmeFilterSql($userId, $role, $filters);
        return Database::fetchAll(
            "SELECT pt.*, p.name AS project_name, c.name AS constituency_name,
                    COALESCE(CONCAT(a.first_name, ' ', a.last_name), '') AS assignee_name,
                    COALESCE(CONCAT(rv.first_name, ' ', rv.last_name), '') AS reviewed_by_name
             FROM programme_tasks pt
             JOIN projects p ON p.id = pt.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN users a ON a.id = pt.assigned_to
             LEFT JOIN users rv ON rv.id = pt.consultant_reviewed_by
             {$where}
             ORDER BY FIELD(COALESCE(pt.consultant_review_status, 'pending'), 'pending', 'needs-revision', 'flagged', 'approved', 'reviewed'),
                      COALESCE(pt.planned_end, pt.end_date, pt.planned_start, pt.start_date) ASC,
                      p.name ASC, pt.sort_order ASC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function programmeCount(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::programmeFilterSql($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM programme_tasks pt JOIN projects p ON p.id = pt.project_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Delayed / critical / pending-review signals across the portfolio (not page-bound). */
    public static function programmeSignalItems(int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        [$where, $bindings] = self::programmeFilterSql($userId, $role, $filters);
        $signal = "(pt.critical_path = 1
            OR COALESCE(pt.consultant_review_status, 'pending') IN ('pending', 'flagged', 'needs-revision')
            OR (pt.status NOT IN ('complete','cancelled') AND COALESCE(pt.planned_end, pt.end_date) IS NOT NULL AND COALESCE(pt.planned_end, pt.end_date) < CURDATE()))";
        $where = $where === '' ? " WHERE {$signal}" : $where . " AND {$signal}";

        return Database::fetchAll(
            "SELECT pt.id, pt.task_name, pt.pct_complete, pt.status, pt.critical_path, pt.planned_end, pt.end_date,
                    pt.consultant_review_status, p.id AS project_id, p.name AS project_name
             FROM programme_tasks pt
             JOIN projects p ON p.id = pt.project_id
             {$where}
             ORDER BY FIELD(COALESCE(pt.consultant_review_status, 'pending'), 'flagged', 'needs-revision', 'pending', 'approved', 'reviewed'),
                      (pt.status NOT IN ('complete','cancelled') AND COALESCE(pt.planned_end, pt.end_date) < CURDATE()) DESC,
                      pt.critical_path DESC,
                      COALESCE(pt.planned_end, pt.end_date) ASC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function materialSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::materialFilterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN ma.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                COALESCE(SUM(CASE WHEN ma.status = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
                COALESCE(SUM(CASE WHEN ma.status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected,
                COALESCE(SUM(CASE WHEN ma.submitted_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END), 0) AS new_week
             FROM material_approvals ma
             JOIN projects p ON p.id = ma.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numericSummary($row, ['total', 'pending', 'approved', 'rejected', 'new_week']);
    }

    public static function materialItems(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::materialFilterSql($userId, $role, $filters);
        return Database::fetchAll(
            "SELECT ma.*, p.name AS project_name, c.name AS constituency_name,
                    COALESCE(CONCAT(sb.first_name, ' ', sb.last_name), '') AS submitted_by_name,
                    COALESCE(CONCAT(ap.first_name, ' ', ap.last_name), '') AS approved_by_name
             FROM material_approvals ma
             JOIN projects p ON p.id = ma.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN users sb ON sb.id = ma.submitted_by
             LEFT JOIN users ap ON ap.id = ma.approved_by
             {$where}
             ORDER BY FIELD(ma.status, 'pending', 'rejected', 'approved'), ma.submitted_date DESC, ma.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function materialCount(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::materialFilterSql($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM material_approvals ma JOIN projects p ON p.id = ma.project_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Pending materials across portfolio (not page-bound). */
    public static function materialPendingItems(int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        $queueFilters = $filters;
        $queueFilters['status'] = 'pending';
        [$where, $bindings] = self::materialFilterSql($userId, $role, $queueFilters);

        return Database::fetchAll(
            "SELECT ma.id, ma.material, ma.submitted_date, ma.status, p.id AS project_id, p.name AS project_name
             FROM material_approvals ma
             JOIN projects p ON p.id = ma.project_id
             {$where}
             ORDER BY ma.submitted_date DESC, ma.id DESC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function drawingSummary(int $userId, string $role, array $filters = []): array
    {
        [$where, $bindings] = self::drawingFilterSql($userId, $role, $filters, false);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN sd.status = 'under-review' THEN 1 ELSE 0 END), 0) AS under_review,
                COALESCE(SUM(CASE WHEN sd.status = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
                COALESCE(SUM(CASE WHEN sd.status = 'resubmit' THEN 1 ELSE 0 END), 0) AS resubmit,
                COALESCE(SUM(CASE WHEN sd.status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected
             FROM shop_drawings sd
             JOIN projects p ON p.id = sd.project_id
             {$where}",
            $bindings
        ) ?: [];

        return self::numericSummary($row, ['total', 'under_review', 'approved', 'resubmit', 'rejected']);
    }

    public static function drawingItems(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::drawingFilterSql($userId, $role, $filters);
        return Database::fetchAll(
            "SELECT sd.*, p.name AS project_name, c.name AS constituency_name,
                    COALESCE(CONCAT(sb.first_name, ' ', sb.last_name), '') AS submitted_by_name,
                    COALESCE(CONCAT(rv.first_name, ' ', rv.last_name), '') AS reviewed_by_name
             FROM shop_drawings sd
             JOIN projects p ON p.id = sd.project_id
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN users sb ON sb.id = sd.submitted_by
             LEFT JOIN users rv ON rv.id = sd.reviewed_by
             {$where}
             ORDER BY FIELD(sd.status, 'under-review', 'resubmit', 'rejected', 'approved'), sd.submitted_date DESC, sd.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function drawingCount(int $userId, string $role, array $filters = []): int
    {
        [$where, $bindings] = self::drawingFilterSql($userId, $role, $filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM shop_drawings sd JOIN projects p ON p.id = sd.project_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    /** Drawings needing action across portfolio (not page-bound). */
    public static function drawingQueueItems(int $userId, string $role, array $filters = [], int $limit = 8): array
    {
        $queueFilters = $filters;
        unset($queueFilters['status']);
        [$where, $bindings] = self::drawingFilterSql($userId, $role, $queueFilters);
        $signal = "sd.status IN ('under-review', 'resubmit')";
        $where = $where === '' ? " WHERE {$signal}" : $where . " AND {$signal}";

        return Database::fetchAll(
            "SELECT sd.id, sd.drawing_no, sd.title, sd.revision, sd.status, sd.submitted_date,
                    p.id AS project_id, p.name AS project_name
             FROM shop_drawings sd
             JOIN projects p ON p.id = sd.project_id
             {$where}
             ORDER BY FIELD(sd.status, 'under-review', 'resubmit', 'rejected', 'approved'),
                      sd.submitted_date DESC, sd.id DESC
             LIMIT " . max(1, min(20, $limit)),
            $bindings
        );
    }

    public static function applyAction(string $type, int $id, string $action, string $note, int $userId, string $role): array
    {
        $type = strtolower($type);
        $action = strtolower($action);
        $note = trim($note);

        return match ($type) {
            'boq' => self::applyBoqAction($id, $action, $note, $userId, $role),
            'programme' => self::applyProgrammeAction($id, $action, $note, $userId, $role),
            'material' => self::applyMaterialAction($id, $action, $note, $userId, $role),
            'drawing' => self::applyDrawingAction($id, $action, $note, $userId, $role),
            default => throw new InvalidArgumentException('Unknown review type.'),
        };
    }

    public static function statusClass(string $status): string
    {
        return match ($status) {
            'approved', 'reviewed', 'complete' => 'badge--success',
            'rejected', 'needs-revision', 'needs-review', 'resubmit', 'delayed', 'flagged', 'escalated' => 'badge--danger',
            'under-review', 'pending', 'not_started', 'in_progress' => 'badge--warning',
            default => 'badge--info',
        };
    }

    private static function applyBoqAction(int $id, string $action, string $note, int $userId, string $role): array
    {
        $item = Database::fetch('SELECT bi.*, p.name AS project_name FROM boq_items bi JOIN projects p ON p.id = bi.project_id WHERE bi.id = ? LIMIT 1', [$id]);
        if (!$item || !self::canAccessProject($userId, $role, (int)$item['project_id'])) {
            throw new RuntimeException('BOQ item could not be found.');
        }

        $review = match ($action) {
            'approve', 'review' => 'reviewed',
            'return' => 'needs-revision',
            'flag' => 'escalated',
            default => throw new InvalidArgumentException('Unsupported BOQ action.'),
        };
        $risk = match ($action) {
            'approve', 'review' => 'normal',
            'return' => 'watch',
            'flag' => 'high',
            default => 'normal',
        };
        if ($action !== 'approve' && $action !== 'review' && $note === '') {
            throw new InvalidArgumentException('A review note is required.');
        }

        Database::query(
            'UPDATE boq_items SET review_status = ?, risk_status = ?, manager_note = ?, last_reviewed_by = ?, last_reviewed_at = NOW(), updated_by = ? WHERE id = ?',
            [$review, $risk, $note !== '' ? $note : null, $userId, $userId, $id]
        );
        ManagerBOQ::recordReviewUpdate($id, (int)$item['project_id'], $userId, $item, [
            'certified_qty' => $item['certified_qty'],
            'paid_qty' => $item['paid_qty'],
            'review_status' => $review,
            'risk_status' => $risk,
        ], $note);
        self::afterAction('boq_review', 'boq_items', $id, $userId, $item['project_name'], $action, $note, 'admin/manager/boq.php');

        return ['message' => 'BOQ review saved.'];
    }

    private static function applyProgrammeAction(int $id, string $action, string $note, int $userId, string $role): array
    {
        $task = Database::fetch('SELECT pt.*, p.name AS project_name FROM programme_tasks pt JOIN projects p ON p.id = pt.project_id WHERE pt.id = ? LIMIT 1', [$id]);
        if (!$task || !self::canAccessProject($userId, $role, (int)$task['project_id'])) {
            throw new RuntimeException('Programme task could not be found.');
        }

        $status = match ($action) {
            'approve', 'review' => 'approved',
            'return' => 'needs-revision',
            'flag' => 'flagged',
            default => throw new InvalidArgumentException('Unsupported programme action.'),
        };
        if ($status !== 'approved' && $note === '') {
            throw new InvalidArgumentException('A review note is required.');
        }

        Database::query(
            'UPDATE programme_tasks SET consultant_review_status = ?, consultant_review_note = ?, consultant_reviewed_by = ?, consultant_reviewed_at = NOW(), updated_by = ? WHERE id = ?',
            [$status, $note !== '' ? $note : null, $userId, $userId, $id]
        );
        self::afterAction('programme_review', 'programme_tasks', $id, $userId, $task['project_name'], $action, $note, 'admin/manager/programme-of-works.php');

        return ['message' => 'Programme review saved.'];
    }

    private static function applyMaterialAction(int $id, string $action, string $note, int $userId, string $role): array
    {
        $item = Database::fetch('SELECT ma.*, p.name AS project_name FROM material_approvals ma JOIN projects p ON p.id = ma.project_id WHERE ma.id = ? LIMIT 1', [$id]);
        if (!$item || !self::canAccessProject($userId, $role, (int)$item['project_id'])) {
            throw new RuntimeException('Material submission could not be found.');
        }

        $status = match ($action) {
            'approve', 'review' => 'approved',
            'return', 'reject' => 'rejected',
            default => throw new InvalidArgumentException('Unsupported material action.'),
        };
        if ($status === 'rejected' && $note === '') {
            throw new InvalidArgumentException('A review note is required.');
        }

        Database::query(
            'UPDATE material_approvals SET status = ?, approved_by = ?, approved_date = CURDATE(), notes = ? WHERE id = ?',
            [$status, $userId, $note !== '' ? $note : ($item['notes'] ?? null), $id]
        );
        self::afterAction('material_review', 'material_approvals', $id, $userId, $item['project_name'], $action, $note, 'admin/manager/site-meeting-minutes.php');

        return ['message' => 'Material review saved.'];
    }

    private static function applyDrawingAction(int $id, string $action, string $note, int $userId, string $role): array
    {
        $drawing = Database::fetch('SELECT sd.*, p.name AS project_name FROM shop_drawings sd JOIN projects p ON p.id = sd.project_id WHERE sd.id = ? LIMIT 1', [$id]);
        if (!$drawing || !self::canAccessProject($userId, $role, (int)$drawing['project_id'])) {
            throw new RuntimeException('Shop drawing could not be found.');
        }

        $status = match ($action) {
            'approve', 'review' => 'approved',
            'return' => 'resubmit',
            'reject' => 'rejected',
            default => throw new InvalidArgumentException('Unsupported drawing action.'),
        };
        if ($status !== 'approved' && $note === '') {
            throw new InvalidArgumentException('A review note is required.');
        }

        Database::query(
            'UPDATE shop_drawings SET status = ?, reviewed_by = ?, review_date = CURDATE(), review_note = ? WHERE id = ?',
            [$status, $userId, $note !== '' ? $note : null, $id]
        );
        self::afterAction('drawing_review', 'shop_drawings', $id, $userId, $drawing['project_name'], $action, $note, 'admin/manager/site-meeting-minutes.php');

        return ['message' => 'Shop drawing review saved.'];
    }

    private static function afterAction(string $event, string $module, int $targetId, int $userId, string $projectName, string $action, string $note, string $managerLink): void
    {
        self::audit($event, $module, $targetId, ['project' => $projectName, 'action' => $action, 'note' => $note]);
        Notification::pushRole('manager', $event, 'Consultant review updated', 'A consultant review for ' . $projectName . ' has been updated.', $managerLink);
        Notification::pushRole('superadmin', $event, 'Consultant review updated', 'A consultant review for ' . $projectName . ' has been updated.', 'admin/superadmin/dashboard.php');
    }

    private static function canAccessProject(int $userId, string $role, int $projectId): bool
    {
        if (strtolower($role) === 'superadmin') {
            return Database::fetch('SELECT id FROM projects WHERE id = ? LIMIT 1', [$projectId]) !== null;
        }
        $project = Database::fetch('SELECT consultant_id FROM projects WHERE id = ? LIMIT 1', [$projectId]);
        if ($project && (int)($project['consultant_id'] ?? 0) === $userId) {
            return true;
        }
        return ProjectAssignment::canManageProject($userId, $projectId, $role);
    }

    private static function scopeSql(int $userId, string $role, string $projectAlias): array
    {
        if (strtolower($role) === 'superadmin') {
            return ['', []];
        }
        return [
            "({$projectAlias}.consultant_id = ? OR EXISTS (
                SELECT 1 FROM project_assignments cpa
                WHERE cpa.project_id = {$projectAlias}.id
                  AND cpa.user_id = ?
                  AND cpa.status = 'active'
            ))",
            [$userId, $userId],
        ];
    }

    private static function boqFilterSql(int $userId, string $role, array $filters, bool $allowRisk = true): array
    {
        [$where, $bindings] = self::baseFilters($userId, $role, $filters, 'p');
        if (!empty($filters['status'])) {
            $where[] = 'COALESCE(bi.review_status, "pending") = ?';
            $bindings[] = (string)$filters['status'];
        }
        if (!empty($filters['risk']) && $allowRisk) {
            $where[] = 'COALESCE(bi.risk_status, "normal") = ?';
            $bindings[] = (string)$filters['risk'];
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(bi.item_no LIKE ? OR bi.description LIKE ? OR bi.section LIKE ? OR p.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function programmeFilterSql(int $userId, string $role, array $filters, bool $allowDate = true): array
    {
        [$where, $bindings] = self::baseFilters($userId, $role, $filters, 'p');
        if (!empty($filters['status'])) {
            $where[] = 'COALESCE(pt.consultant_review_status, "pending") = ?';
            $bindings[] = (string)$filters['status'];
        }
        if (!empty($filters['delay'])) {
            $where[] = match ((string)$filters['delay']) {
                'delayed' => "pt.status NOT IN ('complete','cancelled') AND COALESCE(pt.planned_end, pt.end_date) < CURDATE()",
                'critical' => 'pt.critical_path = 1',
                default => '1 = 1',
            };
        }
        if ($allowDate && !empty($filters['from'])) {
            $where[] = 'COALESCE(pt.planned_end, pt.end_date) >= ?';
            $bindings[] = (string)$filters['from'];
        }
        if ($allowDate && !empty($filters['to'])) {
            $where[] = 'COALESCE(pt.planned_start, pt.start_date) <= ?';
            $bindings[] = (string)$filters['to'];
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(pt.task_name LIKE ? OR pt.notes LIKE ? OR p.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function materialFilterSql(int $userId, string $role, array $filters, bool $allowDate = true): array
    {
        [$where, $bindings] = self::baseFilters($userId, $role, $filters, 'p');
        if (!empty($filters['status'])) {
            $where[] = 'ma.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        if ($allowDate && !empty($filters['from'])) {
            $where[] = 'ma.submitted_date >= ?';
            $bindings[] = (string)$filters['from'];
        }
        if ($allowDate && !empty($filters['to'])) {
            $where[] = 'ma.submitted_date <= ?';
            $bindings[] = (string)$filters['to'];
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(ma.material LIKE ? OR ma.specification LIKE ? OR ma.notes LIKE ? OR p.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function drawingFilterSql(int $userId, string $role, array $filters, bool $allowDate = true): array
    {
        [$where, $bindings] = self::baseFilters($userId, $role, $filters, 'p');
        if (!empty($filters['status'])) {
            $where[] = 'sd.status = ?';
            $bindings[] = (string)$filters['status'];
        }
        if ($allowDate && !empty($filters['from'])) {
            $where[] = 'sd.submitted_date >= ?';
            $bindings[] = (string)$filters['from'];
        }
        if ($allowDate && !empty($filters['to'])) {
            $where[] = 'sd.submitted_date <= ?';
            $bindings[] = (string)$filters['to'];
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(sd.drawing_no LIKE ? OR sd.title LIKE ? OR sd.revision LIKE ? OR p.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term);
        }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function baseFilters(int $userId, string $role, array $filters, string $projectAlias): array
    {
        [$scopeSql, $scopeBindings] = self::scopeSql($userId, $role, $projectAlias);
        $where = [];
        $bindings = [];
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            array_push($bindings, ...$scopeBindings);
        }
        if (!empty($filters['project_id'])) {
            $where[] = "{$projectAlias}.id = ?";
            $bindings[] = (int)$filters['project_id'];
        }
        return [$where, $bindings];
    }

    private static function numericSummary(array $row, array $keys): array
    {
        foreach ($keys as $key) {
            $row[$key] = isset($row[$key]) && is_numeric($row[$key]) ? (float)$row[$key] : 0;
        }
        return $row;
    }

    private static function audit(string $action, string $module, int $targetId, array $details): void
    {
        try {
            Database::query(
                'INSERT INTO audit_logs (user_id, action, module, target_id, details_json, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    Auth::id(),
                    $action,
                    $module,
                    $targetId,
                    json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                ]
            );
        } catch (Throwable) {
        }
    }
}
