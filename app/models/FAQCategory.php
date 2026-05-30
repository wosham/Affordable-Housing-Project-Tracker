<?php

class FAQCategory extends Model
{
    protected static string $table = 'faq_categories';

    public static function ordered(bool $publishedOnly = false): array
    {
        $where = $publishedOnly ? "WHERE status = 'published'" : '';
        return Database::fetchAll(
            "SELECT fc.*,
                    COUNT(fi.id) AS item_count,
                    SUM(CASE WHEN fi.status = 'published' THEN 1 ELSE 0 END) AS published_count
             FROM faq_categories fc
             LEFT JOIN faq_items fi ON fi.category_id = fc.id
             {$where}
             GROUP BY fc.id, fc.name, fc.slug, fc.icon, fc.description, fc.sort_order, fc.status, fc.created_at, fc.updated_at
             ORDER BY fc.sort_order ASC, fc.name ASC"
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM faq_categories WHERE slug = ? LIMIT 1', [$slug]);
    }
}
