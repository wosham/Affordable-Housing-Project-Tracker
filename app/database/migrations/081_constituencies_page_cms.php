<?php

class Migration_081_ConstituenciesPageCms
{
    public function up(\PDO $pdo): void
    {
        $this->ensureConstituencyColumns($pdo);
        $this->seedConstituencies($pdo);
        $this->seedWards($pdo);
        $this->index($pdo, 'wards', 'uq_wards_constituency_slug', 'constituency_id, slug', true);
        $this->seedCmsSections($pdo);
        $this->retireLegacySections($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DELETE FROM cms_sections WHERE section_key IN ('constituencies_hero','constituencies_map','constituencies_progress','constituencies_grid','constituencies_apply_cta')");
    }

    private function ensureConstituencyColumns(\PDO $pdo): void
    {
        $this->column($pdo, 'constituencies', 'population', "INT UNSIGNED NULL AFTER description");
        $this->column($pdo, 'constituencies', 'avg_completion', "TINYINT UNSIGNED DEFAULT 0 AFTER total_projects");
        $this->column($pdo, 'constituencies', 'status', "ENUM('planning','active','completed') DEFAULT 'planning' AFTER avg_completion");
    }

    private function seedConstituencies(\PDO $pdo): void
    {
        $items = [
            ['Saboti', 'saboti', 165000, 1120, 2, 58, 'active', 'Saboti Constituency is home to the county headquarters, Kitale town, and hosts the flagship Maili Tatu AHP Estate - the largest affordable housing project in Trans-Nzoia County with 1,040 mixed-typology units.', 'uploads/gallery/maili-tatu-2.jpg'],
            ['Cherangany', 'cherangany', 152000, 200, 1, 35, 'active', 'Cherangany Constituency is the agricultural heartland of Trans-Nzoia. The Matunda AHP Estate serves farming communities along the Matunda-Sinyerere corridor.', 'uploads/gallery/matunda-ahp-1.jpg'],
            ['Endebess', 'endebess', 104000, 210, 2, 10, 'active', 'Endebess Constituency borders Uganda at the Suam crossing. Two active projects bring affordable housing to this strategic border region, including the Suam Border Post Estate.', 'uploads/gallery/suam-ahp.jpg'],
            ['Kiminini', 'kiminini', 138000, 120, 2, 5, 'planning', 'Kiminini Constituency has two housing projects in the planning and pre-construction stages, targeting delivery by 2028 through the National AHP Fund and County Development Fund.', null],
            ['Kwanza', 'kwanza', 112000, 80, 1, 8, 'planning', 'Kwanza Constituency housing project is in architectural design review, with a Q4 2026 construction start targeted - delivering 80 affordable units in Kwanza town.', null],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO constituencies (name, slug, population, total_units, total_projects, avg_completion, status, description, hero_image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name), population = VALUES(population),
                total_units = VALUES(total_units), total_projects = VALUES(total_projects),
                avg_completion = VALUES(avg_completion), status = VALUES(status),
                description = VALUES(description), hero_image = VALUES(hero_image)
        ");

        foreach ($items as $item) {
            $stmt->execute($item);
        }

        $pdo->prepare("DELETE FROM constituencies WHERE slug IN (?, ?)")->execute(['trans-nzoia-east', 'trans-nzoia-west']);
    }

    private function seedWards(\PDO $pdo): void
    {
        $official = [
            'saboti' => ['Matisi', 'Tuwan', 'Kinyoro', 'Bidii', 'Township'],
            'cherangany' => ['Matunda', 'Sinyerere', 'Kaplamai', 'Motosiet'],
            'endebess' => ['Endebess', 'Chepchoina', 'Kapkoi', 'Metkei'],
            'kiminini' => ['Kiminini', 'Waitaluk', 'Sikhendu', 'Hospital'],
            'kwanza' => ['Kwanza', 'Keiyo', 'Bidii', 'Kapomboi'],
        ];

        $ids = [];
        $rows = $pdo->query("SELECT id, slug FROM constituencies")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $ids[(string)$row['slug']] = (int)$row['id'];
        }

        foreach ($official as $slug => $wards) {
            if (empty($ids[$slug])) {
                continue;
            }

            $cid = $ids[$slug];
            $pdo->prepare("DELETE FROM wards WHERE constituency_id = ?")->execute([$cid]);

            $stmt = $pdo->prepare("
                INSERT INTO wards (constituency_id, name, slug)
                VALUES (?, ?, ?)
            ");

            foreach ($wards as $ward) {
                $stmt->execute([$cid, $ward, $this->slug($ward)]);
            }
        }
    }

    private function seedCmsSections(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);
        if ($pageId === 0) {
            return;
        }

        $sections = [
            ['constituencies_hero', 'Constituencies Hero', 'constituencies_hero', 10, [
                'background_image' => 'uploads/gallery/maili-tatu-3.jpg',
                'background_alt' => 'Affordable housing construction site in Trans-Nzoia County',
                'eyebrow' => 'Coverage Map',
                'title_prefix' => 'All',
                'title_highlight' => '5 Constituencies',
                'subtitle' => 'Explore how the Affordable Housing Programme reaches every corner of Trans-Nzoia County - from Endebess on the Uganda border to Kwanza in the south.',
                'constituencies_label' => 'Constituencies',
                'projects_label' => 'Projects',
                'units_label' => 'Units Planned',
                'residents_label' => 'Residents Served',
            ]],
            ['constituencies_map', 'Map & Side List', 'constituencies_map', 20, [
                'title' => 'Trans-Nzoia County',
                'subtitle' => 'Click a constituency to explore its housing projects',
                'active_label' => 'Active construction',
                'planning_label' => 'Planning stage',
                'selected_label' => 'Selected',
                'empty_text' => 'Constituency data will appear after projects and wards are added to the registry.',
            ]],
            ['constituencies_progress', 'County-Wide Progress', 'constituencies_progress', 30, [
                'title' => "County-Wide\nProgramme\nProgress",
                'subtitle' => 'Across all {constituencies} constituencies, the programme is delivering {units} affordable housing units - targeting Kenyans registered on the national Boma Yangu portal.',
                'button_label' => 'Browse All Projects',
                'button_url' => 'projects.php',
            ]],
            ['constituencies_grid', 'Browse Grid', 'constituencies_grid', 40, [
                'eyebrow' => 'Browse by Constituency',
                'title_prefix' => 'All',
                'title_highlight' => '5 Constituencies',
                'sort_label' => 'Sort:',
                'default_label' => 'Default',
                'progress_label' => 'Highest Progress',
                'units_label' => 'Most Units',
                'alpha_label' => 'A - Z',
            ]],
            ['constituencies_apply_cta', 'Application CTA', 'constituencies_apply_cta', 50, [
                'title' => 'Ready to Apply for Affordable Housing?',
                'subtitle' => 'Register on the national Boma Yangu portal to join the Trans-Nzoia County AHP allocation list.',
                'primary_label' => 'Apply on Boma Yangu',
                'primary_url' => 'https://bomayangu.go.ke',
                'secondary_label' => 'View All Projects',
                'secondary_url' => 'projects.php',
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
        $pageId = $this->pageId($pdo);
        if ($pageId === 0) {
            return;
        }

        $pdo->prepare("DELETE FROM cms_sections WHERE page_id = ? AND section_key IN ('hero','map_intro','county_progress','grid_intro')")
            ->execute([$pageId]);
    }

    private function pageId(\PDO $pdo): int
    {
        $pdo->prepare("
            INSERT INTO cms_pages (slug, template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url, hero_image)
            VALUES ('constituencies', 'listing', 'constituencies.php', 'published',
                'Constituencies | Trans-Nzoia County Affordable Housing Tracker',
                'Explore affordable housing coverage across all 5 constituencies in Trans-Nzoia County - Saboti, Cherangany, Endebess, Kiminini and Kwanza.',
                'Trans-Nzoia constituencies, Saboti housing, Cherangany AHP, Endebess housing, Kiminini housing, Kwanza housing',
                'https://housing.transnzoia.go.ke/constituencies.php',
                'uploads/gallery/maili-tatu-3.jpg')
            ON DUPLICATE KEY UPDATE template = VALUES(template), route_path = VALUES(route_path),
                seo_title = VALUES(seo_title), seo_description = VALUES(seo_description),
                seo_keywords = VALUES(seo_keywords), canonical_url = VALUES(canonical_url),
                hero_image = VALUES(hero_image)
        ")->execute();

        $stmt = $pdo->query("SELECT id FROM cms_pages WHERE slug = 'constituencies' LIMIT 1");
        return (int)($stmt->fetchColumn() ?: 0);
    }

    private function column(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function index(\PDO $pdo, string $table, string $index, string $columns, bool $unique = false): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            $kind = $unique ? 'UNIQUE' : 'INDEX';
            $pdo->exec("ALTER TABLE `{$table}` ADD {$kind} `{$index}` ({$columns})");
        }
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
        return $slug !== '' ? $slug : 'item-' . substr(sha1($value), 0, 8);
    }
}
