<?php

class Migration_082_NewsPageCms
{
    public function up(\PDO $pdo): void
    {
        $this->seedCmsSections($pdo);
        $this->retireLegacySections($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo, false);
        if ($pageId === 0) {
            return;
        }

        $pdo->prepare("
            DELETE FROM cms_sections
            WHERE page_id = ?
              AND section_key IN ('news_hero','news_mosaic','news_featured','news_filters','news_listing','news_cta')
        ")->execute([$pageId]);
    }

    private function seedCmsSections(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);
        if ($pageId === 0) {
            return;
        }

        $sections = [
            ['news_hero', 'News Hero', 'news_hero', 10, [
                'eyebrow' => 'News & Updates',
                'title_plain_1' => 'Latest',
                'title_accent' => 'News &',
                'title_plain_2' => 'Announcements',
                'subtitle' => 'Official updates, progress reports, and community news from the Trans-Nzoia County Affordable Housing Programme.',
                'search_placeholder' => 'Search articles...',
                'articles_label' => 'Articles',
                'categories_label' => 'Categories',
                'last_updated_label' => 'Last Updated',
            ]],
            ['news_mosaic', 'Category Mosaic', 'news_mosaic', 20, [
                'show_mosaic' => '1',
                'programme_label' => 'Programme',
                'groundbreaking_label' => 'Groundbreaking',
                'construction_label' => 'Construction',
                'policy_label' => 'Policy',
                'community_label' => 'Community',
                'official_label' => 'Official',
                'field_reports_label' => 'Field Reports',
                'total_label' => "Total\nArticles",
            ]],
            ['news_featured', 'Featured Story', 'news_featured', 30, [
                'section_label' => 'Featured Story',
                'featured_badge' => 'Featured',
                'read_button_label' => 'Read Full Article',
                'empty_title' => 'Featured story coming soon',
                'empty_text' => 'Mark a published news post as featured to show it here.',
            ]],
            ['news_filters', 'Filters & Sorting', 'news_filters', 40, [
                'all_label' => 'All Articles',
                'results_suffix_single' => 'article',
                'results_suffix_plural' => 'articles',
                'latest_label' => 'Latest First',
                'oldest_label' => 'Oldest First',
            ]],
            ['news_listing', 'Article Listing', 'news_listing', 50, [
                'title' => 'All Articles',
                'subtitle' => 'Showing latest news & updates',
                'read_more_label' => 'Read More',
                'load_more_label' => 'Load More Articles',
                'empty_title' => 'No Articles Found',
                'empty_text' => 'No articles match your current search or filter. Try a different keyword or category.',
                'empty_reset_label' => 'Clear Filters',
            ]],
            ['news_cta', 'Stay Updated CTA', 'news_cta', 60, [
                'title_prefix' => 'Stay',
                'title_highlight' => 'Up to Date',
                'subtitle' => 'Follow the programme on social media or apply for housing directly through the eCitizen portal to receive official notifications about unit availability and beneficiary selection.',
                'x_url' => '#',
                'facebook_url' => '#',
                'youtube_url' => '#',
                'button_label' => 'Apply via eCitizen',
                'button_url' => 'https://ecitizen.go.ke',
            ]],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO cms_sections (page_id, section_key, label, section_type, sort_order, editor_mode, is_visible, is_locked, content_json)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)
            ON DUPLICATE KEY UPDATE label = VALUES(label), section_type = VALUES(section_type),
                sort_order = VALUES(sort_order), editor_mode = VALUES(editor_mode),
                is_visible = 1, is_locked = 1
        ");

        foreach ($sections as [$key, $label, $type, $sort, $content]) {
            $stmt->execute([$pageId, $key, $label, $type, $sort, $type, json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function retireLegacySections(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo, false);
        if ($pageId === 0) {
            return;
        }

        $pdo->prepare("
            DELETE FROM cms_sections
            WHERE page_id = ?
              AND section_key IN ('hero','featured_intro','listing_intro','newsletter_cta')
        ")->execute([$pageId]);
    }

    private function pageId(\PDO $pdo, bool $create = true): int
    {
        if ($create) {
            $pdo->prepare("
                INSERT INTO cms_pages (slug, template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url)
                VALUES ('news', 'listing', 'news.php', 'published',
                    'News & Updates | Trans-Nzoia County AHP Tracker',
                    'Latest news, official announcements, construction progress reports, and community updates from the Trans-Nzoia County Affordable Housing Programme.',
                    'Trans-Nzoia housing news, AHP announcements, affordable housing Kenya, county housing updates',
                    'https://housing.transnzoia.go.ke/news.php')
                ON DUPLICATE KEY UPDATE template = VALUES(template), route_path = VALUES(route_path),
                    seo_title = VALUES(seo_title), seo_description = VALUES(seo_description),
                    seo_keywords = VALUES(seo_keywords), canonical_url = VALUES(canonical_url)
            ")->execute();
        }

        $stmt = $pdo->query("SELECT id FROM cms_pages WHERE slug = 'news' LIMIT 1");
        return (int)($stmt->fetchColumn() ?: 0);
    }
}
