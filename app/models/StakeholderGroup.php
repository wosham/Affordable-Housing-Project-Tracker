<?php

class StakeholderGroup extends Model
{
    protected static string $table = 'stakeholder_groups';

    public static function ordered(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'g.status = ?';
            $params[] = $filters['status'];
        }

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(g.name LIKE ? OR g.summary LIKE ? OR g.description LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term);
        }

        $sql = "SELECT g.*,
                    COUNT(DISTINCT CASE WHEN s.status = 'published' THEN s.id END) AS stakeholder_count,
                    COUNT(DISTINCT CASE WHEN m.status = 'published' THEN m.id END) AS milestone_count,
                    COUNT(DISTINCT CASE WHEN t.status = 'published' THEN t.id END) AS testimonial_count
                FROM stakeholder_groups g
                LEFT JOIN stakeholders s ON s.group_id = g.id
                LEFT JOIN stakeholder_milestones m ON m.group_id = g.id
                LEFT JOIN stakeholder_testimonials t ON t.group_id = g.id"
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' GROUP BY g.id ORDER BY g.sort_order ASC, g.name ASC';

        return Database::fetchAll($sql, $params);
    }

    public static function published(): array
    {
        return self::ordered(['status' => 'published']);
    }

    public static function findDetailed(int|string $idOrSlug): ?array
    {
        if (is_numeric($idOrSlug)) {
            return Database::fetch('SELECT * FROM stakeholder_groups WHERE id = ? LIMIT 1', [(int)$idOrSlug]);
        }

        return Database::fetch('SELECT * FROM stakeholder_groups WHERE slug = ? LIMIT 1', [(string)$idOrSlug]);
    }

    public static function saveGroup(array $input, ?int $id = null): int
    {
        $name = Security::cleanString((string)($input['name'] ?? ''));
        $slug = self::slug((string)($input['slug'] ?? $name));
        self::ensureUniqueSlug($slug, $id);
        $data = [
            'slug' => $slug,
            'name' => $name,
            'icon' => Security::cleanString((string)($input['icon'] ?? '')),
            'count_label' => Security::cleanString((string)($input['count_label'] ?? '')),
            'summary' => Security::cleanString((string)($input['summary'] ?? '')),
            'description' => trim((string)($input['description'] ?? '')),
            'tags_json' => self::jsonLines((string)($input['tags'] ?? '')),
            'cta_label' => Security::cleanString((string)($input['cta_label'] ?? '')),
            'cta_url' => self::nullableUrl($input['cta_url'] ?? null),
            'legal_basis' => trim((string)($input['legal_basis'] ?? '')),
            'responsibilities_json' => self::jsonLines((string)($input['responsibilities'] ?? '')),
            'reporting_lines' => trim((string)($input['reporting_lines'] ?? '')),
            'sort_order' => max(0, Security::cleanInt($input['sort_order'] ?? 0)),
            'status' => self::status((string)($input['status'] ?? 'published')),
        ];

        if ($id) {
            self::update($id, $data);
            return $id;
        }

        return (int)self::create($data);
    }

    public static function tags(array $row): array
    {
        return self::jsonArray($row['tags_json'] ?? null);
    }

    public static function responsibilities(array $row): array
    {
        return self::jsonArray($row['responsibilities_json'] ?? null);
    }

    public static function stats(): array
    {
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published
             FROM stakeholder_groups"
        ) ?: [];
    }

    public static function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
        return $slug !== '' ? $slug : 'stakeholder-group-' . time();
    }

    private static function status(string $status): string
    {
        return strtolower(trim($status)) === 'draft' ? 'draft' : 'published';
    }

    private static function jsonLines(string $value): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $value) ?: [])));
        return json_encode($lines, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function jsonArray(mixed $value): array
    {
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? array_values(array_filter($decoded, static fn ($item): bool => trim((string)$item) !== '')) : [];
    }

    private static function nullableUrl(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '#') {
            return $value === '#' ? '#' : null;
        }

        if (preg_match('#^(?:https?://|mailto:|tel:|/)#i', $value) === 1) {
            return $value;
        }

        throw new RuntimeException('Stakeholder group CTA URL must be http, https, mailto, tel or an internal path.');
    }

    private static function ensureUniqueSlug(string $slug, ?int $id = null): void
    {
        $existing = Database::fetch(
            'SELECT id FROM stakeholder_groups WHERE slug = ? AND (? IS NULL OR id <> ?) LIMIT 1',
            [$slug, $id, $id]
        );

        if ($existing) {
            throw new RuntimeException('This stakeholder group slug is already in use.');
        }
    }
}
