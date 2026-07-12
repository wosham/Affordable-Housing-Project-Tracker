<?php

class SitemapBuilder
{
    private const EXCLUDED_ROUTES = [
        '404.php',
        '500.php',
        'maintenance.php',
        'offline.php',
        'admin/login.php',
        'admin/index.php',
    ];

    private const CORE_PAGES = [
        ['label' => 'Home', 'path' => '', 'group' => 'Main Pages', 'description' => 'Programme overview and live public updates.', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['label' => 'Projects', 'path' => 'projects.php', 'group' => 'Main Pages', 'description' => 'Public project listing and progress overview.', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['label' => 'Constituencies', 'path' => 'constituencies.php', 'group' => 'Main Pages', 'description' => 'Housing delivery by constituency.', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['label' => 'News', 'path' => 'news.php', 'group' => 'Main Pages', 'description' => 'Published news and public announcements.', 'priority' => '0.8', 'changefreq' => 'daily'],
        ['label' => 'Gallery', 'path' => 'gallery.php', 'group' => 'Main Pages', 'description' => 'Project photos and public media.', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['label' => 'Search', 'path' => 'search.php', 'group' => 'Main Pages', 'description' => 'Sitewide public search across projects, news, FAQs, gallery media, and constituencies.', 'priority' => '0.6', 'changefreq' => 'weekly'],
        ['label' => 'About', 'path' => 'about.php', 'group' => 'Programme', 'description' => 'Programme background and objectives.', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['label' => 'FAQ', 'path' => 'faq.php', 'group' => 'Programme', 'description' => 'Frequently asked public questions.', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['label' => 'Leadership', 'path' => 'leadership.php', 'group' => 'Programme', 'description' => 'County and programme leadership.', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['label' => 'Stakeholders', 'path' => 'stakeholders.php', 'group' => 'Programme', 'description' => 'Programme partners and stakeholders.', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['label' => 'Contact', 'path' => 'contact.php', 'group' => 'Programme', 'description' => 'Public contact form and office contacts.', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ['label' => 'Privacy Policy', 'path' => 'legal/privacy.php', 'group' => 'Legal', 'description' => 'Privacy and personal data policy.', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ['label' => 'Terms of Use', 'path' => 'legal/terms.php', 'group' => 'Legal', 'description' => 'Website terms and conditions.', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ['label' => 'Disclaimer', 'path' => 'legal/disclaimer.php', 'group' => 'Legal', 'description' => 'Public information disclaimer.', 'priority' => '0.4', 'changefreq' => 'yearly'],
        ['label' => 'Site Map', 'path' => 'sitemap.php', 'group' => 'Utility', 'description' => 'Human-readable website directory.', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ];

    public static function entries(): array
    {
        $entries = self::coreEntries();

        try {
            $entries = array_merge(
                $entries,
                self::cmsEntries(),
                self::projectEntries(),
                self::constituencyEntries(),
                self::newsEntries()
            );
        } catch (Throwable) {
            // Keep public sitemap available even when the database is temporarily unavailable.
        }

        return self::dedupe($entries);
    }

    public static function grouped(): array
    {
        $groups = [];

        foreach (self::entries() as $entry) {
            $group = (string)($entry['group'] ?? 'Other');
            $groups[$group][] = $entry;
        }

        $order = ['Main Pages', 'Programme', 'Projects', 'Constituencies', 'News', 'Legal', 'Utility'];
        uksort($groups, static function (string $a, string $b) use ($order): int {
            $posA = array_search($a, $order, true);
            $posB = array_search($b, $order, true);
            return ($posA === false ? 99 : $posA) <=> ($posB === false ? 99 : $posB) ?: strcmp($a, $b);
        });

        return $groups;
    }

    public static function xml(): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach (self::entries() as $entry) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . self::xmlEscape((string)$entry['loc']) . '</loc>';
            if (!empty($entry['lastmod'])) {
                $xml[] = '    <lastmod>' . self::xmlEscape((string)$entry['lastmod']) . '</lastmod>';
            }
            $xml[] = '    <changefreq>' . self::xmlEscape((string)($entry['changefreq'] ?? 'monthly')) . '</changefreq>';
            $xml[] = '    <priority>' . self::xmlEscape((string)($entry['priority'] ?? '0.5')) . '</priority>';
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml) . "\n";
    }

    public static function robots(): string
    {
        $config = $GLOBALS['app_config'] ?? [];
        $env = strtolower((string)($config['env'] ?? getenv('APP_ENV') ?: 'local'));
        $sitemap = Url::canonical('sitemap.xml');

        if ($env !== 'production') {
            return implode("\n", [
                'User-agent: *',
                'Disallow: /',
                '',
                'Sitemap: ' . $sitemap,
                '',
            ]);
        }

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            '',
            'Disallow: /admin/',
            'Disallow: /api/',
            'Disallow: /app/',
            'Disallow: /auth/',
            'Disallow: /vendor/',
            'Disallow: /node_modules/',
            'Disallow: /frontend/',
            'Disallow: /secure-uploads/',
            'Disallow: /.git/',
            'Disallow: /.env',
            'Disallow: /composer.json',
            'Disallow: /composer.lock',
            'Disallow: /package.json',
            'Disallow: /package-lock.json',
            'Disallow: /404.php',
            'Disallow: /500.php',
            'Disallow: /maintenance.php',
            'Disallow: /offline.php',
            'Disallow: /search.php?*',
            'Disallow: /*?*q=',
            'Disallow: /*?*search=',
            'Disallow: /*?*filter=',
            '',
            'Sitemap: ' . $sitemap,
            '',
        ]);
    }

    private static function coreEntries(): array
    {
        return array_map(static fn (array $entry): array => self::entry($entry), self::CORE_PAGES);
    }

    private static function cmsEntries(): array
    {
        $rows = Database::fetchAll(
            "SELECT slug, route_path, template, seo_title, updated_at
             FROM cms_pages
             WHERE status = 'published'
               AND route_path IS NOT NULL
               AND TRIM(route_path) <> ''
             ORDER BY FIELD(template, 'landing','content','listing','media','contact','legal','system'), route_path ASC"
        );

        $entries = [];
        foreach ($rows as $row) {
            $path = self::cleanRoute((string)($row['route_path'] ?? ''));
            if (!self::isIndexablePath($path)) {
                continue;
            }

            $entries[] = self::entry([
                'label' => self::labelFromPage($row),
                'path' => $path === 'index.php' ? '' : $path,
                'group' => self::groupForTemplate((string)($row['template'] ?? '')),
                'description' => 'Published public page.',
                'priority' => self::priorityForPath($path),
                'changefreq' => self::changefreqForPath($path),
                'lastmod' => self::dateOnly($row['updated_at'] ?? null),
            ]);
        }

        return $entries;
    }

    private static function projectEntries(): array
    {
        $rows = Project::publicListing([], 0, 0);
        $entries = [];

        foreach ($rows as $row) {
            $slug = trim((string)($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $entries[] = self::entry([
                'label' => (string)($row['name'] ?? status_label($slug)),
                'path' => 'project-detail.php?id=' . rawurlencode($slug),
                'group' => 'Projects',
                'description' => (string)($row['description'] ?? 'Project detail and progress page.'),
                'priority' => in_array((string)($row['status'] ?? ''), ['active', 'completed'], true) ? '0.8' : '0.65',
                'changefreq' => 'weekly',
                'lastmod' => self::dateOnly($row['updated_at'] ?? null),
            ]);
        }

        return $entries;
    }

    private static function constituencyEntries(): array
    {
        $rows = Constituency::publicList();
        $entries = [];

        foreach ($rows as $row) {
            $slug = trim((string)($row['slug'] ?? $row['id'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $entries[] = self::entry([
                'label' => (string)($row['name'] ?? status_label($slug)),
                'path' => 'constituency-detail.php?id=' . rawurlencode($slug),
                'group' => 'Constituencies',
                'description' => 'Constituency housing delivery detail.',
                'priority' => '0.75',
                'changefreq' => 'weekly',
                'lastmod' => self::dateOnly($row['updated_at'] ?? null),
            ]);
        }

        return $entries;
    }

    private static function newsEntries(): array
    {
        $rows = Database::fetchAll(
            "SELECT slug, title, excerpt, published_at, updated_at
             FROM news_articles
             WHERE status = 'published'
               AND COALESCE(is_visible, 1) = 1
               AND deleted_at IS NULL
               AND (published_at IS NULL OR published_at <= NOW())
             ORDER BY COALESCE(published_at, updated_at) DESC, id DESC"
        );
        $entries = [];

        foreach ($rows as $row) {
            $slug = trim((string)($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $entries[] = self::entry([
                'label' => (string)($row['title'] ?? status_label($slug)),
                'path' => 'news-article.php?id=' . rawurlencode($slug),
                'group' => 'News',
                'description' => (string)($row['excerpt'] ?? 'Published news article.'),
                'priority' => '0.65',
                'changefreq' => self::isRecent($row['published_at'] ?? $row['updated_at'] ?? null) ? 'weekly' : 'monthly',
                'lastmod' => self::dateOnly($row['updated_at'] ?? $row['published_at'] ?? null),
            ]);
        }

        return $entries;
    }

    private static function entry(array $data): array
    {
        $path = Url::normalizePath((string)($data['path'] ?? ''));

        return [
            'label' => trim((string)($data['label'] ?? 'Untitled')) ?: 'Untitled',
            'path' => $path,
            'url' => Url::to($path),
            'loc' => Url::canonical($path),
            'group' => (string)($data['group'] ?? 'Other'),
            'description' => trim(strip_tags((string)($data['description'] ?? ''))),
            'priority' => (string)($data['priority'] ?? '0.5'),
            'changefreq' => (string)($data['changefreq'] ?? 'monthly'),
            'lastmod' => self::dateOnly($data['lastmod'] ?? null),
        ];
    }

    private static function dedupe(array $entries): array
    {
        $seen = [];
        $deduped = [];

        foreach ($entries as $entry) {
            $key = (string)($entry['loc'] ?? '');
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $entry;
        }

        return $deduped;
    }

    private static function cleanRoute(string $path): string
    {
        return Url::normalizePath(preg_replace('#/+#', '/', $path) ?? $path);
    }

    private static function isIndexablePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (in_array($path, self::EXCLUDED_ROUTES, true)) {
            return false;
        }

        if (str_starts_with($path, 'admin/') || str_starts_with($path, 'api/') || str_starts_with($path, 'app/')) {
            return false;
        }

        if (in_array($path, ['project-detail.php', 'news-article.php', 'constituency-detail.php'], true)) {
            return false;
        }

        return str_ends_with($path, '.php') || $path === 'sitemap.xml';
    }

    private static function groupForTemplate(string $template): string
    {
        return match ($template) {
            'legal' => 'Legal',
            'system' => 'Utility',
            'landing', 'listing', 'media' => 'Main Pages',
            default => 'Programme',
        };
    }

    private static function labelFromPage(array $row): string
    {
        $title = trim((string)($row['seo_title'] ?? ''));
        if ($title !== '') {
            return trim((string)preg_replace('/\s*\|\s*.*/', '', $title));
        }

        $slug = (string)($row['slug'] ?? $row['route_path'] ?? 'Page');
        return status_label(str_replace(['-', '_'], ' ', $slug));
    }

    private static function priorityForPath(string $path): string
    {
        return match ($path) {
            'index.php' => '1.0',
            'projects.php', 'constituencies.php' => '0.9',
            'news.php' => '0.8',
            'gallery.php', 'about.php', 'faq.php' => '0.7',
            'sitemap.php' => '0.5',
            default => str_starts_with($path, 'legal/') ? '0.4' : '0.6',
        };
    }

    private static function changefreqForPath(string $path): string
    {
        return match ($path) {
            'index.php', 'news.php' => 'daily',
            'projects.php', 'constituencies.php', 'gallery.php' => 'weekly',
            default => str_starts_with($path, 'legal/') ? 'yearly' : 'monthly',
        };
    }

    private static function dateOnly(mixed $value): string
    {
        if ($value === null || trim((string)$value) === '') {
            return date('Y-m-d');
        }

        $timestamp = strtotime((string)$value);
        return $timestamp === false ? date('Y-m-d') : date('Y-m-d', $timestamp);
    }

    private static function isRecent(mixed $value): bool
    {
        $timestamp = strtotime((string)$value);
        return $timestamp !== false && $timestamp >= strtotime('-30 days');
    }

    private static function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
