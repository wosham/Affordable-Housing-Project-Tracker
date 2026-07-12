<?php

class PublicDataContract
{
    public static function payload(string $slug, array $extraModels = [], array $fallback = []): array
    {
        $contract = CmsLoader::pageContract($slug);
        $models = array_merge(self::modelsForContract($contract), $extraModels);

        $payload = CmsLoader::publicPayload($slug, $models, []);
        $payload['settings'] = CmsSetting::groupedPublic();
        $payload['generated_at'] = date(DATE_ATOM);
        $payload['content_version'] = self::contentVersion($slug);

        return $payload;
    }

    public static function modelsForPage(string $slug): array
    {
        return self::modelsForContract(CmsLoader::pageContract($slug));
    }

    private static function modelsForContract(array $contract): array
    {
        $requested = array_map('strtolower', $contract['models'] ?? []);
        $models = [];

        if (in_array('project', $requested, true)) {
            $models['projects'] = self::projects();
            $models['stats'] = self::projectStats();
        }

        if (in_array('constituency', $requested, true)) {
            $models['constituencies'] = self::constituencies();
        }

        if (in_array('newsarticle', $requested, true)) {
            $models['news'] = self::news();
            $models['news_categories'] = self::newsCategories();
        }

        if (in_array('announcement', $requested, true)) {
            $models['announcements'] = self::announcements();
        }

        if (in_array('medialibrary', $requested, true)) {
            $models['gallery'] = self::gallery();
        }

        return $models;
    }

    private static function projects(int $limit = 24): array
    {
        return self::fetchAll(
            'SELECT p.id, p.name, p.slug, p.location_label, p.status, p.pct_complete, p.contract_sum,
                    p.start_date, p.est_delivery, p.current_milestone, p.contractor_name, p.description,
                    p.hero_image, p.units, p.is_featured, p.updated_at,
                    c.name AS constituency_name, c.slug AS constituency_slug,
                    pc.name AS category_name, pc.slug AS category_slug
             FROM projects p
             LEFT JOIN constituencies c ON c.id = p.constituency_id
             LEFT JOIN project_categories pc ON pc.id = p.category_id
             WHERE p.status NOT IN ("cancelled")
             ORDER BY p.is_featured DESC, p.updated_at DESC, p.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    private static function projectStats(): array
    {
        $row = self::fetchOne(
            'SELECT COUNT(*) AS total_projects,
                    COALESCE(SUM(units), 0) AS total_units,
                    COALESCE(ROUND(AVG(pct_complete)), 0) AS average_completion,
                    COALESCE(SUM(contract_sum), 0) AS contract_value
             FROM projects
             WHERE status NOT IN ("cancelled")'
        );

        return [
            'total_projects' => (int)($row['total_projects'] ?? 0),
            'total_units' => (int)($row['total_units'] ?? 0),
            'average_completion' => (int)($row['average_completion'] ?? 0),
            'contract_value' => (float)($row['contract_value'] ?? 0),
        ];
    }

    private static function constituencies(): array
    {
        try {
            return Constituency::publicList();
        } catch (Throwable) {
            return [];
        }
    }

    private static function news(int $limit = 12): array
    {
        return self::fetchAll(
            'SELECT n.id, n.title, n.slug, n.excerpt, n.read_time, n.post_format, n.is_featured,
                    n.published_at, n.seo_title, n.seo_description, n.updated_at,
                    c.name AS category_name, c.slug AS category_slug, c.color AS category_color,
                    m.url AS featured_image_url, m.alt_text AS featured_image_alt
             FROM news_articles n
             LEFT JOIN news_categories c ON c.id = n.category_id
             LEFT JOIN media_library m ON m.id = n.featured_image_id
             WHERE n.status = "published"
               AND n.is_visible = 1
               AND n.deleted_at IS NULL
               AND (n.published_at IS NULL OR n.published_at <= NOW())
             ORDER BY n.is_featured DESC, n.published_at DESC, n.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    private static function newsCategories(): array
    {
        return self::fetchAll(
            'SELECT c.id, c.name, c.slug, c.description, c.color, COUNT(n.id) AS article_count
             FROM news_categories c
             LEFT JOIN news_articles n ON n.category_id = c.id
                AND n.status = "published"
                AND n.is_visible = 1
                AND n.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY c.name ASC'
        );
    }

    private static function announcements(int $limit = 8): array
    {
        return self::fetchAll(
            'SELECT id,
                    COALESCE(NULLIF(ticker_text, ""), title) AS title,
                    body,
                    "official" AS type,
                    "normal" AS priority,
                    0 AS is_pinned,
                    "Read notice" AS cta_label,
                    ticker_url AS cta_url,
                    published_at,
                    ticker_expires_at AS expires_at,
                    updated_at
             FROM news_articles
             WHERE status = "published"
               AND deleted_at IS NULL
               AND show_in_ticker = 1
               AND (published_at IS NULL OR published_at <= NOW())
               AND (ticker_expires_at IS NULL OR ticker_expires_at >= NOW())
             ORDER BY ticker_priority DESC, published_at DESC, id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    private static function gallery(int $limit = 18): array
    {
        return self::fetchAll(
            'SELECT g.id, g.title, g.caption, g.alt_text, g.credit, g.taken_at, g.location,
                    g.media_type, g.video_url, g.duration, g.external_url, g.is_featured,
                    g.is_highlight, g.updated_at,
                    c.name AS category_name, c.slug AS category_slug,
                    p.name AS project_name, p.slug AS project_slug,
                    co.name AS constituency_name, co.slug AS constituency_slug,
                    m.url AS media_url, m.width, m.height, m.type AS file_type,
                    tm.url AS thumbnail_url
             FROM gallery_images g
             LEFT JOIN gallery_categories c ON c.id = g.category_id
             LEFT JOIN projects p ON p.id = g.project_id
             LEFT JOIN constituencies co ON co.id = g.constituency_id
             LEFT JOIN media_library m ON m.id = g.image_id
             LEFT JOIN media_library tm ON tm.id = g.thumbnail_media_id
             WHERE g.status = "published"
             ORDER BY g.is_featured DESC, g.sort_order ASC, g.taken_at DESC, g.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    private static function contentVersion(string $slug): string
    {
        $row = self::fetchOne(
            'SELECT GREATEST(
                COALESCE(MAX(cp.updated_at), "1970-01-01"),
                COALESCE(MAX(cs.updated_at), "1970-01-01"),
                COALESCE((SELECT MAX(updated_at) FROM cms_settings), "1970-01-01")
             ) AS version_at
             FROM cms_pages cp
             LEFT JOIN cms_sections cs ON cs.page_id = cp.id
             WHERE cp.slug = ?',
            [$slug]
        );

        return (string)($row['version_at'] ?? '');
    }

    private static function fetchAll(string $sql, array $params = []): array
    {
        try {
            return Database::fetchAll($sql, $params);
        } catch (Throwable) {
            return [];
        }
    }

    private static function fetchOne(string $sql, array $params = []): array
    {
        try {
            $row = Database::fetch($sql, $params);
            return is_array($row) ? $row : [];
        } catch (Throwable) {
            return [];
        }
    }
}
