<?php

class Variation extends Model
{
    protected static string $table = 'variations';

    public static function detailed(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY v.created_at DESC, v.id DESC' . $limitSql, $bindings);
    }

    public static function countDetailed(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch("
            SELECT COUNT(*) AS aggregate
            FROM variations v
            INNER JOIN projects p ON p.id = v.project_id
            INNER JOIN users submitter ON submitter.id = v.submitted_by
            {$where}
        ", $bindings);

        return (int)($row['aggregate'] ?? 0);
    }

    public static function findDetailed(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE v.id = ? LIMIT 1', [$id]);
    }

    public static function stats(): array
    {
        return Database::fetch("
            SELECT
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_variations,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_variations,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_variations,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS pending_value,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) AS approved_value
            FROM variations
        ") ?: [];
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                v.*,
                p.name AS project_name,
                p.slug AS project_slug,
                CONCAT(submitter.first_name, ' ', submitter.last_name) AS submitter_name,
                submitter.email AS submitter_email,
                CONCAT(approver.first_name, ' ', approver.last_name) AS approver_name
            FROM variations v
            INNER JOIN projects p ON p.id = v.project_id
            INNER JOIN users submitter ON submitter.id = v.submitted_by
            LEFT JOIN users approver ON approver.id = v.approved_by
        ";
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 'v.status = ?';
            $bindings[] = $filters['status'];
        }

        if (!empty($filters['project_id'])) {
            $where[] = 'v.project_id = ?';
            $bindings[] = (int)$filters['project_id'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(p.name LIKE ? OR v.description LIKE ? OR v.reason LIKE ? OR submitter.first_name LIKE ? OR submitter.last_name LIKE ? OR CAST(v.vo_number AS CHAR) LIKE ?)";
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term);
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(v.created_at) >= ?';
            $bindings[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(v.created_at) <= ?';
            $bindings[] = $filters['date_to'];
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
