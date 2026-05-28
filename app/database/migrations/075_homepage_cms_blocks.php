<?php

class Migration_075_HomepageCmsBlocks
{
    public function up(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);

        $this->section($pdo, $pageId, 'home_hero', 'Homepage Hero', 'home_hero', 10, [
            'eyebrow' => 'Ongoing Projects - Trans-Nzoia County',
            'title' => "Affordable Housing\nProject Tracker",
            'subtitle' => 'Transparent, real-time monitoring of housing construction delivery across all five constituencies of Trans-Nzoia County.',
            'background_image' => 'uploads/heroes/hero-main.jpg',
            'background_alt' => 'Affordable housing construction site in Trans-Nzoia County, Kenya',
            'primary_label' => 'View All Projects',
            'primary_url' => 'projects.php',
            'primary_icon' => 'fa-table-cells-large',
            'secondary_label' => 'By Constituency',
            'secondary_url' => 'constituencies.php',
            'secondary_icon' => 'fa-map-location-dot',
            'show_kpis' => '1',
        ]);

        $this->section($pdo, $pageId, 'featured_projects', 'Featured Projects Block', 'dynamic_projects', 20, [
            'title' => 'Featured Projects',
            'subtitle' => 'Track ongoing affordable housing construction across Trans-Nzoia County. View real-time progress, milestones, and unit delivery status for each project.',
            'button_label' => 'Full Portfolio',
            'button_url' => 'projects.php',
            'display_count' => '3',
            'empty_title' => 'Featured projects will appear here',
            'empty_text' => 'Mark projects as featured in the project editor to populate this section.',
        ]);

        $this->section($pdo, $pageId, 'constituency_coverage', 'Constituency Coverage Block', 'dynamic_constituencies', 30, [
            'title' => 'Coverage by Constituency',
            'subtitle' => 'All five constituencies are part of the Trans-Nzoia AHP. Click any constituency to view live project data.',
            'button_label' => 'All Constituencies',
            'button_url' => 'constituencies.php',
            'panel_hint' => 'Click a constituency on the map to see project data',
            'legend_active' => 'Active',
            'legend_planning' => 'Planning',
            'legend_site' => 'Project site',
        ]);

        $this->section($pdo, $pageId, 'ground_reports', 'From the Ground Block', 'dynamic_news', 40, [
            'title' => 'From the Ground',
            'subtitle' => 'Project milestones, tender notices, and official clearances - directly from Trans-Nzoia County AHP sites.',
            'button_label' => 'All Reports',
            'button_url' => 'news.php',
            'alerts_title' => 'Latest Alerts',
            'feature_count' => '1',
            'compact_count' => '2',
            'alert_count' => '6',
            'empty_title' => 'Reports will appear here',
            'empty_text' => 'Published news and alerts will populate this live section.',
        ]);

        $this->section($pdo, $pageId, 'ecitizen_cta', 'Application CTA', 'home_cta', 50, [
            'background_image' => 'uploads/site-photos/maili-tatu-2.jpg',
            'logo_image' => 'uploads/logos/BomaYanguLogo.png',
            'eyebrow' => 'Boma Yangu - eCitizen',
            'title' => "Your Home.\nApplied Online.",
            'subtitle' => 'Register on the national Boma Yangu portal via eCitizen, save your deposit, and choose your preferred constituency in Trans-Nzoia County.',
            'primary_label' => 'Apply via eCitizen',
            'primary_url' => 'https://ecitizen.go.ke',
            'secondary_label' => 'About the programme',
            'secondary_url' => 'about.php',
            'step_1_icon' => 'fa-user-plus',
            'step_1_title' => 'Create eCitizen Account',
            'step_1_body' => 'Register at ecitizen.go.ke with your National ID or Passport.',
            'step_2_icon' => 'fa-house-circle-check',
            'step_2_title' => 'Register on Boma Yangu',
            'step_2_body' => 'Complete your housing application profile on the portal.',
            'step_3_icon' => 'fa-piggy-bank',
            'step_3_title' => 'Save Your Deposit',
            'step_3_body' => 'Min. KES 1,000/month to the Boma Yangu savings account.',
            'step_4_icon' => 'fa-map-location-dot',
            'step_4_title' => 'Select Trans-Nzoia',
            'step_4_body' => 'Choose your preferred constituency as your allocation preference.',
        ]);

        $stmt = $pdo->prepare("
            UPDATE cms_pages
            SET template = 'landing',
                route_path = 'index.php',
                status = 'published',
                seo_title = ?,
                seo_description = ?,
                seo_keywords = ?,
                canonical_url = ?,
                hero_image = ?
            WHERE id = ?
        ");
        $stmt->execute([
            'Trans-Nzoia County | Affordable Housing Project Tracker',
            'Trans-Nzoia County Affordable Housing Project Tracker - real-time monitoring of housing construction delivery across Saboti, Cherangany, Kwanza, Endebess and Kiminini constituencies.',
            'Trans-Nzoia affordable housing, AHP Kenya, county housing tracker, construction progress Kenya',
            'https://housing.transnzoia.go.ke/',
            'uploads/heroes/hero-main.jpg',
            $pageId,
        ]);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);
        $pdo->prepare("DELETE FROM cms_sections WHERE page_id = ? AND section_key IN ('home_hero','featured_projects','constituency_coverage','ground_reports','ecitizen_cta')")
            ->execute([$pageId]);
    }

    private function pageId(\PDO $pdo): int
    {
        $pdo->prepare("
            INSERT INTO cms_pages (slug, template, route_path, status, seo_title)
            VALUES ('home', 'landing', 'index.php', 'published', 'Home | AHPTC')
            ON DUPLICATE KEY UPDATE template = 'landing', route_path = 'index.php'
        ")->execute();

        $stmt = $pdo->prepare("SELECT id FROM cms_pages WHERE slug = 'home' LIMIT 1");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function section(\PDO $pdo, int $pageId, string $key, string $label, string $type, int $sort, array $content): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO cms_sections (page_id, section_key, label, section_type, sort_order, editor_mode, is_visible, is_locked, content_json)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)
            ON DUPLICATE KEY UPDATE
                label = VALUES(label),
                section_type = VALUES(section_type),
                sort_order = VALUES(sort_order),
                editor_mode = VALUES(editor_mode),
                is_visible = VALUES(is_visible),
                is_locked = VALUES(is_locked),
                content_json = VALUES(content_json)
        ");

        $stmt->execute([
            $pageId,
            $key,
            $label,
            $type,
            $sort,
            $type,
            json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
