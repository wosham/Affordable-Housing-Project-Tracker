<?php

class Migration_154_PublicFrontendContentSeed
{
    private array $pages = [
        ['home', 'index.php', 'Home'],
        ['about', 'about.php', 'About'],
        ['projects', 'projects.php', 'Projects'],
        ['project-detail', 'project-detail.php', 'Project Detail'],
        ['constituencies', 'constituencies.php', 'Constituencies'],
        ['constituency-detail', 'constituency-detail.php', 'Constituency Detail'],
        ['news', 'news.php', 'News'],
        ['news-article', 'news-article.php', 'News Article'],
        ['gallery', 'gallery.php', 'Gallery'],
        ['faq', 'faq.php', 'FAQ'],
        ['leadership', 'leadership.php', 'Leadership'],
        ['stakeholders', 'stakeholders.php', 'Stakeholders'],
        ['contact', 'contact.php', 'Contact'],
        ['sitemap', 'sitemap.php', 'Sitemap'],
        ['not-found', '404.php', '404 Error'],
        ['server-error', '500.php', '500 Error'],
        ['maintenance', 'maintenance.php', 'Maintenance'],
        ['offline', 'offline.php', 'Offline'],
        ['privacy', 'legal/privacy.php', 'Privacy Policy'],
        ['terms', 'legal/terms.php', 'Terms of Use'],
        ['disclaimer', 'legal/disclaimer.php', 'Disclaimer'],
    ];

