<?php

class Migration_087_NewsArticleTemplateCms
{
    public function up(\PDO $pdo): void
    {
        $pdo->prepare("
            INSERT INTO cms_pages
                (slug, template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                template = VALUES(template),
                route_path = VALUES(route_path),
                status = VALUES(status),
                seo_title = VALUES(seo_title),
                seo_description = VALUES(seo_description),
                seo_keywords = VALUES(seo_keywords),
                canonical_url = VALUES(canonical_url)
        ")->execute([
            'news-article',
            'template',
            'news-article.php',
            'published',
            'News Article | Trans-Nzoia County AHP Tracker',
            'Read official Trans-Nzoia Affordable Housing Programme news, announcements, reports and updates.',
            'Trans-Nzoia housing news, affordable housing, AHP reports',
            'https://housing.transnzoia.go.ke/news-article.php',
        ]);

        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'news-article' LIMIT 1")->fetchColumn();
        if ($pageId <= 0) {
            return;
        }

        $defaults = [
            ['news_article_labels', 'Article Labels', 'news_article_labels', 10, [
                'news_breadcrumb_label' => 'News & Updates',
                'default_read_time' => '4 min',
                'author_fallback' => 'Trans-Nzoia County Department of Land, Housing & Physical Planning',
                'view_all_news_label' => 'View all news',
            ]],
            ['news_article_sidebar', 'Sidebar Labels', 'news_article_sidebar', 20, [
                'details_title' => 'Article Details',
                'category_label' => 'Category',
                'format_label' => 'Format',
                'published_label' => 'Published',
                'source_label' => 'Source',
                'related_title' => 'Related Stories',
                'related_empty_text' => 'No related stories are available yet.',
            ]],
            ['news_article_downloads', 'Downloads & Links', 'news_article_downloads', 30, [
                'download_label' => 'Download Report',
                'external_label' => 'Open Source Link',
            ]],
            ['news_article_not_found', 'Not Found', 'news_article_not_found', 40, [
                'title' => 'Article not found',
                'text' => 'The article may be unpublished, archived or no longer available.',
                'button_label' => 'Back to News',
            ]],
        ];

        $section = $pdo->prepare("
            INSERT INTO cms_sections
                (page_id, section_key, label, section_type, editor_mode, sort_order, is_visible, is_locked, content_json)
            VALUES
                (?, ?, ?, ?, ?, ?, 1, 1, ?)
            ON DUPLICATE KEY UPDATE
                label = VALUES(label),
                section_type = VALUES(section_type),
                editor_mode = VALUES(editor_mode),
                sort_order = VALUES(sort_order),
                is_visible = VALUES(is_visible),
                is_locked = VALUES(is_locked)
        ");

        foreach ($defaults as [$key, $label, $type, $sort, $content]) {
            $section->execute([$pageId, $key, $label, $type, $type, $sort, json_encode($content, JSON_UNESCAPED_SLASHES)]);
        }

        $pdo->prepare("
            DELETE FROM cms_sections
            WHERE page_id = ?
              AND section_key IN ('template_labels', 'sidebar_facts', 'related_intro')
        ")->execute([$pageId]);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'news-article' LIMIT 1")->fetchColumn();
        if ($pageId > 0) {
            $pdo->prepare("
                DELETE FROM cms_sections
                WHERE page_id = ?
                  AND section_key IN ('news_article_labels', 'news_article_sidebar', 'news_article_downloads', 'news_article_not_found')
            ")->execute([$pageId]);
        }
    }
}
