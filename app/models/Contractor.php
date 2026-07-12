<?php

class Contractor extends Model
{
    protected static string $table = 'contractors';

    public static function ordered(array $filters = []): array
    {
        [$where, $bindings] = self::filters($filters);

        return Database::fetchAll(
            "SELECT ct.*, p.name AS project_name, p.slug AS project_slug, p.pct_complete AS project_progress,
                    c.name AS constituency_name
             FROM contractors ct
             LEFT JOIN projects p ON p.id = ct.project_id
             LEFT JOIN constituencies c ON c.id = ct.constituency_id
             {$where}
             ORDER BY ct.sort_order ASC, ct.company_name ASC",
            $bindings
        );
    }

    public static function featured(int $limit = 8): array
    {
        return Database::fetchAll(
            "SELECT ct.*, p.name AS project_name, p.slug AS project_slug, p.pct_complete AS project_progress,
                    c.name AS constituency_name
             FROM contractors ct
             LEFT JOIN projects p ON p.id = ct.project_id
             LEFT JOIN constituencies c ON c.id = ct.constituency_id
             WHERE ct.status IN ('active','tendering')
             ORDER BY ct.sort_order ASC, ct.company_name ASC
             LIMIT " . max(1, $limit)
        );
    }

    public static function stats(): array
    {
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'tendering' THEN 1 ELSE 0 END) AS tendering
             FROM contractors"
        ) ?: [];
    }

    public static function saveContractor(array $data, ?int $id = null): int
    {
        $company = Security::cleanString((string)($data['company_name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        $slug = $slug !== '' ? self::slug($slug) : self::slug($company);
        self::ensureUniqueSlug($slug, $id);
        $website = self::nullableUrl($data['website'] ?? null);
        $initials = trim((string)($data['initials'] ?? ''));
        $initials = $initials !== '' ? strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $initials), 0, 12)) : self::initials($company);
        $status = strtolower((string)($data['status'] ?? 'active'));
        $status = in_array($status, ['active', 'tendering', 'inactive', 'completed'], true) ? $status : 'active';

        $payload = [
            'company_name' => $company,
            'slug' => $slug,
            'initials' => $initials,
            'nca_grade' => Security::cleanString((string)($data['nca_grade'] ?? '')),
            'contact_person' => Security::cleanString((string)($data['contact_person'] ?? '')),
            'email' => Security::cleanString((string)($data['email'] ?? '')),
            'phone' => Security::cleanString((string)($data['phone'] ?? '')),
            'website' => $website,
            'project_id' => ((int)($data['project_id'] ?? 0)) > 0 ? (int)$data['project_id'] : null,
            'constituency_id' => ((int)($data['constituency_id'] ?? 0)) > 0 ? (int)$data['constituency_id'] : null,
            'status' => $status,
            'progress_pct' => min(100, max(0, (int)($data['progress_pct'] ?? 0))),
            'quote' => trim((string)($data['quote'] ?? '')),
            'logo_path' => Security::cleanString((string)($data['logo_path'] ?? '')),
            'sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
        ];

        if ($id !== null && $id > 0) {
            self::update($id, $payload);
            return $id;
        }

        return self::create($payload);
    }

    public static function findDetailed(int|string $idOrSlug): ?array
    {
        $isId = is_int($idOrSlug) || ctype_digit((string)$idOrSlug);
        $where = $isId ? 'ct.id = ?' : 'ct.slug = ?';
        return Database::fetch(
            "SELECT ct.*, p.name AS project_name, p.slug AS project_slug, c.name AS constituency_name
             FROM contractors ct
             LEFT JOIN projects p ON p.id = ct.project_id
             LEFT JOIN constituencies c ON c.id = ct.constituency_id
             WHERE {$where}
             LIMIT 1",
            [$idOrSlug]
        );
    }

    public static function slug(string $name): string
    {
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        return $slug !== '' ? $slug : 'contractor-' . substr(sha1($name . microtime(true)), 0, 8);
    }

    public static function initials(string $name): string
    {
        return LeadershipProfile::initials($name);
    }

    private static function filters(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['q'])) {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(ct.company_name LIKE ? OR ct.nca_grade LIKE ? OR p.name LIKE ? OR c.name LIKE ?)';
            array_push($bindings, $term, $term, $term, $term);
        }

        if (!empty($filters['status'])) {
            $where[] = 'ct.status = ?';
            $bindings[] = (string)$filters['status'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $bindings];
    }

    private static function nullableUrl(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return $value;
        }

        throw new RuntimeException('Contractor website must start with http:// or https://.');
    }

    private static function ensureUniqueSlug(string $slug, ?int $id = null): void
    {
        $existing = Database::fetch(
            'SELECT id FROM contractors WHERE slug = ? AND (? IS NULL OR id <> ?) LIMIT 1',
            [$slug, $id, $id]
        );

        if ($existing) {
            throw new RuntimeException('This contractor slug is already in use.');
        }
    }
}
