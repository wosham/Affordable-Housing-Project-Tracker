<?php
class FAQItem extends Model
{
    protected static string $table = 'faq_items';

    public static function publicItems(): array
    {
        return Database::fetchAll(
            "SELECT fi.*, fc.name AS category_name, fc.slug AS category_slug, fc.icon AS category_icon,
                    fc.description AS category_description, fc.sort_order AS category_sort
             FROM faq_items fi
             LEFT JOIN faq_categories fc ON fc.id = fi.category_id
             WHERE fi.status = 'published'
               AND COALESCE(fi.is_visible, 1) = 1
               AND (fc.id IS NULL OR fc.status = 'published')
             ORDER BY COALESCE(fc.sort_order, 9999) ASC, fi.sort_order ASC, fi.id ASC"
        );
    }

    public static function publicGrouped(): array
    {
        $groups = [];
        foreach (self::publicItems() as $item) {
            $slug = trim((string)($item['category_slug'] ?? ''));
            if ($slug === '') {
                $slug = self::slugify((string)($item['category'] ?? 'general'));
            }
            if ($slug === '') {
                $slug = 'general';
            }

            if (!isset($groups[$slug])) {
                $groups[$slug] = [
                    'id' => (int)($item['category_id'] ?? 0),
                    'slug' => $slug,
                    'name' => (string)($item['category_name'] ?: $item['category'] ?: 'General Questions'),
                    'icon' => (string)($item['category_icon'] ?: 'fa-circle-question'),
                    'description' => (string)($item['category_description'] ?: ''),
                    'items' => [],
                ];
            }

            $groups[$slug]['items'][] = $item;
        }

        return $groups;
    }

    public static function publicPopular(int $limit = 6): array
    {
        $limit = max(1, min(24, $limit));
        return Database::fetchAll(
            "SELECT fi.*, fc.slug AS category_slug
             FROM faq_items fi
             LEFT JOIN faq_categories fc ON fc.id = fi.category_id
             WHERE fi.status = 'published'
               AND COALESCE(fi.is_visible, 1) = 1
               AND fi.is_popular = 1
               AND (fc.id IS NULL OR fc.status = 'published')
             ORDER BY fi.sort_order ASC, fi.id ASC
             LIMIT {$limit}"
        );
    }

    public static function publicStats(): array
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS questions,
                    COUNT(DISTINCT fi.category_id) AS categories,
                    MAX(fi.updated_at) AS last_updated
             FROM faq_items fi
             LEFT JOIN faq_categories fc ON fc.id = fi.category_id
             WHERE fi.status = 'published'
               AND COALESCE(fi.is_visible, 1) = 1
               AND (fc.id IS NULL OR fc.status = 'published')"
        ) ?: [];

        return [
            'questions' => (int)($row['questions'] ?? 0),
            'categories' => (int)($row['categories'] ?? 0),
            'last_updated' => $row['last_updated'] ?? null,
        ];
    }

    public static function publicDomId(array $item): string
    {
        $slug = trim((string)($item['slug'] ?? ''));
        if ($slug === '') {
            $slug = self::slugify((string)($item['question'] ?? 'faq'));
        }
        return 'q-' . ($slug !== '' ? $slug : (int)($item['id'] ?? 0));
    }

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

    private static function slugify(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? ''));
        return trim($slug, '-');
    }
}
