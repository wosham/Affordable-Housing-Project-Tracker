<?php

class Project extends Model
{
    protected static string $table = 'projects';

    public static function withRelations(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(self::selectSql() . $where . ' ORDER BY p.updated_at DESC, p.id DESC' . $limitSql, $bindings);
    }

    public static function countWithFilters(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch("
            SELECT COUNT(*) AS aggregate
            FROM projects p
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            {$where}
        ", $bindings);

        return (int)($row['aggregate'] ?? 0);
    }

    public static function findDetailed(int|string $idOrSlug): ?array
    {
        $isId = is_int($idOrSlug) || ctype_digit((string)$idOrSlug);
        $where = $isId ? 'p.id = ?' : 'p.slug = ?';
        return Database::fetch(self::selectSql() . ' WHERE ' . $where . ' LIMIT 1', [$idOrSlug]);
    }

    public static function featured(): array
    {
        return self::withRelations(['featured' => '1', 'status' => 'active'], 6);
    }

    public static function byConstituency(int $constituencyId): array
    {
        return self::withRelations(['constituency_id' => $constituencyId]);
    }

    public static function forPublic(array $filters = []): array
    {
        return self::withRelations($filters);
    }

    public static function stats(): array
    {
        return Database::fetch("
            SELECT
                COUNT(*) AS total_projects,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_projects,
                SUM(CASE WHEN status = 'planning' THEN 1 ELSE 0 END) AS planning_projects,
                SUM(CASE WHEN status IN ('stalled','on_hold') THEN 1 ELSE 0 END) AS watch_projects,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_projects,
                COALESCE(SUM(units), 0) AS total_units,
                COALESCE(SUM(contract_sum), 0) AS contract_value,
                COALESCE(AVG(pct_complete), 0) AS avg_completion
            FROM projects
        ") ?: [];
    }

    public static function statusOptions(): array
    {
        return ['planning', 'active', 'on_hold', 'stalled', 'completed', 'cancelled'];
    }

    public static function buildSlug(string $name): string
    {
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        return $slug !== '' ? $slug : 'project-' . substr(sha1($name . microtime(true)), 0, 8);
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $bindings = [$slug];
        $sql = 'SELECT id FROM projects WHERE slug = ?';
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $bindings[] = $exceptId;
        }

        return Database::fetch($sql . ' LIMIT 1', $bindings) !== null;
    }

    public static function safeDelete(int $id): bool
    {
        Database::beginTransaction();
        try {
            foreach (self::projectChildTables() as $table) {
                Database::query("DELETE FROM {$table} WHERE project_id = ?", [$id]);
            }
            Database::query('DELETE FROM projects WHERE id = ?', [$id]);
            Database::commit();
            return true;
        } catch (Throwable $e) {
            Database::rollBack();
            return false;
        }
    }

    public static function childSummary(int $id): array
    {
        $summary = [];
        foreach (self::projectChildTables() as $table) {
            try {
                $row = Database::fetch("SELECT COUNT(*) AS total FROM {$table} WHERE project_id = ?", [$id]);
                $summary[$table] = (int)($row['total'] ?? 0);
            } catch (Throwable $e) {
                $summary[$table] = 0;
            }
        }

        return $summary;
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                p.*,
                pc.name AS category_name,
                pc.slug AS category_slug,
                pc.icon AS category_icon,
                c.name AS constituency_name,
                c.slug AS constituency_slug,
                w.name AS ward_name,
                w.slug AS ward_slug
            FROM projects p
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
        ";
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 'p.status = ?';
            $bindings[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $where[] = 'pc.slug = ?';
            $bindings[] = $filters['category'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = ?';
            $bindings[] = (int)$filters['category_id'];
        }

        if (!empty($filters['constituency'])) {
            $where[] = 'c.slug = ?';
            $bindings[] = $filters['constituency'];
        }

        if (!empty($filters['constituency_id'])) {
            $where[] = 'p.constituency_id = ?';
            $bindings[] = (int)$filters['constituency_id'];
        }

        if (isset($filters['featured']) && (string)$filters['featured'] !== '') {
            $where[] = 'p.is_featured = ?';
            $bindings[] = (int)$filters['featured'];
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.slug LIKE ? OR p.description LIKE ? OR p.location_label LIKE ? OR p.contractor_name LIKE ? OR p.site_engineer LIKE ? OR c.name LIKE ? OR w.name LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term, $term, $term, $term, $term, $term);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function projectChildTables(): array
    {
        $rows = Database::fetchAll("
            SELECT TABLE_NAME AS table_name
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND COLUMN_NAME = 'project_id'
              AND TABLE_NAME <> 'projects'
            ORDER BY TABLE_NAME ASC
        ");

        return array_values(array_filter(
            array_map(static fn (array $row): string => (string)$row['table_name'], $rows),
            static fn (string $table): bool => preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table) === 1
        ));
    }
}
