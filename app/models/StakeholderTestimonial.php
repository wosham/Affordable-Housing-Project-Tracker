<?php

class StakeholderTestimonial extends Model
{
    protected static string $table = 'stakeholder_testimonials';

    public static function ordered(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (($filters['status'] ?? '') !== '') {
            $where[] = 't.status = ?';
            $params[] = $filters['status'];
        }

        return Database::fetchAll(
            "SELECT t.*, g.name AS group_name
             FROM stakeholder_testimonials t
             LEFT JOIN stakeholder_groups g ON g.id = t.group_id"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY t.sort_order ASC, t.name ASC',
            $params
        );
    }

    public static function findDetailed(int $id): ?array
    {
        return self::find($id);
    }

    public static function saveTestimonial(array $input, ?int $id = null): int
    {
        $name = Security::cleanString((string)($input['name'] ?? ''));
        $data = [
            'group_id' => Security::cleanInt($input['group_id'] ?? 0) ?: null,
            'name' => $name,
            'role' => Security::cleanString((string)($input['role'] ?? '')),
            'initials' => Security::cleanString((string)($input['initials'] ?? self::initials($name))),
            'photo_path' => Security::cleanString((string)($input['photo_path'] ?? '')),
            'rating' => min(5, max(1, Security::cleanInt($input['rating'] ?? 5))),
            'quote' => trim((string)($input['quote'] ?? '')),
            'tags_json' => self::jsonLines((string)($input['tags'] ?? '')),
            'status' => strtolower((string)($input['status'] ?? 'published')) === 'draft' ? 'draft' : 'published',
            'sort_order' => max(0, Security::cleanInt($input['sort_order'] ?? 0)),
        ];

        if ($id) {
            self::update($id, $data);
            return $id;
        }

        return (int)self::create($data);
    }

    public static function tags(array $row): array
    {
        $decoded = json_decode((string)($row['tags_json'] ?? ''), true);
        return is_array($decoded) ? array_values(array_filter($decoded)) : [];
    }

    public static function stats(): array
    {
        return Database::fetch("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published FROM stakeholder_testimonials") ?: [];
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        return $initials ?: 'ST';
    }

    private static function jsonLines(string $value): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $value) ?: [])));
        return json_encode($lines, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
