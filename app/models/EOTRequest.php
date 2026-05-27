<?php

class EOTRequest extends Model
{
    protected static string $table = 'eot_requests';

    public static function detailed(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY e.created_at DESC, e.id DESC' . $limitSql, $bindings);
    }

    public static function countDetailed(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch("
            SELECT COUNT(*) AS aggregate
            FROM eot_requests e
            INNER JOIN projects p ON p.id = e.project_id
            INNER JOIN users submitter ON submitter.id = e.submitted_by
            {$where}
        ", $bindings);

        return (int)($row['aggregate'] ?? 0);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE e.id = ? LIMIT 1', [$id]);
    }

    public static function stats(): array
    {
        return Database::fetch("
            SELECT
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_eots,
                SUM(CASE WHEN status IN ('granted','partially-granted') THEN 1 ELSE 0 END) AS granted_eots,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_eots,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN days_requested ELSE 0 END), 0) AS pending_days,
                COALESCE(SUM(CASE WHEN status IN ('granted','partially-granted') THEN granted_days ELSE 0 END), 0) AS granted_days
            FROM eot_requests
        ") ?: [];
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                e.*,
                p.name AS project_name,
                p.slug AS project_slug,
                p.est_delivery,
                CONCAT(submitter.first_name, ' ', submitter.last_name) AS submitter_name,
                submitter.email AS submitter_email,
                CONCAT(approver.first_name, ' ', approver.last_name) AS approver_name
            FROM eot_requests e
            INNER JOIN projects p ON p.id = e.project_id
            INNER JOIN users submitter ON submitter.id = e.submitted_by
            LEFT JOIN users approver ON approver.id = e.approved_by
        ";
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 'e.status = ?';
            $bindings[] = $filters['status'];
        }

        if (!empty($filters['project_id'])) {
            $where[] = 'e.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(p.name LIKE ? OR e.reason LIKE ? OR submitter.first_name LIKE ? OR submitter.last_name LIKE ? OR CAST(e.eot_number AS CHAR) LIKE ?)";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term);
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(e.created_at) >= ?';
            $bindings[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(e.created_at) <= ?';
            $bindings[] = $filters['date_to'];
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
