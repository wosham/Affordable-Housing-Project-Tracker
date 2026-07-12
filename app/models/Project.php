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

    public static function publicDetail(int|string $idOrSlug): ?array
    {
        $isId = is_int($idOrSlug) || ctype_digit((string)$idOrSlug);
        $where = $isId ? 'p.id = ?' : 'p.slug = ?';
        $project = Database::fetch(self::selectSql() . " WHERE {$where} AND p.status <> 'cancelled' LIMIT 1", [$idOrSlug]);
        if (!$project) {
            return null;
        }

        return self::publicPayload($project);
    }

    public static function publicRelated(array $project, int $limit = 4): array
    {
        $filters = [];
        if (!empty($project['constituency_slug'])) {
            $filters['constituency'] = (string)$project['constituency_slug'];
        } elseif (!empty($project['category_slug'])) {
            $filters['category'] = (string)$project['category_slug'];
        }

        $rows = self::publicListing($filters, max(1, $limit + 1), 0);
        return array_values(array_slice(array_filter(
            $rows,
            static fn (array $row): bool => (int)($row['id'] ?? 0) !== (int)($project['id'] ?? 0)
        ), 0, max(1, $limit)));
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
        return self::publicListing($filters, 0, 0);
    }

    public static function publicListing(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::publicFilterSql($filters);
        $sortSql = self::publicSortSql((string)($filters['sort'] ?? 'pct-desc'));
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        $rows = Database::fetchAll(self::selectSql() . $where . $sortSql . $limitSql, $bindings);
        return array_map([self::class, 'publicPayload'], $rows);
    }

    public static function publicCount(array $filters = []): int
    {
        [$where, $bindings] = self::publicFilterSql($filters);
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

    public static function publicStats(array $filters = []): array
    {
        [$where, $bindings] = self::publicFilterSql($filters);
        $row = Database::fetch("
            SELECT
                COUNT(*) AS total_projects,
                SUM(CASE WHEN p.status IN ('active','completed') THEN 1 ELSE 0 END) AS active_projects,
                COALESCE(SUM(p.units), 0) AS total_units,
                COALESCE(ROUND(AVG(p.pct_complete)), 0) AS average_completion,
                COUNT(DISTINCT p.constituency_id) AS constituencies
            FROM projects p
            LEFT JOIN project_categories pc ON pc.id = p.category_id
            LEFT JOIN constituencies c ON c.id = p.constituency_id
            LEFT JOIN wards w ON w.id = p.ward_id
            {$where}
        ", $bindings);

        return [
            'total_projects' => (int)($row['total_projects'] ?? 0),
            'active_projects' => (int)($row['active_projects'] ?? 0),
            'total_units' => (int)($row['total_units'] ?? 0),
            'average_completion' => (int)($row['average_completion'] ?? 0),
            'constituencies' => (int)($row['constituencies'] ?? 0),
        ];
    }

    public static function publicConstituencies(): array
    {
        return Database::fetchAll("
            SELECT DISTINCT c.slug, c.name
            FROM constituencies c
            INNER JOIN projects p ON p.constituency_id = c.id
            WHERE p.status <> 'cancelled'
            ORDER BY FIELD(c.slug, 'saboti','cherangany','endebess','kiminini','kwanza'), c.name ASC
        ");
    }

    public static function publicCategories(): array
    {
        return Database::fetchAll("
            SELECT DISTINCT pc.slug, pc.name
            FROM project_categories pc
            INNER JOIN projects p ON p.category_id = pc.id
            WHERE p.status <> 'cancelled'
            ORDER BY pc.name ASC
        ");
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
        if ($id <= 0) {
            return false;
        }

        foreach (self::childSummary($id) as $count) {
            if ((int)$count > 0) {
                return false;
            }
        }

        try {
            Database::query('DELETE FROM projects WHERE id = ?', [$id]);
            return true;
        } catch (Throwable $e) {
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

    private static function publicFilterSql(array $filters): array
    {
        $where = ["p.status <> 'cancelled'"];
        $bindings = [];

        $status = strtolower(trim((string)($filters['status'] ?? '')));
        if ($status === 'active') {
            $where[] = "p.status IN ('active','completed')";
        } elseif ($status === 'planning') {
            $where[] = "p.status IN ('planning','on_hold','stalled')";
        } elseif (in_array($status, ['completed', 'on_hold', 'stalled'], true)) {
            $where[] = 'p.status = ?';
            $bindings[] = $status;
        }

        $category = strtolower(trim((string)($filters['category'] ?? '')));
        if ($category !== '' && preg_match('/^[a-z0-9-]+$/', $category)) {
            $where[] = 'pc.slug = ?';
            $bindings[] = $category;
        }

        $constituency = strtolower(trim((string)($filters['constituency'] ?? '')));
        if ($constituency !== '' && preg_match('/^[a-z0-9-]+$/', $constituency)) {
            $where[] = 'c.slug = ?';
            $bindings[] = $constituency;
        }

        $q = trim((string)($filters['q'] ?? $filters['search'] ?? ''));
        if ($q !== '') {
            $q = function_exists('mb_substr') ? mb_substr($q, 0, 80) : substr($q, 0, 80);
            $term = '%' . $q . '%';
            $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.location_label LIKE ? OR p.contractor_name LIKE ? OR p.current_milestone LIKE ? OR c.name LIKE ? OR w.name LIKE ? OR pc.name LIKE ?)';
            array_push($bindings, $term, $term, $term, $term, $term, $term, $term, $term);
        }

        return [' WHERE ' . implode(' AND ', $where), $bindings];
    }

    private static function publicSortSql(string $sort): string
    {
        return match ($sort) {
            'pct-asc' => ' ORDER BY p.pct_complete ASC, p.updated_at DESC, p.id DESC',
            'units-desc' => ' ORDER BY p.units DESC, p.updated_at DESC, p.id DESC',
            'name-asc' => ' ORDER BY p.name ASC, p.id DESC',
            'updated-desc' => ' ORDER BY p.updated_at DESC, p.id DESC',
            default => ' ORDER BY p.pct_complete DESC, p.updated_at DESC, p.id DESC',
        };
    }

    public static function publicPayload(array $project): array
    {
        $status = (string)($project['status'] ?? 'planning');
        $publicStatus = in_array($status, ['active', 'completed'], true) ? 'active' : 'planning';

        return [
            'id' => (int)($project['id'] ?? 0),
            'name' => (string)($project['name'] ?? ''),
            'slug' => (string)($project['slug'] ?? ''),
            'location_label' => (string)($project['location_label'] ?? ''),
            'ward_name' => (string)($project['ward_name'] ?? ''),
            'ward_slug' => (string)($project['ward_slug'] ?? ''),
            'status' => $status,
            'public_status' => $publicStatus,
            'status_label' => status_label($status),
            'pct_complete' => percentage($project['pct_complete'] ?? 0),
            'units' => (int)($project['units'] ?? 0),
            'current_milestone' => (string)($project['current_milestone'] ?? ''),
            'contractor_name' => (string)($project['contractor_name'] ?? ''),
            'description' => safe_truncate((string)($project['description'] ?? ''), 220),
            'full_description' => (string)($project['description'] ?? ''),
            'hero_image' => (string)($project['hero_image'] ?? ''),
            'images_json' => (string)($project['images_json'] ?? '[]'),
            'contract_sum' => (float)($project['contract_sum'] ?? 0),
            'funding_source' => (string)($project['funding_source'] ?? ''),
            'lead_agency' => (string)($project['lead_agency'] ?? ''),
            'site_engineer' => (string)($project['site_engineer'] ?? ''),
            'start_date' => (string)($project['start_date'] ?? ''),
            'est_delivery' => (string)($project['est_delivery'] ?? ''),
            'constituency_name' => (string)($project['constituency_name'] ?? ''),
            'constituency_slug' => (string)($project['constituency_slug'] ?? ''),
            'category_name' => (string)($project['category_name'] ?? ''),
            'category_slug' => (string)($project['category_slug'] ?? ''),
            'updated_at' => (string)($project['updated_at'] ?? ''),
        ];
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
