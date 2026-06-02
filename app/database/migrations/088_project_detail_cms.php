<?php

class Migration_088_ProjectDetailCms
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
            'project-detail',
            'template',
            'project-detail.php',
            'published',
            'Project Detail | Trans-Nzoia County Affordable Housing Tracker',
            'Detailed construction progress, contractor information, milestones and site photos for affordable housing projects in Trans-Nzoia County.',
            'Trans-Nzoia AHP project detail, affordable housing Kenya, construction progress',
            'https://housing.transnzoia.go.ke/project-detail.php',
        ]);

        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'project-detail' LIMIT 1")->fetchColumn();
        if ($pageId <= 0) {
            return;
        }

        $defaults = [
            ['project_detail_labels', 'Template Labels', 'project_detail_labels', 10, [
                'projects_breadcrumb_label' => 'Projects',
                'active_status_label' => 'Active',
                'planning_status_label' => 'Planning',
                'completed_status_label' => 'Completed',
                'watch_status_label' => 'On Hold',
                'started_label' => 'Started',
                'delivery_label' => 'Est. Delivery',
                'construction_complete_label' => 'Construction Complete',
                'units_label' => 'Units Planned',
                'ward_label' => 'Ward',
                'contractor_label' => 'Contractor',
                'funding_label' => 'Funding Source',
                'start_date_label' => 'Start Date',
                'est_delivery_label' => 'Est. Delivery',
            ]],
            ['project_detail_overview', 'Overview, Progress & Media', 'project_detail_overview', 20, [
                'section_label' => 'About This Project',
                'title' => 'Project Overview',
                'lead_agency_label' => 'Lead Agency',
                'site_engineer_label' => 'Site Engineer',
                'funding_label' => 'Funding',
                'current_activity_label' => 'Current Activity',
                'progress_label' => 'Construction Progress',
                'progress_title' => 'Live Progress Tracker',
                'overall_completion_label' => 'Overall Completion',
                'progress_note' => 'Data updated regularly by the Trans-Nzoia County Housing Department.',
                'units_in_progress_label' => 'Units In Progress',
                'target_delivery_label' => 'Target Delivery',
                'timeline_label' => 'Key Milestones',
                'timeline_title' => 'Construction Timeline',
                'timeline_empty_text' => 'Milestones will appear after they are added to this project.',
                'gallery_label' => 'Site Photography',
                'gallery_title' => 'Photo Gallery',
                'gallery_empty_text' => 'Site photography will be added as construction progresses.',
            ]],
            ['project_detail_sidebar', 'Sidebar', 'project_detail_sidebar', 30, [
                'contractor_title' => 'Contractor',
                'contractor_role_label' => 'Principal Contractor',
                'project_info_title' => 'Project Info',
                'constituency_label' => 'Constituency',
                'status_label' => 'Status',
                'target_units_label' => 'Target Units',
                'completion_label' => 'Completion',
                'related_title' => 'Related',
                'constituency_link_suffix' => 'Constituency',
                'all_projects_prefix' => 'All',
                'back_projects_label' => 'Back to All Projects',
            ]],
            ['project_detail_apply_cta', 'Application CTA', 'project_detail_apply_cta', 40, [
                'title' => 'Interested in a Unit?',
                'subtitle' => 'Register on the national Boma Yangu portal to apply for affordable housing in Trans-Nzoia County.',
                'button_label' => 'Apply on Boma Yangu',
                'button_url' => 'https://app.bomayangu.go.ke',
            ]],
            ['project_detail_not_found', 'Not Found', 'project_detail_not_found', 50, [
                'title' => 'Project Not Found',
                'text' => "The project you're looking for doesn't exist or the URL is incorrect.",
                'button_label' => 'Back to All Projects',
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
              AND section_key IN ('template_labels', 'sidebar_cta', 'related_links')
        ")->execute([$pageId]);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'project-detail' LIMIT 1")->fetchColumn();
        if ($pageId > 0) {
            $pdo->prepare("
                DELETE FROM cms_sections
                WHERE page_id = ?
                  AND section_key IN ('project_detail_labels', 'project_detail_overview', 'project_detail_sidebar', 'project_detail_apply_cta', 'project_detail_not_found')
            ")->execute([$pageId]);
        }
    }
}
