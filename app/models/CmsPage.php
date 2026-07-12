<?php

class CmsPage extends Model
{
    protected static string $table = 'cms_pages';

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM cms_pages WHERE slug = ? LIMIT 1', [$slug]);
    }

    public static function findByRoute(string $routePath): ?array
    {
        $routePath = trim(str_replace('\\', '/', $routePath), '/');
        if ($routePath === '') {
            $routePath = 'index.php';
        }

        return Database::fetch('SELECT * FROM cms_pages WHERE TRIM(BOTH "/" FROM route_path) = ? LIMIT 1', [$routePath]);
    }

    public static function publicBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM cms_pages WHERE slug = ? AND status = "published" LIMIT 1', [$slug]);
    }

    public static function publicByRoute(string $routePath): ?array
    {
        $routePath = trim(str_replace('\\', '/', $routePath), '/');
        if ($routePath === '') {
            $routePath = 'index.php';
        }

        return Database::fetch(
            'SELECT * FROM cms_pages WHERE TRIM(BOTH "/" FROM route_path) = ? AND status = "published" LIMIT 1',
            [$routePath]
        );
    }

    public static function sections(int $pageId, bool $visibleOnly = true): array
    {
        $sections = [];
        foreach (CmsSection::forPage($pageId) as $section) {
            if ($visibleOnly && (int)($section['is_visible'] ?? 0) !== 1) {
                continue;
            }

            $sections[(string)$section['section_key']] = $section;
        }

        return $sections;
    }

    public static function seo(string $slug, array $fallback = []): array
    {
        $page = self::publicBySlug($slug);
        if (!$page) {
            return $fallback;
        }

        return array_filter([
            'title' => $page['seo_title'] ?? null,
            'description' => $page['seo_description'] ?? null,
            'keywords' => $page['seo_keywords'] ?? null,
            'canonical_url' => $page['canonical_url'] ?? null,
            'hero_image' => $page['hero_image'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    public static function ensurePublicPage(
        string $slug,
        string $routePath,
        string $title,
        string $description = '',
        string $template = 'content'
    ): int {
        return self::upsert([
            'slug' => $slug,
            'route_path' => $routePath,
            'template' => $template,
            'status' => 'published',
            'seo_title' => $title,
            'seo_description' => $description,
        ]);
    }

    public static function allWithSectionCounts(): array
    {
        return Database::fetchAll(
            'SELECT cp.*,
                    CONCAT(u.first_name, " ", u.last_name) AS updated_by_name,
                    COALESCE(SUM(CASE WHEN cs.is_visible = 1 THEN 1 ELSE 0 END), 0) AS section_count,
                    COALESCE(SUM(CASE WHEN cs.is_visible = 1 THEN 1 ELSE 0 END), 0) AS visible_section_count
             FROM cms_pages cp
             LEFT JOIN cms_sections cs ON cs.page_id = cp.id
                AND cs.section_key NOT IN ("source_snapshot", "current_frontend_snapshot", "frontend_source_snapshot")
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
