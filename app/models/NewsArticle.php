<?php

class NewsArticle extends Model
{
    protected static string $table = 'news_articles';

    public const FORMATS = [
        'article' => ['label' => 'News Article', 'icon' => 'fa-newspaper', 'hint' => 'Standard story with images and full body copy.'],
        'announcement' => ['label' => 'Official Public Announcement', 'icon' => 'fa-bullhorn', 'hint' => 'Public-facing county or programme notice used by the website news page and ticker.'],
        'progress_report' => ['label' => 'Progress Report', 'icon' => 'fa-chart-line', 'hint' => 'Construction or programme progress update.'],
        'field_report' => ['label' => 'Field Report', 'icon' => 'fa-map-location-dot', 'hint' => 'Site visit report, inspection note or field observation.'],
        'policy' => ['label' => 'Policy / Allocation Update', 'icon' => 'fa-file-contract', 'hint' => 'Policy, allocation, levy or eligibility update.'],
        'pdf_report' => ['label' => 'PDF Report / Download', 'icon' => 'fa-file-pdf', 'hint' => 'Downloadable report with a short article summary.'],
    ];

    public const STATUSES = ['draft', 'published', 'scheduled', 'archived'];

    public static function published(int $limit = 10): array
    {
        return self::withRelations(['status' => 'published'], $limit);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            self::selectSql() . ' WHERE na.slug = ? AND na.status = "published" AND COALESCE(na.is_visible, 1) = 1 AND na.deleted_at IS NULL LIMIT 1',
            [$slug]
        );
    }

    public static function featured(): ?array
    {
        return Database::fetch(
            self::selectSql() . ' WHERE na.status = "published" AND COALESCE(na.is_visible, 1) = 1 AND na.deleted_at IS NULL AND COALESCE(na.is_featured, 0) = 1 ORDER BY na.published_at DESC, na.id DESC LIMIT 1'
        );
    }

    public static function withRelations(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        $where = ['na.status = ?', 'COALESCE(na.is_visible, 1) = 1', 'na.deleted_at IS NULL'];
        $bindings = [$filters['status'] ?? 'published'];

        if (!empty($filters['category'])) {
            $where[] = 'nc.slug = ?';
            $bindings[] = (string)$filters['category'];
        }

        if (isset($filters['featured'])) {
            $where[] = 'COALESCE(na.is_featured, 0) = ?';
            $bindings[] = (int)$filters['featured'];
        }

        if (!empty($filters['exclude_featured'])) {
            $where[] = 'COALESCE(na.is_featured, 0) = 0';
        }

        $limitSql = $limit > 0 ? ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, $offset) : '';

        return Database::fetchAll(
            self::selectSql() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY na.published_at DESC, na.id DESC' . $limitSql,
            $bindings
        );
    }

    public static function relatedFor(array $article, int $limit = 4): array
    {
        $articleId = (int)($article['id'] ?? 0);
        $categorySlug = (string)($article['category_slug'] ?? '');
        $related = [];

        if ($categorySlug !== '') {
            $related = self::withRelations(['category' => $categorySlug], $limit + 1);
        }

        $related = array_values(array_filter(
            $related,
            static fn (array $item): bool => (int)($item['id'] ?? 0) !== $articleId
        ));

        if (count($related) < $limit) {
            $fallback = self::withRelations([], $limit + 4);
            foreach ($fallback as $item) {
                if ((int)($item['id'] ?? 0) === $articleId) {
                    continue;
                }
                if (in_array((int)$item['id'], array_map(static fn (array $row): int => (int)$row['id'], $related), true)) {
                    continue;
                }
                $related[] = $item;
                if (count($related) >= $limit) {
                    break;
                }
            }
        }

        return array_slice($related, 0, $limit);
    }

    public static function publicCategories(): array
    {
        return Database::fetchAll("
            SELECT
                nc.*,
                COUNT(na.id) AS published_count
            FROM news_categories nc
            INNER JOIN news_articles na ON na.category_id = nc.id
                AND na.status = 'published'
                AND COALESCE(na.is_visible, 1) = 1
                AND na.deleted_at IS NULL
            GROUP BY nc.id
            ORDER BY nc.sort_order ASC, nc.name ASC
        ");
    }

    public static function publicStats(): array
    {
        $row = Database::fetch("
            SELECT
                COUNT(*) AS total_articles,
                COUNT(DISTINCT category_id) AS total_categories,
                MAX(published_at) AS last_published_at
            FROM news_articles
            WHERE status = 'published'
              AND COALESCE(is_visible, 1) = 1
              AND deleted_at IS NULL
        ") ?: [];

        return [
            'total_articles' => (int)($row['total_articles'] ?? 0),
            'total_categories' => (int)($row['total_categories'] ?? 0),
            'last_published_at' => $row['last_published_at'] ?? null,
        ];
    }

    public static function adminList(array $filters = [], int $limit = 12, int $offset = 0): array
    {
        [$where, $bindings] = self::adminFilterSql($filters);
        return Database::fetchAll(
            self::selectSql() . $where . ' ORDER BY na.updated_at DESC, na.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $bindings
        );
    }

    public static function adminCount(array $filters = []): int
    {
        [$where, $bindings] = self::adminFilterSql($filters);
        $row = Database::fetch(
            'SELECT COUNT(*) AS total FROM news_articles na LEFT JOIN news_categories nc ON nc.id = na.category_id ' . $where,
            $bindings
        );
        return (int)($row['total'] ?? 0);
    }

    public static function adminStats(): array
    {
        $row = Database::fetch("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'published') AS published,
                SUM(status = 'draft') AS drafts,
                SUM(status = 'scheduled') AS scheduled,
                SUM(status = 'archived') AS archived,
                SUM(COALESCE(is_featured, 0) = 1) AS featured,
                SUM(COALESCE(show_in_ticker, 0) = 1) AS ticker
            FROM news_articles
            WHERE deleted_at IS NULL
        ") ?: [];

        return array_map(static fn ($value): int => (int)$value, $row);
    }

    public static function findAdmin(int $id): ?array
    {
        return Database::fetch(self::selectSql() . ' WHERE na.id = ? AND na.deleted_at IS NULL LIMIT 1', [$id]);
    }

    public static function categories(): array
    {
        return Database::fetchAll('SELECT * FROM news_categories ORDER BY sort_order ASC, name ASC');
    }

    public static function tagNames(int $articleId): array
    {
        $rows = Database::fetchAll("
            SELECT nt.name
            FROM news_tags nt
            INNER JOIN news_article_tags nat ON nat.tag_id = nt.id
            WHERE nat.article_id = ?
            ORDER BY nt.name ASC
        ", [$articleId]);

        return array_map(static fn (array $row): string => (string)$row['name'], $rows);
    }

    public static function mediaOptions(string $type = ''): array
    {
        $filters = [];
        if ($type !== '') {
            $filters['type'] = $type;
        }
        return MediaLibrary::query($filters, 200);
    }

    public static function formatLabel(?string $format): string
    {
        return self::FORMATS[$format ?? '']['label'] ?? status_label($format ?: 'article');
    }

    public static function formatIcon(?string $format): string
    {
        return self::FORMATS[$format ?? '']['icon'] ?? 'fa-newspaper';
    }

    public static function saveFromAdmin(array $input, ?int $id = null): int
    {
        $title = trim((string)($input['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('Post title is required.');
        }

        $status = self::normaliseStatus((string)($input['status'] ?? 'draft'));
        $format = self::normaliseFormat((string)($input['post_format'] ?? 'article'));
        $slug = self::uniqueSlug((string)($input['slug'] ?? $title), $id);
        $publishedAt = self::dateOrNull((string)($input['published_at'] ?? ''));
        $scheduledFor = self::dateOrNull((string)($input['scheduled_for'] ?? ''));
        $externalUrl = trim((string)($input['external_url'] ?? ''));
        if ($externalUrl !== '' && !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('External URL must be a valid URL.');
        }
        $tickerUrl = trim((string)($input['ticker_url'] ?? ''));
        if ($tickerUrl !== '' && !filter_var($tickerUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Ticker supporting URL must be a valid URL.');
        }

        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        if ($status === 'scheduled' && $scheduledFor === null) {
            throw new RuntimeException('Scheduled posts need a scheduled date and time.');
        }

        $data = [
            'post_format' => $format,
            'category_id' => self::nullableInt($input['category_id'] ?? null),
            'author_id' => (int)(Auth::id() ?: ($input['author_id'] ?? 1)),
            'title' => $title,
            'slug' => $slug,
            'excerpt' => trim((string)($input['excerpt'] ?? '')),
            'read_time' => trim((string)($input['read_time'] ?? '')),
            'body' => self::cleanBodyHtml((string)($input['body'] ?? '')),
            'featured_image_id' => self::nullableInt($input['featured_image_id'] ?? null),
            'image_caption' => trim((string)($input['image_caption'] ?? '')),
            'inline_image_id' => self::nullableInt($input['inline_image_id'] ?? null),
            'attachment_id' => self::nullableInt($input['attachment_id'] ?? null),
            'external_url' => $externalUrl,
            'status' => $status,
            'is_featured' => ($format !== 'announcement' && !empty($input['is_featured'])) ? 1 : 0,
            'is_visible' => ($format === 'announcement') ? 0 : (!empty($input['is_visible']) ? 1 : 0),
            'show_in_ticker' => !empty($input['show_in_ticker']) || $format === 'announcement' ? 1 : 0,
            'ticker_text' => self::nullableText($input['ticker_text'] ?? null),
            'ticker_url' => self::nullableText($tickerUrl),
            'ticker_expires_at' => self::dateOrNull((string)($input['ticker_expires_at'] ?? '')),
            'ticker_priority' => self::normaliseTickerPriority($input['ticker_priority'] ?? 0),
            'published_at' => $publishedAt,
            'scheduled_for' => $scheduledFor,
            'seo_title' => trim((string)($input['seo_title'] ?? '')),
            'seo_description' => trim((string)($input['seo_description'] ?? '')),
            'og_image_id' => self::nullableInt($input['og_image_id'] ?? null),
            'metadata_json' => json_encode([
                'editor_notes' => trim((string)($input['editor_notes'] ?? '')),
                'source_label' => trim((string)($input['source_label'] ?? '')),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];

        if ($data['body'] === '') {
            $fallback = $data['excerpt'] !== '' ? $data['excerpt'] : $title;
            $data['body'] = '<p>' . Security::e($fallback) . '</p>';
        }

        if ($data['excerpt'] === '') {
            $data['excerpt'] = substr(trim((string)preg_replace('/\s+/', ' ', strip_tags($data['body']))), 0, 220);
        }

        if ($data['read_time'] === '') {
            $wordCount = str_word_count(strip_tags($data['body']));
            $data['read_time'] = max(1, (int)ceil($wordCount / 220)) . ' min read';
        }

        if ((int)$data['show_in_ticker'] === 1 && (string)$data['ticker_text'] === '') {
            $data['ticker_text'] = $title;
        }

        if (in_array($status, ['published', 'scheduled'], true) && $data['category_id'] === null) {
            throw new RuntimeException('Published and scheduled posts need a category.');
        }

        if ($format === 'pdf_report' && in_array($status, ['published', 'scheduled'], true) && $data['attachment_id'] === null) {
            throw new RuntimeException('PDF report posts need a PDF/report attachment.');
        }

        if ($id) {
            self::update($id, $data);
            $articleId = $id;
        } else {
            $articleId = (int)self::create($data);
        }

        self::syncTags($articleId, (string)($input['tags'] ?? ''));
        return $articleId;
    }

    public static function quickStatus(int $id, string $status): void
    {
        $status = self::normaliseStatus($status);
        $article = self::findAdmin($id);
        if (!$article) {
            throw new RuntimeException('News post could not be found.');
        }

        $isAnnouncement = (string)($article['post_format'] ?? '') === 'announcement';
        $data = ['status' => $status];
        if ($status === 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
            $data['is_visible'] = $isAnnouncement ? 0 : 1;
            if ($isAnnouncement) {
                $data['is_featured'] = 0;
                $data['show_in_ticker'] = 1;
                $data['ticker_text'] = $article['ticker_text'] ?: $article['title'];
            }
        }
        if ($status === 'archived') {
            $data['is_visible'] = 0;
        }
        self::update($id, $data);
    }

    public static function toggleFeatured(int $id): void
    {
        $article = self::findAdmin($id);
        if (!$article || (string)($article['post_format'] ?? '') === 'announcement') {
            throw new RuntimeException('Official public announcements cannot be featured stories.');
        }

        Database::query('UPDATE news_articles SET is_featured = 1 - COALESCE(is_featured, 0) WHERE id = ?', [$id]);
    }

    public static function softDelete(int $id): void
    {
        self::update($id, ['deleted_at' => date('Y-m-d H:i:s'), 'status' => 'archived', 'is_visible' => 0]);
    }

    private static function adminFilterSql(array $filters): array
    {
        $where = ['na.deleted_at IS NULL'];
        $bindings = [];

        foreach (['status', 'post_format'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = 'na.' . $field . ' = ?';
                $bindings[] = (string)$filters[$field];
            }
        }

        if (!empty($filters['category'])) {
            $where[] = 'nc.slug = ?';
            $bindings[] = (string)$filters['category'];
        }

        if (!empty($filters['featured'])) {
            $where[] = 'COALESCE(na.is_featured, 0) = 1';
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(na.title LIKE ? OR na.excerpt LIKE ? OR na.slug LIKE ?)';
            $term = '%' . $q . '%';
            array_push($bindings, $term, $term, $term);
        }

        return [' WHERE ' . implode(' AND ', $where), $bindings];
    }

    private static function normaliseStatus(string $status): string
    {
        return in_array($status, self::STATUSES, true) ? $status : 'draft';
    }

    private static function normaliseFormat(string $format): string
    {
        return array_key_exists($format, self::FORMATS) ? $format : 'article';
    }

    private static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
        $base = $base !== '' ? $base : 'news-post';
        $slug = $base;
        $i = 2;

        while (true) {
            $bindings = [$slug];
            $sql = 'SELECT id FROM news_articles WHERE slug = ?';
            if ($ignoreId) {
                $sql .= ' AND id <> ?';
                $bindings[] = $ignoreId;
            }
            if (!Database::fetch($sql . ' LIMIT 1', $bindings)) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    private static function dateOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private static function nullableInt(mixed $value): ?int
    {
        $int = (int)$value;
        return $int > 0 ? $int : null;
    }

    private static function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private static function normaliseTickerPriority(mixed $value): int
    {
        return max(0, min(100, (int)$value));
    }

    private static function syncTags(int $articleId, string $tags): void
    {
        Database::query('DELETE FROM news_article_tags WHERE article_id = ?', [$articleId]);
        $names = array_filter(array_unique(array_map(
            static fn (string $tag): string => trim($tag),
            preg_split('/[,;\n]+/', $tags) ?: []
        )));

        foreach ($names as $name) {
            $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
            if ($slug === '') {
                continue;
            }

            Database::query('INSERT IGNORE INTO news_tags (name, slug) VALUES (?, ?)', [$name, $slug]);
            $tag = Database::fetch('SELECT id FROM news_tags WHERE slug = ? LIMIT 1', [$slug]);
            if ($tag) {
                Database::query(
                    'INSERT IGNORE INTO news_article_tags (article_id, tag_id) VALUES (?, ?)',
                    [$articleId, (int)$tag['id']]
                );
            }
        }
    }

    private static function cleanBodyHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? '';
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? '';
        $html = preg_replace('/\son[a-z]+\s*=\s*(["\']).*?\1/is', '', $html) ?? '';
        $html = preg_replace('/\s(href|src)\s*=\s*(["\'])\s*javascript:.*?\2/is', '', $html) ?? '';

        return strip_tags($html, '<p><br><strong><b><em><i><u><s><a><ul><ol><li><blockquote><h2><h3><h4><figure><figcaption><img><table><thead><tbody><tr><th><td>');
    }

    private static function selectSql(): string
    {
        return "
            SELECT
                na.*,
                nc.name AS category_name,
                nc.slug AS category_slug,
                nc.color AS category_color,
                u.first_name AS author_first_name,
                u.last_name AS author_last_name,
                media.path AS featured_image_path,
                media.url AS featured_image_url,
                media.alt_text AS featured_image_alt,
                attach.path AS attachment_path,
                attach.url AS attachment_url,
                attach.title AS attachment_title,
                og.path AS og_image_path,
                og.url AS og_image_url
            FROM news_articles na
            LEFT JOIN news_categories nc ON nc.id = na.category_id
            LEFT JOIN users u ON u.id = na.author_id
            LEFT JOIN media_library media ON media.id = na.featured_image_id
            LEFT JOIN media_library attach ON attach.id = na.attachment_id
            LEFT JOIN media_library og ON og.id = na.og_image_id
        ";
    }
}
