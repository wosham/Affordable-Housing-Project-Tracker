<?php

class IPC extends Model
{
    protected static string $table = 'ipcs';

    public const STATUSES = ['draft', 'submitted', 'clerk-endorsed', 'certified', 'endorsed', 'approved', 'rejected', 'paid'];
    public const APPROVABLE_STATUSES = ['endorsed', 'certified'];

    public static function detailed(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY i.updated_at DESC, i.id DESC' . $limitSql, $bindings);
    }

    public static function countDetailed(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch("
            SELECT COUNT(*) AS aggregate
            FROM ipcs i
            INNER JOIN projects p ON p.id = i.project_id
            INNER JOIN users contractor ON contractor.id = i.contractor_id
            {$where}
        ", $bindings);

        return (int)($row['aggregate'] ?? 0);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE i.id = ? LIMIT 1', [$id]);
    }

    public static function forProject(int $projectId): array
    {
        return self::detailed(['project_id' => $projectId]);
    }

    public static function pending(): array
    {
        return self::detailed(['not_status' => 'paid']);
    }

    public static function stats(): array
    {
        return Database::fetch("
            SELECT
                COUNT(*) AS total_ipcs,
                SUM(CASE WHEN status = 'endorsed' THEN 1 ELSE 0 END) AS awaiting_final,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_unpaid,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_ipcs,
                COALESCE(SUM(CASE WHEN status = 'endorsed' THEN net_amount ELSE 0 END), 0) AS awaiting_value,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN net_amount ELSE 0 END), 0) AS approved_value
            FROM ipcs
        ") ?: [];
    }

