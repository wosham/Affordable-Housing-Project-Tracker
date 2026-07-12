<?php

class Constituency extends Model
{
    protected static string $table = 'constituencies';
    private const PUBLIC_ORDER = "'saboti','cherangany','endebess','kiminini','kwanza'";

    public static function withProjectCounts(): array
    {
        return Database::fetchAll("
            SELECT
                c.*,
                COALESCE(ps.live_project_count, 0) AS live_project_count,
                COALESCE(ps.live_total_units, 0) AS live_total_units,
                COALESCE(ps.live_avg_completion, 0) AS live_avg_completion,
                COALESCE(ws.ward_count, 0) AS ward_count
            FROM constituencies c
            LEFT JOIN (
                SELECT constituency_id, COUNT(*) AS live_project_count, SUM(units) AS live_total_units, AVG(pct_complete) AS live_avg_completion
                FROM projects
                WHERE status <> 'cancelled'
                GROUP BY constituency_id
            ) ps ON ps.constituency_id = c.id
            LEFT JOIN (
                SELECT constituency_id, COUNT(*) AS ward_count
                FROM wards
                WHERE is_public = 1
                GROUP BY constituency_id
            ) ws ON ws.constituency_id = c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ");
    }
    public static function adminList(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $bindings] = self::adminFilterSql($filters);
        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll("
            SELECT
                c.*,
                COALESCE(ps.live_project_count, 0) AS live_project_count,
                COALESCE(ps.live_total_units, 0) AS live_total_units,
                COALESCE(ps.live_avg_completion, 0) AS live_avg_completion,
                COALESCE(ws.ward_count, 0) AS ward_count
            FROM constituencies c
            LEFT JOIN (
                SELECT constituency_id, COUNT(*) AS live_project_count, SUM(units) AS live_total_units, ROUND(AVG(pct_complete)) AS live_avg_completion
                FROM projects
                WHERE status <> 'cancelled'
                GROUP BY constituency_id
            ) ps ON ps.constituency_id = c.id
            LEFT JOIN (
                SELECT constituency_id, COUNT(*) AS ward_count
                FROM wards
                GROUP BY constituency_id
            ) ws ON ws.constituency_id = c.id
            {$where}
            ORDER BY c.sort_order ASC, c.name ASC
            {$limitSql}
        ", $bindings);
    }
    public static function countWithFilters(array $filters = []): int
    {
        [$where, $bindings] = self::adminFilterSql($filters);
        $row = Database::fetch("SELECT COUNT(*) AS aggregate FROM constituencies c {$where}", $bindings);
        return (int)($row['aggregate'] ?? 0);
    }

    public static function adminStats(): array
    {
        return Database::fetch("
            SELECT
                (SELECT COUNT(*) FROM constituencies) AS total_constituencies,
                (SELECT COUNT(*) FROM constituencies WHERE is_public = 1) AS public_constituencies,
                (SELECT COUNT(*) FROM wards) AS total_wards,
                (SELECT COUNT(*) FROM projects) AS total_projects,
                COALESCE((SELECT SUM(units) FROM projects WHERE status <> 'cancelled'), 0) AS total_outputs,
                COALESCE((SELECT ROUND(AVG(pct_complete)) FROM projects WHERE status <> 'cancelled'), 0) AS avg_completion
        ") ?: [];
    }
    public static function adminDetail(int $id): ?array
    {
        return Database::fetch("
            SELECT
                c.*,
                COUNT(DISTINCT p.id) AS live_project_count,
                COALESCE(SUM(CASE WHEN p.status <> 'cancelled' THEN p.units ELSE 0 END), 0) AS live_total_units,
                COALESCE(ROUND(AVG(CASE WHEN p.status <> 'cancelled' THEN p.pct_complete ELSE NULL END)), 0) AS live_avg_completion
            FROM constituencies c
            LEFT JOIN projects p ON p.constituency_id = c.id
            WHERE c.id = ?
            GROUP BY c.id
            LIMIT 1
        ", [$id]);
    }

    public static function wardsForAdmin(int $constituencyId): array
    {
        return Database::fetchAll("
            SELECT
                w.*,
                COUNT(p.id) AS project_count
            FROM wards w
            LEFT JOIN projects p ON p.ward_id = w.id
            WHERE w.constituency_id = ?
            GROUP BY w.id
            ORDER BY w.sort_order ASC, w.id ASC
        ", [$constituencyId]);
    }

    public static function slugify(string $value, string $fallback = 'constituency'): string
    {
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
        return $slug !== '' ? $slug : $fallback . '-' . substr(sha1($value . microtime(true)), 0, 8);
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $bindings = [$slug];
        $sql = 'SELECT id FROM constituencies WHERE slug = ?';
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $bindings[] = $exceptId;
        }

        return Database::fetch($sql . ' LIMIT 1', $bindings) !== null;
    }

    public static function wardSlugExists(int $constituencyId, string $slug, ?int $exceptId = null): bool
    {
        $bindings = [$constituencyId, $slug];
        $sql = 'SELECT id FROM wards WHERE constituency_id = ? AND slug = ?';
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $bindings[] = $exceptId;
        }

        return Database::fetch($sql . ' LIMIT 1', $bindings) !== null;
    }

    public static function wardProjectCount(int $wardId): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS aggregate FROM projects WHERE ward_id = ?', [$wardId]);
        return (int)($row['aggregate'] ?? 0);
    }

    public static function publicList(): array
    {
        $rows = Database::fetchAll("
            SELECT
                c.*,
                COUNT(DISTINCT p.id) AS live_project_count,
                COALESCE(SUM(CASE WHEN p.status <> 'cancelled' THEN p.units ELSE 0 END), 0) AS live_total_units,
                COALESCE(ROUND(AVG(CASE WHEN p.status <> 'cancelled' THEN p.pct_complete ELSE NULL END)), 0) AS live_avg_completion
            FROM constituencies c
            LEFT JOIN projects p ON p.constituency_id = c.id
                AND p.status <> 'cancelled'
            WHERE c.is_public = 1
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ");

        $wards = self::wardsBySlug();

        return array_map(
            static fn (array $row): array => self::publicPayload($row, $wards[(string)($row['slug'] ?? '')] ?? []),
            $rows
        );
    }

    public static function publicDetail(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
            return null;
        }

        foreach (self::publicList() as $row) {
            if ((string)$row['slug'] === $slug || (string)$row['id'] === $slug) {
                return $row;
            }
        }

        return null;
    }

    public static function publicRelated(string $currentSlug, int $limit = 4): array
    {
        $currentSlug = strtolower(trim($currentSlug));
        return array_values(array_slice(array_filter(
            self::publicList(),
            static fn (array $row): bool => (string)($row['slug'] ?? '') !== $currentSlug
        ), 0, max(1, $limit)));
    }

    public static function publicStats(): array
    {
        $rows = self::publicList();

        $projects = array_sum(array_map(static fn (array $row): int => (int)$row['project_count'], $rows));
        $units = array_sum(array_map(static fn (array $row): int => (int)$row['total_units'], $rows));
        $residents = array_sum(array_map(static fn (array $row): int => (int)$row['population_raw'], $rows));
        $avg = count($rows) > 0
            ? (int)round(array_sum(array_map(static fn (array $row): int => (int)$row['avg_completion'], $rows)) / count($rows))
            : 0;

        return [
            'constituencies' => count($rows),
            'projects' => $projects,
            'units' => $units,
            'residents' => $residents,
            'avg_completion' => max(0, min(100, $avg)),
        ];
    }

    public static function publicPayload(array $row, array $wards = []): array
    {
        $slug = (string)($row['slug'] ?? '');
        $projectCount = (int)($row['live_project_count'] ?? 0) > 0
            ? (int)$row['live_project_count']
            : (int)($row['total_projects'] ?? 0);
        $totalUnits = (int)($row['live_total_units'] ?? 0) > 0
            ? (int)$row['live_total_units']
            : (int)($row['total_units'] ?? 0);
        $avgCompletion = (int)round((float)($row['live_avg_completion'] ?? 0) > 0
            ? (float)$row['live_avg_completion']
            : (float)($row['avg_completion'] ?? 0));
        $population = (int)($row['population'] ?? 0);
        $status = (string)($row['status'] ?? 'planning');
        $publicStatus = in_array($status, ['active', 'completed'], true) ? 'active' : 'planning';

        return [
            'id' => $slug,
            'database_id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'slug' => $slug,
            'wards' => array_values(array_filter(array_map('strval', $wards))),
            'ward_count' => count($wards),
            'population' => $population > 0 && function_exists('format_number') ? '~' . format_number($population) : ($population > 0 ? '~' . number_format($population) : '-'),
            'population_raw' => $population,
            'mp' => (string)($row['mp'] ?? ''),
            'mp_photo' => (string)($row['mp_photo'] ?? ''),
            'description' => (string)($row['description'] ?? ''),
            'total_units' => $totalUnits,
            'totalUnits' => $totalUnits,
            'avg_completion' => max(0, min(100, $avgCompletion)),
            'avgCompletion' => max(0, min(100, $avgCompletion)),
            'status' => $publicStatus,
            'raw_status' => $status,
            'project_count' => $projectCount,
            'projectCount' => $projectCount,
            'hero_image' => (string)($row['hero_image'] ?? ''),
            'heroImage' => (string)($row['hero_image'] ?? ''),
            'is_public' => (int)($row['is_public'] ?? 1),
            'sort_order' => (int)($row['sort_order'] ?? 0),
            'link' => 'constituency-detail.php?id=' . rawurlencode($slug),
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];
    }

    public static function mapShapes(): array
    {
        return [
            'endebess' => ['path' => 'M 20,20 L 220,20 L 220,185 L 120,205 L 20,165 Z', 'label_x' => 112, 'label_y' => 100, 'sub_y' => 116],
            'cherangany' => ['path' => 'M 220,20 L 460,20 L 460,235 L 265,235 L 220,185 Z', 'label_x' => 352, 'label_y' => 115, 'sub_y' => 131],
            'kiminini' => ['path' => 'M 20,165 L 120,205 L 120,385 L 20,385 Z', 'label_x' => 62, 'label_y' => 295, 'sub_y' => 311],
            'saboti' => ['path' => 'M 120,205 L 220,185 L 265,235 L 265,385 L 120,385 Z', 'label_x' => 190, 'label_y' => 308, 'sub_y' => 324],
            'kwanza' => ['path' => 'M 265,235 L 460,235 L 460,385 L 265,385 Z', 'label_x' => 365, 'label_y' => 313, 'sub_y' => 329],
        ];
    }

    private static function adminFilterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(c.name LIKE ? OR c.slug LIKE ? OR c.description LIKE ? OR c.mp LIKE ?)';
            $like = '%' . $q . '%';
            array_push($bindings, $like, $like, $like, $like);
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '' && in_array($status, ['planning', 'active', 'completed'], true)) {
            $where[] = 'c.status = ?';
            $bindings[] = $status;
        }

        $visibility = trim((string)($filters['visibility'] ?? ''));
        if ($visibility === 'public' || $visibility === 'hidden') {
            $where[] = 'c.is_public = ?';
            $bindings[] = $visibility === 'public' ? 1 : 0;
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function wardsBySlug(): array
    {
        $wards = [];
        foreach (Database::fetchAll("
            SELECT c.slug AS constituency_slug, w.name
            FROM wards w
            INNER JOIN constituencies c ON c.id = w.constituency_id
            WHERE c.is_public = 1 AND w.is_public = 1
            ORDER BY c.sort_order ASC, w.sort_order ASC, w.id ASC
        ") as $ward) {
            $wards[(string)$ward['constituency_slug']][] = (string)$ward['name'];
        }

        return $wards;
    }
}