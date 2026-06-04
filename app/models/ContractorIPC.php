<?php

class ContractorIPC
{
    public const RETENTION_RATE = 0.05;

    public static function projects(int $userId, string $role): array
    {
        return ContractorProject::projects($userId, $role);
    }

    public static function defaultProjectId(int $userId, string $role, int $requestedId = 0): int
    {
        return ContractorProject::defaultProjectId($userId, $role, $requestedId);
    }

    public static function canAccess(int $userId, string $role, int $projectId): bool
    {
        return ContractorProject::canAccess($userId, $role, $projectId);
    }

    public static function projectSummary(int $projectId, int $userId, string $role): array
    {
        if (!self::canAccess($userId, $role, $projectId)) {
            return [];
        }

        $project = ContractorProject::detail($projectId, $userId, $role) ?: [];
        $boq = Database::fetch(
            "SELECT
                COUNT(*) AS boq_items,
                COALESCE(SUM(COALESCE(NULLIF(amount, 0), quantity * rate)), 0) AS boq_value,
                COALESCE(SUM(certified_qty * rate), 0) AS certified_value,
                COALESCE(SUM(paid_qty * rate), 0) AS paid_value
             FROM boq_items
             WHERE project_id = ?",
            [$projectId]
        ) ?: [];
        $ipc = Database::fetch(
            "SELECT
                COUNT(*) AS total_ipcs,
                COALESCE(MAX(ipc_number), 0) + 1 AS next_ipc_number,
                COALESCE(SUM(CASE WHEN status NOT IN ('rejected') THEN gross_amount ELSE 0 END), 0) AS submitted_value,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END), 0) AS paid_value
             FROM ipcs
             WHERE project_id = ? AND contractor_id = ?",
            [$projectId, $userId]
        ) ?: [];

        return [
            'project' => $project,
            'boq_items' => (int)($boq['boq_items'] ?? 0),
            'boq_value' => (float)($boq['boq_value'] ?? 0),
            'certified_value' => (float)($boq['certified_value'] ?? 0),
            'paid_value' => (float)($boq['paid_value'] ?? $ipc['paid_value'] ?? 0),
            'submitted_value' => (float)($ipc['submitted_value'] ?? 0),
            'next_ipc_number' => max(1, (int)($ipc['next_ipc_number'] ?? 1)),
            'total_ipcs' => (int)($ipc['total_ipcs'] ?? 0),
        ];
    }

    public static function boqLines(int $projectId, int $userId, string $role): array
    {
        if (!self::canAccess($userId, $role, $projectId)) {
            return [];
        }

        return Database::fetchAll(
            "SELECT
                b.*,
                COALESCE((
                    SELECT SUM(il.qty_this_period)
                    FROM ipc_lines il
                    JOIN ipcs i ON i.id = il.ipc_id
                    WHERE il.boq_item_id = b.id
                      AND i.status NOT IN ('draft','rejected')
                ), 0) AS previously_claimed_qty
             FROM boq_items b
             WHERE b.project_id = ? AND COALESCE(b.status, 'active') <> 'inactive'
             ORDER BY b.section ASC, b.item_no ASC, b.id ASC",
            [$projectId]
        );
    }

    public static function stats(int $userId, string $role): array
    {
        $projectIds = ContractorProject::projectIds($userId, $role);
        if ($projectIds === []) {
            return self::emptyStats();
        }

        [$in, $bindings] = self::inClause($projectIds);
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN i.status = 'submitted' THEN 1 ELSE 0 END), 0) AS submitted,
                COALESCE(SUM(CASE WHEN i.status = 'clerk-endorsed' THEN 1 ELSE 0 END), 0) AS clerk_endorsed,
                COALESCE(SUM(CASE WHEN i.status = 'certified' THEN 1 ELSE 0 END), 0) AS certified,
                COALESCE(SUM(CASE WHEN i.status = 'endorsed' THEN 1 ELSE 0 END), 0) AS endorsed,
                COALESCE(SUM(CASE WHEN i.status = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN 1 ELSE 0 END), 0) AS paid,
                COALESCE(SUM(CASE WHEN i.status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected,
                COALESCE(SUM(i.gross_amount), 0) AS gross_value,
                COALESCE(SUM(i.net_amount), 0) AS net_value,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.net_amount ELSE 0 END), 0) AS paid_value
             FROM ipcs i
             WHERE i.contractor_id = ? AND i.project_id IN ({$in})",
            array_merge([$userId], $bindings)
        ) ?: [];

        return array_merge(self::emptyStats(), array_map(static fn ($value) => is_numeric($value) ? (float)$value : $value, $row));
    }

    public static function history(int $userId, string $role, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        [$where, $bindings] = self::historyWhere($userId, $role, $filters);
        if ($where === null) {
            return [];
        }

        return IPC::detailed(array_merge($filters, ['contractor_id' => $userId]), $limit, $offset);
    }

    public static function historyCount(int $userId, string $role, array $filters = []): int
    {
        if (ContractorProject::projectIds($userId, $role) === []) {
            return 0;
        }
        return IPC::countDetailed(array_merge($filters, ['contractor_id' => $userId]));
    }

    public static function approvals(int $ipcId): array
    {
        return Database::fetchAll(
            "SELECT ia.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS actor_name, r.name AS role_name
             FROM ipc_approvals ia
             JOIN users u ON u.id = ia.action_by
             LEFT JOIN user_role_assignments ura ON ura.user_id = u.id
             LEFT JOIN roles r ON r.id = ura.role_id
             WHERE ia.ipc_id = ?
             ORDER BY ia.actioned_at ASC, ia.id ASC",
            [$ipcId]
        );
    }

    public static function createSubmission(array $payload): int
    {
        $projectId = (int)$payload['project_id'];
        $contractorId = (int)$payload['contractor_id'];
        $ipcNumber = (int)$payload['ipc_number'];
        $gross = (float)$payload['gross_amount'];
        $retention = (float)$payload['retention_amount'];
        $net = (float)$payload['net_amount'];
        $lines = (array)$payload['lines'];

        Database::beginTransaction();
        try {
            $ipcId = (int)IPC::create([
                'project_id' => $projectId,
                'contractor_id' => $contractorId,
                'ipc_number' => $ipcNumber,
                'period_from' => $payload['period_from'],
                'period_to' => $payload['period_to'],
                'gross_amount' => $gross,
                'retention_amount' => $retention,
                'net_amount' => $net,
                'status' => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
                'contractor_reference' => $payload['contractor_reference'] ?: null,
                'declaration_accepted' => 1,
                'current_stage' => 'clerk_verification',
            ]);

            foreach ($lines as $line) {
                IPCLine::create([
                    'ipc_id' => $ipcId,
                    'boq_item_id' => (int)$line['boq_item_id'],
                    'description' => (string)$line['description'],
                    'qty_this_period' => (float)$line['qty_this_period'],
                    'cumulative_qty' => (float)$line['cumulative_qty'],
                    'rate' => (float)$line['rate'],
                    'amount' => (float)$line['amount'],
                ]);
            }

            Database::commit();
            return $ipcId;
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    private static function historyWhere(int $userId, string $role, array $filters): array
    {
        return [[], []];
    }

    private static function emptyStats(): array
    {
        return ['total' => 0, 'submitted' => 0, 'clerk_endorsed' => 0, 'certified' => 0, 'endorsed' => 0, 'approved' => 0, 'paid' => 0, 'rejected' => 0, 'gross_value' => 0, 'net_value' => 0, 'paid_value' => 0];
    }

    private static function inClause(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        return [implode(',', array_fill(0, count($ids), '?')), $ids];
    }
}
