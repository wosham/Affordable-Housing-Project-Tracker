<?php

class ContractorIPC
{
    public const RETENTION_RATE = 0.05;
    private static array $tableColumns = [];
    private static array $tableExists = [];

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

        $stats = self::emptyStats();
        foreach ($row as $key => $value) {
            $stats[$key] = is_numeric($value) ? (float)$value : $value;
        }

        return $stats;
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

    public static function claimDetail(int $ipcId, int $userId, string $role): ?array
    {
        $ipc = IPC::findDetailed($ipcId);
        if (!$ipc) {
            return null;
        }
        if ((int)($ipc['contractor_id'] ?? 0) !== $userId && strtolower($role) !== 'superadmin') {
            return null;
        }
        if (!self::canAccess($userId, $role, (int)$ipc['project_id'])) {
            return null;
        }

        $lines = self::claimLines($ipcId);
        $payment = null;
        try {
            $payment = Database::fetch(
                "SELECT p.*
                 FROM payments p
                 WHERE p.ipc_id = ? AND p.status = 'processed'
                 ORDER BY p.payment_date DESC, p.id DESC
                 LIMIT 1",
                [$ipcId]
            ) ?: null;
        } catch (Throwable) {
            $payment = null;
        }

        $state = IPC::workflowState($ipc);
        return [
            'ipc' => $ipc,
            'lines' => $lines,
            'payment' => $payment,
            'workflow' => $state,
            'approvals' => self::approvals($ipcId),
        ];
    }

    public static function claimLines(int $ipcId): array
    {
        try {
            return Database::fetchAll(
                "SELECT il.*, b.item_no, b.section, b.unit
                 FROM ipc_lines il
                 LEFT JOIN boq_items b ON b.id = il.boq_item_id
                 WHERE il.ipc_id = ?
                 ORDER BY il.id ASC",
                [$ipcId]
            );
        } catch (Throwable) {
            return [];
        }
    }

    public static function paymentRows(int $userId, string $role, array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $projectIds = ContractorProject::projectIds($userId, $role);
        if ($projectIds === []) {
            return [];
        }
        [$in, $bindings] = self::inClause($projectIds);
        $where = ["i.contractor_id = ?", "i.project_id IN ({$in})"];
        $bindings = array_merge([$userId], $bindings);

        $projectId = (int)($filters['project_id'] ?? 0);
        if ($projectId > 0) {
            $where[] = 'i.project_id = ?';
            $bindings[] = $projectId;
        }
        $status = trim((string)($filters['status'] ?? ''));
        if ($status === 'paid') {
            $where[] = 'p.id IS NOT NULL';
        } elseif ($status === 'unpaid') {
            $where[] = "i.status = 'approved' AND p.id IS NULL";
        } elseif ($status === 'approved') {
            $where[] = "i.status = 'approved'";
        } elseif ($status !== '' && in_array($status, IPC::statuses(), true)) {
            $where[] = 'i.status = ?';
            $bindings[] = $status;
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(CAST(i.ipc_number AS CHAR) LIKE ? OR i.contractor_reference LIKE ? OR COALESCE(p.reference_no, "") LIKE ? OR pr.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term);
        }

        try {
            return Database::fetchAll(
                "SELECT i.id, i.ipc_number, i.contractor_reference, i.status, i.gross_amount, i.retention_amount, i.net_amount,
                        i.project_id, i.approved_at, i.submitted_at, i.updated_at,
                        pr.name AS project_name,
                        p.amount, p.payment_date, p.reference_no, p.bank, p.receipt_path, p.payment_method, p.notes AS payment_notes
                 FROM ipcs i
                 JOIN projects pr ON pr.id = i.project_id
                 LEFT JOIN payments p ON p.ipc_id = i.id AND p.status = 'processed'
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY COALESCE(p.payment_date, i.approved_at, i.updated_at) DESC, i.id DESC
                 LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
                $bindings
            );
        } catch (Throwable) {
            return [];
        }
    }

