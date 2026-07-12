<?php

class StakeholderEngagementPath extends Model
{
    protected static string $table = 'stakeholder_engagement_paths';

    public static function ordered(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }

        return Database::fetchAll(
            'SELECT * FROM stakeholder_engagement_paths'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY sort_order ASC, title ASC',
            $params
        );
    }

    public static function findDetailed(int $id): ?array
    {
        return self::find($id);
    }

    public static function savePath(array $input, ?int $id = null): int
    {
        $title = Security::cleanString((string)($input['title'] ?? ''));
        $slug = StakeholderGroup::slug((string)($input['slug'] ?? $title));
        self::ensureUniqueSlug($slug, $id);
        $data = [
            'slug' => $slug,
            'title' => $title,
            'icon' => Security::cleanString((string)($input['icon'] ?? '')),
            'description' => trim((string)($input['description'] ?? '')),
            'steps_json' => self::jsonLines((string)($input['steps'] ?? '')),
            'button_label' => Security::cleanString((string)($input['button_label'] ?? '')),
            'button_url' => self::nullableUrl($input['button_url'] ?? null),
            'tone' => Security::cleanString((string)($input['tone'] ?? 'standard')),
            'status' => strtolower((string)($input['status'] ?? 'published')) === 'draft' ? 'draft' : 'published',
            'sort_order' => max(0, Security::cleanInt($input['sort_order'] ?? 0)),
        ];

        if ($id) {
            self::update($id, $data);
            return $id;
        }

        return (int)self::create($data);
    }

    public static function steps(array $row): array
    {
        $decoded = json_decode((string)($row['steps_json'] ?? ''), true);
        return is_array($decoded) ? array_values(array_filter($decoded)) : [];
    }

    public static function stats(): array
    {
        return Database::fetch("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published FROM stakeholder_engagement_paths") ?: [];
    }

    private static function jsonLines(string $value): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $value) ?: [])));
        return json_encode($lines, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function nullableUrl(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#^(?:https?://|mailto:|tel:|/)#i', $value) === 1) {
            return $value;
        }

        throw new RuntimeException('Engagement button URL must be http, https, mailto, tel or an internal path.');
    }

    private static function ensureUniqueSlug(string $slug, ?int $id = null): void
    {
        $existing = Database::fetch(
            'SELECT id FROM stakeholder_engagement_paths WHERE slug = ? AND (? IS NULL OR id <> ?) LIMIT 1',
            [$slug, $id, $id]
        );

        if ($existing) {
            throw new RuntimeException('This engagement path slug is already in use.');
        }
    }
}