    public static function centreStats(array $filters = []): array
    {
        [$where, $bindings] = self::filterSql($filters);

        return Database::fetch("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN i.status = 'draft' THEN 1 ELSE 0 END), 0) AS draft,
                COALESCE(SUM(CASE WHEN i.status = 'submitted' THEN 1 ELSE 0 END), 0) AS submitted,
                COALESCE(SUM(CASE WHEN i.status = 'clerk-endorsed' THEN 1 ELSE 0 END), 0) AS clerk_endorsed,
                COALESCE(SUM(CASE WHEN i.status = 'certified' THEN 1 ELSE 0 END), 0) AS certified,
                COALESCE(SUM(CASE WHEN i.status = 'endorsed' THEN 1 ELSE 0 END), 0) AS endorsed,
                COALESCE(SUM(CASE WHEN i.status = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN 1 ELSE 0 END), 0) AS paid,
                COALESCE(SUM(CASE WHEN i.status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected,
                COALESCE(SUM(i.gross_amount), 0) AS gross_value,
                COALESCE(SUM(i.retention_amount), 0) AS retention_value,
                COALESCE(SUM(i.net_amount), 0) AS net_value,
                COALESCE(SUM(CASE WHEN i.status = 'approved' THEN i.net_amount ELSE 0 END), 0) AS approved_unpaid_value,
                COALESCE(SUM(CASE WHEN i.status IN ('certified','endorsed') THEN i.net_amount ELSE 0 END), 0) AS approval_queue_value
            FROM ipcs i
            INNER JOIN projects p ON p.id = i.project_id
            INNER JOIN users contractor ON contractor.id = i.contractor_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            {$where}
        ", $bindings) ?: [];
    }

    public static function centreList(array $filters = [], int $limit = 15, int $offset = 0): array
    {
        return self::detailed($filters, $limit, $offset);
    }

    public static function centreCount(array $filters = []): int
    {
        return self::countDetailed($filters);
    }

    public static function statuses(): array
    {
        return self::STATUSES;
    }

    public static function statusFlow(): array
    {
        return [
            'submitted' => ['label' => 'Submitted', 'step' => 1],
            'clerk-endorsed' => ['label' => 'Clerk Verified', 'step' => 2],
            'certified' => ['label' => 'Consultant Certified', 'step' => 3],
            'endorsed' => ['label' => 'Manager Endorsed', 'step' => 4],
            'approved' => ['label' => 'Director Approved', 'step' => 5],
            'paid' => ['label' => 'Finance Paid', 'step' => 6],
        ];
    }

    public static function workflowState(array $ipc): array
    {
        $status = (string)($ipc['status'] ?? 'draft');
        $stepByStatus = [
            'draft' => 0,
            'submitted' => 1,
            'clerk-endorsed' => 2,
            'certified' => 3,
            'endorsed' => 4,
            'approved' => 5,
            'paid' => 6,
            'rejected' => -1,
        ];

        return [
            'status' => $status,
            'current_step' => $stepByStatus[$status] ?? 0,
            'is_terminal' => in_array($status, ['paid', 'rejected'], true),
            'can_approve' => self::canApprove($ipc),
            'can_reject' => self::canReject($ipc),
        ];
    }

    public static function canApprove(array $ipc): bool
    {
        return in_array((string)($ipc['status'] ?? ''), self::APPROVABLE_STATUSES, true);
    }

    public static function canReject(array $ipc): bool
    {
        return !in_array((string)($ipc['status'] ?? ''), ['paid', 'rejected'], true);
    }

    public static function lineTotals(int $ipcId): array
    {
        return IPCLine::totalsForIPC($ipcId);
    }

    public static function warnings(array $ipc): array
    {
        $warnings = [];
        $ipcId = (int)($ipc['id'] ?? 0);
        $lineTotals = $ipcId > 0 ? self::lineTotals($ipcId) : [];
        $lineTotal = (float)($lineTotals['total_amount'] ?? 0);
        $gross = (float)($ipc['gross_amount'] ?? 0);

        if ((int)($lineTotals['line_count'] ?? 0) === 0) {
            $warnings[] = 'No IPC line items are recorded.';
        }

        if ($lineTotal > 0 && abs($lineTotal - $gross) > 1) {
            $warnings[] = 'Line item total does not match the IPC gross amount.';
        }

        if ((float)($ipc['net_amount'] ?? 0) <= 0 && !in_array((string)($ipc['status'] ?? ''), ['draft', 'rejected'], true)) {
            $warnings[] = 'Net payable amount is zero or missing.';
        }

        if ((string)($ipc['status'] ?? '') === 'approved' && empty($ipc['approved_at'])) {
            $warnings[] = 'IPC is approved but approval date is missing.';
        }

        if ((string)($ipc['status'] ?? '') === 'rejected' && empty($ipc['rejection_reason'] ?? '')) {
            $warnings[] = 'IPC is rejected but no rejection reason is stored.';
        }

        return $warnings;
    }

    public static function lineItems(int $ipcId): array
    {
        return IPCLine::forIPC($ipcId);
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                i.*,
                p.name AS project_name,
                p.slug AS project_slug,
                p.contract_sum,
                p.pct_complete,
                p.status AS project_status,
                CONCAT(contractor.first_name, ' ', contractor.last_name) AS contractor_name,
                contractor.email AS contractor_email,
                contractor.avatar AS contractor_avatar,
                c.name AS constituency_name,
                w.name AS ward_name,
                latest.action AS last_action,
                latest.comments AS last_comment,
                latest.actioned_at AS last_actioned_at,
                CONCAT(actor.first_name, ' ', actor.last_name) AS last_action_by,
                approver.email AS approved_by_email,
                CONCAT(approver.first_name, ' ', approver.last_name) AS approved_by_name,
                rejector.email AS rejected_by_email,
                CONCAT(rejector.first_name, ' ', rejector.last_name) AS rejected_by_name
            FROM ipcs i
            INNER JOIN projects p ON p.id = i.project_id
            INNER JOIN users contractor ON contractor.id = i.contractor_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            LEFT JOIN ipc_approvals latest ON latest.id = (
                SELECT ia.id FROM ipc_approvals ia
                WHERE ia.ipc_id = i.id
                ORDER BY ia.actioned_at DESC, ia.id DESC
                LIMIT 1
            )
            LEFT JOIN users actor ON actor.id = latest.action_by
            LEFT JOIN users approver ON approver.id = i.approved_by
            LEFT JOIN users rejector ON rejector.id = i.rejected_by
        ";
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 'i.status = ?';
            $bindings[] = $filters['status'];
        }

        if (!empty($filters['not_status'])) {
            $where[] = 'i.status <> ?';
            $bindings[] = $filters['not_status'];
        }

        if (!empty($filters['project_id'])) {
            $where[] = 'i.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        if (!empty($filters['contractor_id'])) {
            $where[] = 'i.contractor_id = ?';
            $bindings[] = (int)$filters['contractor_id'];
        }

        if (!empty($filters['constituency_id'])) {
            $where[] = 'p.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(p.name LIKE ? OR contractor.first_name LIKE ? OR contractor.last_name LIKE ? OR contractor.email LIKE ? OR CAST(i.ipc_number AS CHAR) LIKE ?)";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term);
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(i.submitted_at) >= ?';
            $bindings[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(i.submitted_at) <= ?';
            $bindings[] = $filters['date_to'];
        }

        if (!empty($filters['amount_min'])) {
            $where[] = 'i.net_amount >= ?';
            $bindings[] = (float)$filters['amount_min'];
        }

        if (!empty($filters['amount_max'])) {
            $where[] = 'i.net_amount <= ?';
            $bindings[] = (float)$filters['amount_max'];
        }

        if (!empty($filters['payment_readiness'])) {
            if ($filters['payment_readiness'] === 'ready') {
                $where[] = "i.status = 'approved'";
            } elseif ($filters['payment_readiness'] === 'paid') {
                $where[] = "i.status = 'paid'";
            } elseif ($filters['payment_readiness'] === 'blocked') {
                $where[] = "i.status IN ('rejected','draft','submitted','clerk-endorsed','certified','endorsed')";
            }
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
