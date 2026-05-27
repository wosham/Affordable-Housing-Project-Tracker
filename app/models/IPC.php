<?php

class IPC extends Model
{
    protected static string $table = 'ipcs';

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

    public static function lineItems(int $ipcId): array
    {
        return Database::fetchAll("
            SELECT il.*, bi.item_no AS item_code, bi.unit
            FROM ipc_lines il
            LEFT JOIN boq_items bi ON bi.id = il.boq_item_id
            WHERE il.ipc_id = ?
            ORDER BY il.id ASC
        ", [$ipcId]);
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
                CONCAT(actor.first_name, ' ', actor.last_name) AS last_action_by
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

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
