<?php

class Migration_155_PublicCmsDataContract
{
    public function up(\PDO $pdo): void
    {
        $contract = [
            'home' => [
                'route' => 'index.php',
                'cms_sections' => ['hero', 'impact_stats', 'featured_projects', 'constituency_overview', 'latest_news', 'gallery_preview', 'call_to_action'],
                'models' => ['Project', 'Constituency', 'NewsArticle', 'Announcement', 'CmsSetting'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'about' => [
                'route' => 'about.php',
                'cms_sections' => ['hero', 'programme_overview', 'mandate', 'delivery_model', 'partners', 'timeline'],
                'models' => ['CmsSetting'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'projects' => [
                'route' => 'projects.php',
                'cms_sections' => ['hero', 'filters_intro', 'project_listing_intro'],
                'models' => ['Project', 'Constituency'],
                'fallback' => 'database_empty_state',
            ],
            'project-detail' => [
                'route' => 'project-detail.php',
                'cms_sections' => ['detail_intro', 'progress_context', 'contact_panel'],
                'models' => ['Project', 'ProgrammeTask', 'IPC'],
                'fallback' => 'database_empty_state',
            ],
            'constituencies' => [
                'route' => 'constituencies.php',
                'cms_sections' => ['hero', 'map_intro', 'constituency_listing_intro'],
                'models' => ['Constituency', 'Project'],
                'fallback' => 'database_empty_state',
            ],
            'constituency-detail' => [
                'route' => 'constituency-detail.php',
                'cms_sections' => ['detail_intro', 'project_summary', 'local_contacts'],
                'models' => ['Constituency', 'Project'],
                'fallback' => 'database_empty_state',
            ],
            'news' => [
                'route' => 'news.php',
                'cms_sections' => ['hero', 'category_intro', 'newsletter_cta'],
                'models' => ['NewsArticle', 'NewsCategory', 'Subscriber'],
                'fallback' => 'database_empty_state',
            ],
            'news-article' => [
                'route' => 'news-article.php',
                'cms_sections' => ['article_shell', 'related_news'],
                'models' => ['NewsArticle', 'NewsCategory'],
                'fallback' => 'database_empty_state',
            ],
            'gallery' => [
                'route' => 'gallery.php',
                'cms_sections' => ['hero', 'gallery_intro', 'upload_guidance'],
                'models' => ['MediaLibrary', 'Project'],
                'fallback' => 'database_empty_state',
            ],
            'faq' => [
                'route' => 'faq.php',
                'cms_sections' => ['hero', 'faq_groups', 'contact_cta'],
                'models' => ['CmsPage'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'leadership' => [
                'route' => 'leadership.php',
                'cms_sections' => ['hero', 'leadership_listing', 'governance_note'],
                'models' => ['CmsPage'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'stakeholders' => [
                'route' => 'stakeholders.php',
                'cms_sections' => ['hero', 'stakeholder_groups', 'partner_note'],
                'models' => ['CmsPage'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'contact' => [
                'route' => 'contact.php',
                'cms_sections' => ['hero', 'contact_cards', 'office_hours', 'contact_form_intro'],
                'models' => ['CmsSetting'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'sitemap' => [
                'route' => 'sitemap.php',
                'cms_sections' => ['hero', 'link_groups'],
                'models' => ['CmsPage'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'privacy' => [
                'route' => 'legal/privacy.php',
                'cms_sections' => ['legal_document'],
                'models' => ['CmsPage'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'terms' => [
                'route' => 'legal/terms.php',
                'cms_sections' => ['legal_document'],
                'models' => ['CmsPage'],
                'fallback' => 'current_frontend_snapshot',
            ],
            'disclaimer' => [
                'route' => 'legal/disclaimer.php',
                'cms_sections' => ['legal_document'],
                'models' => ['CmsPage'],
                'fallback' => 'current_frontend_snapshot',
            ],
        ];

        $this->upsertSetting(
            $pdo,
            'frontend_data_contract',
            json_encode($contract, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'json',
            'Public CMS/data contract',
            'frontend'
        );
        $this->upsertSetting($pdo, 'frontend_static_data_status', 'retired', 'text', 'Static frontend data status', 'frontend');
        $this->upsertSetting($pdo, 'frontend_data_contract_version', '2026-06-07', 'text', 'Public data contract version', 'frontend');
    }

    public function down(\PDO $pdo): void
    {
        $stmt = $pdo->prepare('DELETE FROM cms_settings WHERE `key` IN (?, ?, ?)');
        $stmt->execute(['frontend_data_contract', 'frontend_static_data_status', 'frontend_data_contract_version']);
    }

    private function upsertSetting(\PDO $pdo, string $key, string $value, string $type, string $label, string $group): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO cms_settings (`key`, value, type, label, `group`, updated_by)
             VALUES (?, ?, ?, ?, ?, NULL)
             ON DUPLICATE KEY UPDATE value = VALUES(value), type = VALUES(type), label = VALUES(label), `group` = VALUES(`group`)'
        );
        $stmt->execute([$key, $value, $type, $label, $group]);
    }
}
