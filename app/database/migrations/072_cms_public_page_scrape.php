<?php

class Migration_072_CmsPublicPageScrape
{
    public function up(\PDO $pdo): void
    {
        $root = dirname(__DIR__, 3);
        $pages = [
            'home' => 'index.php',
            'about' => 'about.php',
            'projects' => 'projects.php',
            'project-detail' => 'project-detail.php',
            'constituencies' => 'constituencies.php',
            'constituency-detail' => 'constituency-detail.php',
            'news' => 'news.php',
            'news-article' => 'news-article.php',
            'gallery' => 'gallery.php',
            'faq' => 'faq.php',
            'leadership' => 'leadership.php',
            'stakeholders' => 'stakeholders.php',
            'contact' => 'contact.php',
            'sitemap' => 'sitemap.php',
            'privacy' => 'legal/privacy.php',
            'terms' => 'legal/terms.php',
            'disclaimer' => 'legal/disclaimer.php',
            'not-found' => '404.php',
            'server-error' => '500.php',
            'maintenance' => 'maintenance.php',
            'offline' => 'offline.php',
        ];

        $pageStmt = $pdo->prepare("
            INSERT INTO cms_pages (slug, template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url)
            VALUES (?, ?, ?, 'published', ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE route_path = VALUES(route_path), seo_title = COALESCE(cms_pages.seo_title, VALUES(seo_title)),
                seo_description = COALESCE(cms_pages.seo_description, VALUES(seo_description)),
                seo_keywords = COALESCE(cms_pages.seo_keywords, VALUES(seo_keywords)),
                canonical_url = COALESCE(cms_pages.canonical_url, VALUES(canonical_url))
        ");
        $pageIdStmt = $pdo->prepare("SELECT id FROM cms_pages WHERE slug = ? LIMIT 1");
        $snapshotStmt = $pdo->prepare("
            INSERT INTO cms_sections (page_id, section_key, label, section_type, sort_order, editor_mode, is_visible, content_json)
            VALUES (?, 'source_snapshot', 'Frontend Source Snapshot', 'source', 9999, 'reference', 1, ?)
            ON DUPLICATE KEY UPDATE content_json = VALUES(content_json), section_type = 'source', editor_mode = 'reference'
        ");
        $legalStmt = $pdo->prepare("
            INSERT INTO cms_sections (page_id, section_key, label, section_type, sort_order, editor_mode, is_visible, content_json)
            VALUES (?, 'legal_document', ?, 'document', 10, 'document', 1, ?)
            ON DUPLICATE KEY UPDATE label = VALUES(label), section_type = 'document', editor_mode = 'document'
        ");

        foreach ($pages as $slug => $file) {
            $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
            if (!is_file($path)) {
                continue;
            }

            $source = (string)file_get_contents($path);
            $meta = $this->extractPhpMeta($source);
            $template = in_array($slug, ['privacy', 'terms', 'disclaimer'], true) ? 'legal' : null;

            $pageStmt->execute([
                $slug,
                $template,
                $file,
                $meta['pageTitle'] ?? ucwords(str_replace('-', ' ', $slug)),
                $meta['pageDescription'] ?? null,
                $meta['pageKeywords'] ?? null,
                $meta['canonicalUrl'] ?? null,
            ]);

            $pageIdStmt->execute([$slug]);
            $pageId = (int)$pageIdStmt->fetchColumn();
            if ($pageId <= 0) {
                continue;
            }

            $snapshotStmt->execute([$pageId, json_encode([
                'source_file' => $file,
                'meta' => $meta,
                'headings' => $this->extractHeadings($source),
                'sections' => $this->extractSections($source),
                'images' => $this->extractImages($source),
                'links' => $this->extractLinks($source),
                'html_snapshot' => $source,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);

            if (in_array($slug, ['privacy', 'terms', 'disclaimer'], true)) {
                $article = $this->extractArticle($source);
                $title = $meta['pageTitle'] ?? ucwords(str_replace('-', ' ', $slug));
                $legalStmt->execute([$pageId, $title . ' Document', json_encode([
                    'title' => $title,
                    'body' => $article,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DELETE FROM cms_sections WHERE section_key = 'legal_document'");
    }

    private function extractPhpMeta(string $source): array
    {
        $meta = [];
        foreach (['pageTitle', 'pageDescription', 'pageKeywords', 'canonicalUrl', 'activePage'] as $key) {
            if (preg_match('/\\$' . preg_quote($key, '/') . '\\s*=\\s*([\\\'"])(.*?)\\1\\s*;/s', $source, $match)) {
                $meta[$key] = html_entity_decode($match[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }
        return $meta;
    }

    private function extractHeadings(string $source): array
    {
        preg_match_all('/<h([1-6])\\b[^>]*>(.*?)<\\/h\\1>/is', $source, $matches, PREG_SET_ORDER);
        return array_map(static fn (array $match): array => [
            'level' => (int)$match[1],
            'text' => trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
        ], $matches);
    }

    private function extractSections(string $source): array
    {
        preg_match_all('/<section\\b([^>]*)>/is', $source, $matches, PREG_SET_ORDER);
        $sections = [];
        foreach ($matches as $index => $match) {
            $attrs = $match[1];
            $sections[] = [
                'order' => $index + 1,
                'id' => $this->attr($attrs, 'id'),
                'class' => $this->attr($attrs, 'class'),
                'aria_label' => $this->attr($attrs, 'aria-label'),
                'labelled_by' => $this->attr($attrs, 'aria-labelledby'),
            ];
        }
        return $sections;
    }

    private function extractImages(string $source): array
    {
        preg_match_all('/<img\\b([^>]*)>/is', $source, $matches, PREG_SET_ORDER);
        $images = [];
        foreach ($matches as $match) {
            $attrs = $match[1];
            $src = $this->attr($attrs, 'src');
            if ($src === '') {
                continue;
            }
            $images[] = [
                'src' => $src,
                'alt' => $this->attr($attrs, 'alt'),
                'loading' => $this->attr($attrs, 'loading'),
            ];
        }
        return $images;
    }

    private function extractLinks(string $source): array
    {
        preg_match_all('/<a\\b([^>]*)>(.*?)<\\/a>/is', $source, $matches, PREG_SET_ORDER);
        $links = [];
        foreach ($matches as $match) {
            $href = $this->attr($match[1], 'href');
            if ($href === '') {
                continue;
            }
            $links[] = [
                'href' => $href,
                'text' => trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            ];
        }
        return array_slice($links, 0, 80);
    }

    private function extractArticle(string $source): string
    {
        if (preg_match('/<article\\b[^>]*class=([\\\'"])[^\\\'"]*lg-article[^\\\'"]*\\1[^>]*>(.*?)<\\/article>/is', $source, $match)) {
            return trim($match[2]);
        }
        return '';
    }

    private function attr(string $attrs, string $name): string
    {
        if (preg_match('/\\b' . preg_quote($name, '/') . '\\s*=\\s*([\\\'"])(.*?)\\1/is', $attrs, $match)) {
            return html_entity_decode($match[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return '';
    }
}