    public static function paymentCount(int $userId, string $role, array $filters = []): int
    {
        $projectIds = ContractorProject::projectIds($userId, $role);
        if ($projectIds === []) {
            return 0;
        }
        [$in, $bindings] = self::inClause($projectIds);
        $where = ["i.contractor_id = ?", "i.project_id IN ({$in})"];
        $bindings = array_merge([$userId], $bindings);
        $projectId = (int)($filters['project_id'] ?? 0);
        if ($projectId > 0) {
            $where[] = 'i.project_id = ?';
            $bindings[] = $projectId;
        }
        $status = trim((string)($filters['status'] ?? ''));
        if ($status === 'paid') {
            $where[] = 'p.id IS NOT NULL';
        } elseif ($status === 'unpaid') {
            $where[] = "i.status = 'approved' AND p.id IS NULL";
        } elseif ($status === 'approved') {
            $where[] = "i.status = 'approved'";
        }
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(CAST(i.ipc_number AS CHAR) LIKE ? OR i.contractor_reference LIKE ? OR p.reference_no LIKE ? OR pr.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term);
        }
        try {
            $row = Database::fetch(
                "SELECT COUNT(DISTINCT i.id) AS total
                 FROM ipcs i
                 JOIN projects pr ON pr.id = i.project_id
                 LEFT JOIN payments p ON p.ipc_id = i.id AND p.status = 'processed'
                 WHERE " . implode(' AND ', $where),
                $bindings
            );
            return (int)($row['total'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    public static function paymentSummary(int $userId, string $role, array $filters = []): array
    {
        $projectIds = ContractorProject::projectIds($userId, $role);
        if ($projectIds === []) {
            return ['total' => 0, 'paid_count' => 0, 'paid_value' => 0, 'unpaid_count' => 0, 'unpaid_value' => 0, 'retention' => 0];
        }
        [$in, $bindings] = self::inClause($projectIds);
        $where = ["i.contractor_id = ?", "i.project_id IN ({$in})"];
        $bindings = array_merge([$userId], $bindings);
        $projectId = (int)($filters['project_id'] ?? 0);
        if ($projectId > 0) {
            $where[] = 'i.project_id = ?';
            $bindings[] = $projectId;
        }
        try {
            $row = Database::fetch(
                "SELECT COUNT(DISTINCT i.id) AS total,
                        COALESCE(SUM(CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END), 0) AS paid_count,
                        COALESCE(SUM(CASE WHEN p.id IS NOT NULL THEN p.amount ELSE 0 END), 0) AS paid_value,
                        COALESCE(SUM(CASE WHEN i.status = 'approved' AND p.id IS NULL THEN 1 ELSE 0 END), 0) AS unpaid_count,
                        COALESCE(SUM(CASE WHEN i.status = 'approved' AND p.id IS NULL THEN i.net_amount ELSE 0 END), 0) AS unpaid_value,
                        COALESCE(SUM(i.retention_amount), 0) AS retention
                 FROM ipcs i
                 LEFT JOIN payments p ON p.ipc_id = i.id AND p.status = 'processed'
                 WHERE " . implode(' AND ', $where),
                $bindings
            ) ?: [];
            return [
                'total' => (int)($row['total'] ?? 0),
                'paid_count' => (int)($row['paid_count'] ?? 0),
                'paid_value' => (float)($row['paid_value'] ?? 0),
                'unpaid_count' => (int)($row['unpaid_count'] ?? 0),
                'unpaid_value' => (float)($row['unpaid_value'] ?? 0),
                'retention' => (float)($row['retention'] ?? 0),
            ];
        } catch (Throwable) {
            return ['total' => 0, 'paid_count' => 0, 'paid_value' => 0, 'unpaid_count' => 0, 'unpaid_value' => 0, 'retention' => 0];
        }
    }

    public static function approvals(int $ipcId): array
    {
        try {
            return Database::fetchAll(
                "SELECT ia.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS actor_name, r.name AS role_name
                 FROM ipc_approvals ia
                 JOIN users u ON u.id = ia.action_by
                 LEFT JOIN roles r ON r.id = u.role_id
                 WHERE ia.ipc_id = ?
                 ORDER BY ia.actioned_at ASC, ia.id ASC",
                [$ipcId]
            );
        } catch (Throwable) {
            return [];
        }
    }

    public static function attachments(int $ipcId): array
    {
        if ($ipcId <= 0 || !self::tableExists('ipc_attachments')) {
            return [];
        }

        $mediaTable = self::tableExists('media_library') ? 'media_library' : (self::tableExists('media_assets') ? 'media_assets' : null);
        if ($mediaTable === null) {
            return Database::fetchAll(
                'SELECT * FROM ipc_attachments WHERE ipc_id = ? ORDER BY id ASC',
                [$ipcId]
            );
        }

        return Database::fetchAll(
            "SELECT ia.*, m.title AS media_title, m.path AS media_path, m.url AS media_url
             FROM ipc_attachments ia
             LEFT JOIN {$mediaTable} m ON m.id = ia.media_id
             WHERE ia.ipc_id = ?
             ORDER BY ia.id ASC",
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
            $ipcData = self::filterColumns('ipcs', [
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

            $ipcId = (int)IPC::create($ipcData);

            foreach ($lines as $line) {
                IPCLine::create(self::filterColumns('ipc_lines', [
                    'ipc_id' => $ipcId,
                    'boq_item_id' => (int)$line['boq_item_id'],
                    'description' => (string)$line['description'],
                    'qty_this_period' => (float)$line['qty_this_period'],
                    'cumulative_qty' => (float)$line['cumulative_qty'],
                    'rate' => (float)$line['rate'],
                    'amount' => (float)$line['amount'],
                ]));
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

    private static function tableExists(string $table): bool
    {
        if (array_key_exists($table, self::$tableExists)) {
            return self::$tableExists[$table];
        }

        try {
            $row = Database::fetch(
                'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$table]
            );
            self::$tableExists[$table] = (int)($row['total'] ?? 0) > 0;
        } catch (Throwable) {
            self::$tableExists[$table] = false;
        }

        return self::$tableExists[$table];
    }

    private static function columns(string $table): array
    {
        if (isset(self::$tableColumns[$table])) {
            return self::$tableColumns[$table];
        }

        try {
            $rows = Database::fetchAll(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?',
                [$table]
            );
            self::$tableColumns[$table] = array_fill_keys(array_column($rows, 'column_name'), true);
        } catch (Throwable) {
            self::$tableColumns[$table] = [];
        }

        return self::$tableColumns[$table];
    }

    private static function filterColumns(string $table, array $data): array
    {
        $columns = self::columns($table);
        if ($columns === []) {
            return $data;
        }

        return array_intersect_key($data, $columns);
    }
}
