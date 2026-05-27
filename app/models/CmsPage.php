<?php

class CmsPage extends Model
{
    protected static string $table = 'cms_pages';

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM cms_pages WHERE slug = ? LIMIT 1', [$slug]);
    }

    public static function allWithSectionCounts(): array
    {
        return Database::fetchAll(
            'SELECT cp.*,
                    CONCAT(u.first_name, " ", u.last_name) AS updated_by_name,
                    COUNT(cs.id) AS section_count,
                    SUM(CASE WHEN cs.is_visible = 1 THEN 1 ELSE 0 END) AS visible_section_count
             FROM cms_pages cp
             LEFT JOIN cms_sections cs ON cs.page_id = cp.id AND cs.section_key <> "source_snapshot"
             LEFT JOIN users u ON u.id = cp.updated_by
             WHERE COALESCE(cp.template, "") <> "legacy"
             GROUP BY cp.id
             ORDER BY FIELD(cp.template, "landing", "content", "listing", "template", "media", "contact", "legal", "system"), cp.slug'
        );
    }

    public static function upsert(array $data): int
    {
        $existing = self::findBySlug((string)$data['slug']);
        $payload = [
            $data['template'] ?? null,
            $data['route_path'] ?? null,
            $data['status'] ?? 'published',
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['seo_keywords'] ?? null,
            $data['canonical_url'] ?? null,
            $data['hero_image'] ?? null,
            (int)Auth::id(),
        ];

        if ($existing) {
            $payload[] = (int)$existing['id'];
            Database::query(
                'UPDATE cms_pages
                 SET template = ?, route_path = ?, status = ?, seo_title = ?, seo_description = ?,
                     seo_keywords = ?, canonical_url = ?, hero_image = ?, updated_by = ?
                 WHERE id = ?',
                $payload
            );
            return (int)$existing['id'];
        }

        Database::query(
            'INSERT INTO cms_pages
                (template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url, hero_image, updated_by, slug)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_merge($payload, [$data['slug']])
        );

        return (int)Database::lastInsertId();
    }
}
