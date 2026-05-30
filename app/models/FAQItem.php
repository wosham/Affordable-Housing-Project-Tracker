<?php
class FAQItem extends Model
{
    protected static string $table = 'faq_items';

    public static function filtered(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        [$where, $bindings] = self::filterSql($filters);
        $sql = "SELECT fi.*, fc.name AS category_name, fc.slug AS category_slug, fc.icon AS category_icon
                FROM faq_items fi
                LEFT JOIN faq_categories fc ON fc.id = fi.category_id
                {$where}
                ORDER BY fc.sort_order ASC, fi.sort_order ASC, fi.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        return Database::fetchAll($sql, $bindings);
    }

    public static function countFiltered(array $filters = []): int
    {
        [$where, $bindings] = self::filterSql($filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM faq_items fi LEFT JOIN faq_categories fc ON fc.id = fi.category_id {$where}", $bindings);
        return (int)($row['total'] ?? 0);
    }

    public static function stats(): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft,
                    SUM(CASE WHEN is_popular = 1 THEN 1 ELSE 0 END) AS popular
             FROM faq_items"
        ) ?? [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'published' => (int)($row['published'] ?? 0),
            'draft' => (int)($row['draft'] ?? 0),
            'popular' => (int)($row['popular'] ?? 0),
        ];
    }

    private static function filterSql(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(fi.question LIKE ? OR fi.answer LIKE ? OR fi.search_keywords LIKE ?)';
            $needle = '%' . $filters['q'] . '%';
            array_push($bindings, $needle, $needle, $needle);
        }

        if (($filters['category_id'] ?? '') !== '') {
            $where[] = 'fi.category_id = ?';
            $bindings[] = (int)$filters['category_id'];
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'fi.status = ?';
            $bindings[] = $filters['status'];
        }

        if (($filters['popular'] ?? '') === '1') {
            $where[] = 'fi.is_popular = 1';
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
