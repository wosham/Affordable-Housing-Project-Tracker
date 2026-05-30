<?php

class Migration_086_ConstituencyDetailCms
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
            'constituency-detail',
            'template',
            'constituency-detail.php',
            'published',
            'Constituency Detail | Trans-Nzoia County Affordable Housing Tracker',
            'Detailed affordable housing information for Trans-Nzoia constituencies, including live projects, progress, wards and local facts.',
            'Trans-Nzoia constituency housing, AHP Kenya, affordable housing projects',
            'https://housing.transnzoia.go.ke/constituency-detail.php',
        ]);

        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'constituency-detail' LIMIT 1")->fetchColumn();
        if ($pageId <= 0) {
            return;
        }

        $defaults = [
            ['constituency_detail_labels', 'Template Labels', 'constituency_detail_labels', 10, [
                'active_status_label' => 'Active Construction',
                'planning_status_label' => 'Planning Stage',
                'projects_label' => 'Projects',
                'units_label' => 'Units Planned',
                'population_label' => 'Population',
                'completion_label' => 'Avg. Completion',
                'wards_label' => 'Wards',
                'projects_title_suffix' => 'Projects',
                'projects_subtitle' => 'All housing developments in this constituency under the national AHP programme.',
                'view_all_projects_label' => 'View all projects',
                'unit_card_label' => 'Units',
                'complete_card_label' => 'Complete',
                'view_project_label' => 'View Project',
                'empty_projects_text' => 'No projects listed yet for this constituency.',
                'progress_eyebrow' => 'Progress Overview',
                'progress_title_suffix' => 'Construction Progress',
                'progress_subtitle' => 'Across {projects} active {project_word}, {constituency} Constituency has delivered {units} units under the national AHP programme. Work is progressing across {wards} wards.',
                'total_units_label' => 'Total Units',
            ]],
            ['constituency_detail_facts', 'Facts & Location', 'constituency_detail_facts', 20, [
                'facts_title' => 'Constituency Facts',
                'county_label' => 'County',
                'population_label' => 'Population',
                'wards_label' => 'Wards',
                'lead_agency_label' => 'Lead Agency',
                'lead_agency_value' => 'State Dept. of Housing',
                'funding_label' => 'Funding',
                'funding_value' => 'National AHP Fund + County Budget',
                'programme_label' => 'Programme',
                'programme_value' => 'National Affordable Housing Programme',
                'location_title' => 'Location',
                'all_constituencies_label' => 'All Constituencies',
            ]],
            ['constituency_detail_related', 'Other Constituencies', 'constituency_detail_related', 30, [
                'title' => 'Other Constituencies',
                'subtitle' => 'Explore housing developments across Trans-Nzoia County.',
                'view_all_label' => 'View all',
                'explore_label' => 'Explore',
                'not_found_title' => 'Constituency Not Found',
                'not_found_text' => "The constituency you're looking for doesn't exist or the URL is incorrect.",
                'not_found_button' => 'Back to Constituencies',
            ]],
            ['constituency_detail_apply_cta', 'Application CTA', 'constituency_detail_apply_cta', 40, [
                'title' => 'Ready to Apply for Affordable Housing?',
                'subtitle' => 'Register on the national Boma Yangu portal to join the allocation list for this constituency.',
                'primary_label' => 'Apply on Boma Yangu',
                'primary_url' => 'https://bomayangu.go.ke',
                'secondary_label' => 'View All Projects',
                'secondary_url' => 'projects.php',
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
              AND section_key IN ('template_labels', 'apply_cta', 'related_links')
        ")->execute([$pageId]);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'constituency-detail' LIMIT 1")->fetchColumn();
        if ($pageId > 0) {
            $pdo->prepare("
                DELETE FROM cms_sections
                WHERE page_id = ?
                  AND section_key IN ('constituency_detail_labels', 'constituency_detail_facts', 'constituency_detail_related', 'constituency_detail_apply_cta')
            ")->execute([$pageId]);
        }
    }
}
