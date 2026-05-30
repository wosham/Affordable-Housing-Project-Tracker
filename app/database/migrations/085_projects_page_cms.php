<?php

class Migration_085_ProjectsPageCms
{
    public function up(\PDO $pdo): void
    {
        $pdo->prepare("
            INSERT INTO cms_pages
                (slug, template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url, hero_image)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                template = VALUES(template),
                route_path = VALUES(route_path),
                status = VALUES(status),
                seo_title = VALUES(seo_title),
                seo_description = VALUES(seo_description),
                seo_keywords = VALUES(seo_keywords),
                canonical_url = VALUES(canonical_url),
                hero_image = VALUES(hero_image)
        ")->execute([
            'projects',
            'listing',
            'projects.php',
            'published',
            'All Projects | Trans-Nzoia County Affordable Housing Tracker',
            'Browse all affordable housing projects in Trans-Nzoia County with live construction progress, contractor details and unit counts across all 5 constituencies.',
            'Trans-Nzoia affordable housing projects, AHP Kenya, Maili Tatu estate, Matunda estate, Saboti housing, Cherangany housing',
            'https://housing.transnzoia.go.ke/projects.php',
            'uploads/gallery/maili-tatu-2.jpg',
        ]);

        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'projects' LIMIT 1")->fetchColumn();
        if ($pageId <= 0) {
            return;
        }

        $defaults = [
            ['projects_hero', 'Projects Hero', 'projects_hero', 10, [
                'background_image' => 'uploads/gallery/maili-tatu-2.jpg',
                'background_alt' => 'Affordable housing construction site in Trans-Nzoia County',
                'eyebrow' => 'AHP Projects',
                'title_plain' => 'Housing Projects',
                'title_highlight' => 'Directory',
                'subtitle' => 'Track every affordable housing project across Trans-Nzoia County - construction progress, contractor details, timelines and unit counts in real time.',
                'total_projects_label' => 'Total Projects',
                'units_label' => 'Units Planned',
                'active_label' => 'Active',
                'constituencies_label' => 'Constituencies',
            ]],
            ['projects_filters', 'Project Filters', 'projects_filters', 20, [
                'search_placeholder' => 'Search projects...',
                'status_label' => 'Status:',
                'all_label' => 'All',
                'active_label' => 'Active',
                'planning_label' => 'Planning',
                'constituency_label' => 'Constituency:',
                'sort_label' => 'Sort:',
                'sort_completion_desc' => 'Completion high to low',
                'sort_completion_asc' => 'Completion low to high',
                'sort_units_desc' => 'Most units',
                'sort_name_asc' => 'Name A-Z',
                'reset_label' => 'Clear filters',
            ]],
            ['projects_listing', 'Project Listing', 'projects_listing', 30, [
                'results_prefix' => 'Showing',
                'project_single' => 'project',
                'project_plural' => 'projects',
                'units_label' => 'Units',
                'complete_label' => 'Complete',
                'view_label' => 'View Project',
                'pagination_showing_label' => 'Showing',
                'pagination_of_label' => 'of',
                'empty_title' => 'No projects found',
                'empty_text' => 'Try adjusting your filters or search term.',
                'empty_reset_label' => 'Clear all filters',
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
              AND section_key IN ('hero', 'stats_strip', 'listing_intro')
        ")->execute([$pageId]);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'projects' LIMIT 1")->fetchColumn();
        if ($pageId > 0) {
            $pdo->prepare("
                DELETE FROM cms_sections
                WHERE page_id = ?
                  AND section_key IN ('projects_hero', 'projects_filters', 'projects_listing')
            ")->execute([$pageId]);
        }
    }
}
