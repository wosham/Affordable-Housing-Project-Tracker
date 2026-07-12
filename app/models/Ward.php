<?php

class Ward extends Model
{
    protected static string $table = 'wards';

    public static function forConstituency(int $constituencyId, bool $publicOnly = false): array
    {
        if ($constituencyId <= 0) {
            return [];
        }

        $where = 'w.constituency_id = ?';
        $bindings = [$constituencyId];
        if ($publicOnly) {
            $where .= ' AND w.is_public = 1';
        }

        return Database::fetchAll(
            "SELECT w.*, c.name AS constituency_name
             FROM wards w
             LEFT JOIN constituencies c ON c.id = w.constituency_id
             WHERE {$where}
             ORDER BY w.sort_order ASC, w.name ASC",
            $bindings
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }

        return Database::fetch(
            "SELECT w.*, c.name AS constituency_name
             FROM wards w
             LEFT JOIN constituencies c ON c.id = w.constituency_id
             WHERE w.slug = ?
             LIMIT 1",
            [$slug]
        );
    }

    public static function allOrdered(bool $publicOnly = false): array
    {
        $where = $publicOnly ? 'WHERE w.is_public = 1' : '';
        return Database::fetchAll(
            "SELECT w.*, c.name AS constituency_name
             FROM wards w
             LEFT JOIN constituencies c ON c.id = w.constituency_id
             {$where}
             ORDER BY c.name ASC, w.sort_order ASC, w.name ASC"
        );
    }

    public static function optionsForSelect(?int $constituencyId = null): array
    {
        $rows = $constituencyId && $constituencyId > 0
            ? self::forConstituency($constituencyId)
            : self::allOrdered();

        return array_map(static fn (array $row): array => [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'constituency_id' => (int)($row['constituency_id'] ?? 0),
            'label' => trim((string)($row['name'] ?? '')) .
                (!empty($row['constituency_name']) ? ' (' . $row['constituency_name'] . ')' : ''),
        ], $rows);
    }
}