    public function up(\PDO $pdo): void
    {
        $this->createSnapshotTable($pdo);
        $this->ensureCmsColumns($pdo);

        $root = dirname(__DIR__, 3);
        foreach ($this->pages as [$slug, $filePath, $label]) {
            $absolutePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
            if (!is_file($absolutePath)) {
                continue;
            }

            $source = (string)file_get_contents($absolutePath);
            $snapshot = $this->snapshot($slug, $filePath, $label, $source);
            $this->upsertSnapshot($pdo, $snapshot);
            $pageId = $this->upsertCmsPage($pdo, $snapshot);
            $this->insertCmsSnapshotSection($pdo, $pageId, $snapshot);
        }

        $this->seedFrontendContentIndex($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS public_frontend_content_snapshots');
    }

    private function createSnapshotTable(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS public_frontend_content_snapshots (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_slug       VARCHAR(120) NOT NULL UNIQUE,
            page_label      VARCHAR(180) NOT NULL,
            file_path       VARCHAR(255) NOT NULL,
            source_hash     CHAR(64) NOT NULL,
            raw_source      LONGTEXT NOT NULL,
            extracted_text  LONGTEXT NULL,
            extracted_json  LONGTEXT NULL,
            seeded_at       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_public_frontend_content_file (file_path),
            KEY idx_public_frontend_content_hash (source_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    private function ensureCmsColumns(\PDO $pdo): void
    {
        $this->column($pdo, 'cms_pages', 'route_path', "VARCHAR(255) NULL AFTER slug");
        $this->column($pdo, 'cms_pages', 'template', "VARCHAR(80) NULL AFTER route_path");
        $this->column($pdo, 'cms_pages', 'hero_image', "VARCHAR(255) NULL AFTER canonical_url");
        $this->column($pdo, 'cms_sections', 'section_type', "VARCHAR(80) NULL AFTER label");
        $this->column($pdo, 'cms_sections', 'editor_mode', "VARCHAR(80) NULL AFTER section_type");
        $this->column($pdo, 'cms_sections', 'sort_order', "SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER editor_mode");
        $this->column($pdo, 'cms_sections', 'is_locked', "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_visible");
        $this->column($pdo, 'cms_sections', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER updated_by");
    }

    private function snapshot(string $slug, string $filePath, string $label, string $source): array
    {
        $metadata = $this->extractMetadata($source);
        $text = $this->extractReadableText($source);

        return [
            'page_slug' => $slug,
            'page_label' => $label,
            'file_path' => $filePath,
            'source_hash' => hash('sha256', $source),
            'raw_source' => $source,
            'extracted_text' => $text,
            'extracted_json' => json_encode([
                'page_slug' => $slug,
                'page_label' => $label,
                'file_path' => $filePath,
                'source_hash' => hash('sha256', $source),
                'title' => $metadata['title'],
                'meta_description' => $metadata['meta_description'],
                'headings' => $metadata['headings'],
                'links' => $metadata['links'],
                'images' => $metadata['images'],
                'forms' => $metadata['forms'],
                'cms_keys' => $metadata['cms_keys'],
                'text_content' => $text,
                'raw_source' => $source,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    private function upsertSnapshot(\PDO $pdo, array $snapshot): void
    {
        $stmt = $pdo->prepare("INSERT INTO public_frontend_content_snapshots
            (page_slug, page_label, file_path, source_hash, raw_source, extracted_text, extracted_json)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                page_label = VALUES(page_label),
                file_path = VALUES(file_path),
                source_hash = VALUES(source_hash),
                raw_source = VALUES(raw_source),
                extracted_text = VALUES(extracted_text),
                extracted_json = VALUES(extracted_json)");
        $stmt->execute([
            $snapshot['page_slug'],
            $snapshot['page_label'],
            $snapshot['file_path'],
            $snapshot['source_hash'],
            $snapshot['raw_source'],
            $snapshot['extracted_text'],
            $snapshot['extracted_json'],
        ]);
    }

    private function upsertCmsPage(\PDO $pdo, array $snapshot): int
    {
        $json = json_decode((string)$snapshot['extracted_json'], true) ?: [];
        $seoTitle = $json['title'] ?: $snapshot['page_label'] . ' | Trans-Nzoia Affordable Housing Programme';
        $description = $json['meta_description'] ?: $this->summary((string)$snapshot['extracted_text']);
        $canonical = $snapshot['file_path'];

        $stmt = $pdo->prepare("INSERT INTO cms_pages
            (slug, route_path, template, status, seo_title, seo_description, canonical_url)
            VALUES (?, ?, 'public_page_snapshot', 'published', ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                route_path = COALESCE(route_path, VALUES(route_path)),
                template = COALESCE(template, VALUES(template)),
                status = status,
                seo_title = COALESCE(seo_title, VALUES(seo_title)),
                seo_description = COALESCE(seo_description, VALUES(seo_description)),
                canonical_url = COALESCE(canonical_url, VALUES(canonical_url))");
        $stmt->execute([$snapshot['page_slug'], $snapshot['file_path'], $seoTitle, $description, $canonical]);

        $select = $pdo->prepare('SELECT id FROM cms_pages WHERE slug = ? LIMIT 1');
        $select->execute([$snapshot['page_slug']]);
        return (int)$select->fetchColumn();
    }

    private function insertCmsSnapshotSection(\PDO $pdo, int $pageId, array $snapshot): void
    {
        $content = json_decode((string)$snapshot['extracted_json'], true) ?: [];
        $content['note'] = 'Snapshot of the current public frontend file. Frontend rendering has not been changed yet.';

        $stmt = $pdo->prepare('SELECT id FROM cms_sections WHERE page_id = ? AND section_key = ? LIMIT 1');
        $stmt->execute([$pageId, 'current_frontend_snapshot']);
        if ($stmt->fetchColumn()) {
            return;
        }

        $insert = $pdo->prepare("INSERT INTO cms_sections
            (page_id, section_key, label, section_type, editor_mode, sort_order, is_visible, is_locked, content_json)
            VALUES (?, 'current_frontend_snapshot', ?, 'frontend_snapshot', 'json', 1, 1, 1, ?)");
        $insert->execute([$pageId, $snapshot['page_label'] . ' - Current Frontend Snapshot', $this->json($content)]);
    }

    private function seedFrontendContentIndex(\PDO $pdo): void
    {
        $value = [];
        foreach ($this->pages as [$slug, $filePath, $label]) {
            $value[] = ['slug' => $slug, 'file_path' => $filePath, 'label' => $label];
        }

        $stmt = $pdo->prepare("INSERT INTO cms_settings (`key`, value, type, label, `group`)
            VALUES ('public_frontend_content_index', ?, 'json', 'Public frontend content index', 'frontend')
            ON DUPLICATE KEY UPDATE value = VALUES(value), type = 'json', label = VALUES(label), `group` = 'frontend'");
        $stmt->execute([$this->json($value)]);
    }

    private function extractMetadata(string $source): array
    {
        $html = preg_replace('/<\?php[\s\S]*?\?>/m', ' ', $source) ?? $source;

        preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $title);
        preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/is', $html, $description);

        $headings = [];
        if (preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $text = $this->cleanText($match[2]);
                if ($text !== '') {
                    $headings[] = ['level' => (int)$match[1], 'text' => $text];
                }
            }
        }

        $links = [];
        if (preg_match_all('/<a\b[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $href = trim($match[1]);
                $text = $this->cleanText($match[2]);
                if ($href !== '' || $text !== '') {
                    $links[] = ['href' => $href, 'text' => $text];
                }
            }
        }

        $images = [];
        if (preg_match_all('/<img\b[^>]*>/is', $html, $matches)) {
            foreach ($matches[0] as $tag) {
                preg_match('/\bsrc=["\']([^"\']*)["\']/i', $tag, $src);
                preg_match('/\balt=["\']([^"\']*)["\']/i', $tag, $alt);
                $images[] = ['src' => $src[1] ?? '', 'alt' => $alt[1] ?? ''];
            }
        }

        $forms = [];
        if (preg_match_all('/<form\b[^>]*>/is', $html, $matches)) {
            foreach ($matches[0] as $tag) {
                preg_match('/\baction=["\']([^"\']*)["\']/i', $tag, $action);
                preg_match('/\bmethod=["\']([^"\']*)["\']/i', $tag, $method);
                $forms[] = ['action' => $action[1] ?? '', 'method' => strtoupper($method[1] ?? 'GET')];
            }
        }

        $cmsKeys = [];
        if (preg_match_all('/CmsLoader::content\([^,]+,\s*[\'"]([^\'"]+)[\'"]/i', $source, $matches)) {
            $cmsKeys = array_values(array_unique($matches[1]));
        }

        return [
            'title' => isset($title[1]) ? $this->cleanText($title[1]) : '',
            'meta_description' => isset($description[1]) ? html_entity_decode($description[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '',
            'headings' => $headings,
            'links' => $links,
            'images' => $images,
            'forms' => $forms,
            'cms_keys' => $cmsKeys,
        ];
    }

    private function extractReadableText(string $source): string
    {
        $withoutPhp = preg_replace('/<\?php[\s\S]*?\?>/m', ' ', $source) ?? $source;
        $withoutScripts = preg_replace('/<(script|style)\b[\s\S]*?<\/\1>/i', ' ', $withoutPhp) ?? $withoutPhp;
        return $this->cleanText($withoutScripts);
    }

    private function cleanText(string $value): string
    {
        $value = preg_replace('/<[^>]+>/', ' ', $value) ?? $value;
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return trim($value);
    }

    private function summary(string $text): string
    {
        if (mb_strlen($text) <= 250) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, 247)) . '...';
    }

    private function column(\PDO $pdo, string $table, string $column, string $definition): void
    {
        if (in_array($column, $this->tableColumns($pdo, $table), true)) {
            return;
        }

        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }

    private function tableColumns(\PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        return array_map(static fn (array $row): string => $row['Field'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
