<?php

class Migration_078_LeadershipPageUpgrade
{
    public function up(\PDO $pdo): void
    {
        $this->column($pdo, 'leadership_profiles', 'slug', "VARCHAR(180) NULL AFTER id");
        $this->column($pdo, 'leadership_profiles', 'profile_type', "VARCHAR(60) NOT NULL DEFAULT 'national' AFTER slug");
        $this->column($pdo, 'leadership_profiles', 'parent_id', "INT UNSIGNED NULL AFTER profile_type");
        $this->column($pdo, 'leadership_profiles', 'tier', "SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER parent_id");
        $this->column($pdo, 'leadership_profiles', 'icon', "VARCHAR(80) NULL AFTER tier");
        $this->column($pdo, 'leadership_profiles', 'initials', "VARCHAR(12) NULL AFTER icon");
        $this->column($pdo, 'leadership_profiles', 'appointment_label', "VARCHAR(180) NULL AFTER initials");
        $this->column($pdo, 'leadership_profiles', 'appointment_source', "VARCHAR(220) NULL AFTER appointment_label");
        $this->column($pdo, 'leadership_profiles', 'quote', "TEXT NULL AFTER bio");
        $this->column($pdo, 'leadership_profiles', 'responsibilities_json', "LONGTEXT NULL AFTER quote");
        $this->column($pdo, 'leadership_profiles', 'email', "VARCHAR(180) NULL AFTER responsibilities_json");
        $this->column($pdo, 'leadership_profiles', 'phone', "VARCHAR(80) NULL AFTER email");
        $this->column($pdo, 'leadership_profiles', 'office_location', "VARCHAR(220) NULL AFTER phone");
        $this->column($pdo, 'leadership_profiles', 'photo_path', "VARCHAR(255) NULL AFTER photo_id");
        $this->column($pdo, 'leadership_profiles', 'show_in_org_chart', "TINYINT(1) NOT NULL DEFAULT 1 AFTER is_visible");
        $this->column($pdo, 'leadership_profiles', 'show_in_cards', "TINYINT(1) NOT NULL DEFAULT 1 AFTER show_in_org_chart");
        $this->column($pdo, 'leadership_profiles', 'show_in_spotlight', "TINYINT(1) NOT NULL DEFAULT 0 AFTER show_in_cards");
        $this->column($pdo, 'leadership_profiles', 'status', "ENUM('published','draft') NOT NULL DEFAULT 'published' AFTER show_in_spotlight");
        $this->column($pdo, 'leadership_profiles', 'created_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER social_links_json");
        $this->column($pdo, 'leadership_profiles', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        $this->index($pdo, 'leadership_profiles', 'idx_leadership_slug', 'slug', true);
        $this->index($pdo, 'leadership_profiles', 'idx_leadership_type_status', 'profile_type, status');

        $pdo->exec("CREATE TABLE IF NOT EXISTS contractors (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_name    VARCHAR(200) NOT NULL,
            slug            VARCHAR(220) NOT NULL UNIQUE,
            initials        VARCHAR(12) NULL,
            nca_grade       VARCHAR(80) NULL,
            contact_person  VARCHAR(160) NULL,
            email           VARCHAR(180) NULL,
            phone           VARCHAR(80) NULL,
            website         VARCHAR(255) NULL,
            project_id      INT UNSIGNED NULL,
            constituency_id INT UNSIGNED NULL,
            status          ENUM('active','tendering','inactive','completed') NOT NULL DEFAULT 'active',
            progress_pct    TINYINT UNSIGNED NOT NULL DEFAULT 0,
            quote           TEXT NULL,
            logo_path       VARCHAR(255) NULL,
            sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_contractors_project (project_id),
            KEY idx_contractors_constituency (constituency_id),
            KEY idx_contractors_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS leadership_quotes (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quote_text    TEXT NOT NULL,
            author_name   VARCHAR(160) NOT NULL,
            author_title  VARCHAR(220) NULL,
            source_type   VARCHAR(60) NOT NULL DEFAULT 'leader',
            source_id     INT UNSIGNED NULL,
            avatar_label  VARCHAR(12) NULL,
            theme         VARCHAR(40) NOT NULL DEFAULT 'green',
            status        ENUM('published','draft') NOT NULL DEFAULT 'published',
            is_featured   TINYINT(1) NOT NULL DEFAULT 1,
            sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_leadership_quotes_status (status, is_featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->column($pdo, 'stakeholders', 'slug', "VARCHAR(180) NULL AFTER id");
        $this->column($pdo, 'stakeholders', 'category', "VARCHAR(80) NOT NULL DEFAULT 'partner' AFTER role");
        $this->column($pdo, 'stakeholders', 'icon', "VARCHAR(80) NULL AFTER category");
        $this->column($pdo, 'stakeholders', 'description', "TEXT NULL AFTER icon");
        $this->column($pdo, 'stakeholders', 'logo_path', "VARCHAR(255) NULL AFTER logo_id");
        $this->column($pdo, 'stakeholders', 'status', "ENUM('published','draft') NOT NULL DEFAULT 'published' AFTER is_visible");
        $this->column($pdo, 'stakeholders', 'featured_on_leadership', "TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
        $this->column($pdo, 'stakeholders', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER featured_on_leadership");
        $this->index($pdo, 'stakeholders', 'idx_stakeholders_slug', 'slug', true);

        $this->seedLeadership($pdo);
        $this->seedContractors($pdo);
        $this->seedQuotes($pdo);
        $this->seedStakeholders($pdo);
        $this->seedCms($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS leadership_quotes');
        $pdo->exec('DROP TABLE IF EXISTS contractors');
    }

    private function seedLeadership(\PDO $pdo): void
    {
        $profiles = [
            ['william-ruto', 'national', null, 1, 'fa-star', 'WR', 'H.E. Dr. William S. Ruto', 'President of the Republic of Kenya', 'State House, Nairobi', 'Head of State', 'Presidential mandate and national programme direction.', 'Every Kenyan deserves a decent, affordable place to call home.', 10, 1, 1, 0],
            ['alice-wahome', 'national', null, 2, 'fa-landmark', 'AW', 'Hon. Alice Wahome', 'Cabinet Secretary', 'Ministry of Lands, Housing & Urban Development', 'Cabinet Secretary', 'Policy oversight, Cabinet-level accountability and inter-ministerial coordination.', 'We are building communities, not just houses.', 20, 1, 1, 0],
            ['charles-hinga', 'national', null, 3, 'fa-briefcase', 'CH', 'Eng. Charles Hinga', 'Principal Secretary', 'State Department of Housing & Urban Development', 'Principal Secretary', 'Technical programme delivery, contractor standards, Boma Yangu and county deployment.', 'No site should stall for lack of funding, standards or oversight.', 30, 1, 1, 0],
            ['affordable-housing-board', 'board', null, 4, 'fa-scale-balanced', 'AHB', 'Affordable Housing Board', 'Statutory Oversight Body', 'Established under the Affordable Housing Act, 2024', 'Statutory Board', 'Fund oversight, levy administration, unit pricing and allocation integrity.', 'Every unit, every shilling, accounted for.', 40, 1, 1, 0],
            ['county-directors', 'field', null, 4, 'fa-map-location-dot', 'CD', 'County Directors (47 Counties)', 'National Appointments', 'State Department of Housing - Field Representatives', 'National Appointments', 'Official field representation for every county.', 'County deployment keeps national delivery close to citizens.', 50, 1, 0, 0],
            ['moses-owuor', 'county', null, 5, 'fa-user-tie', 'MO', 'Moses Owuor', 'County Director of Housing - Trans-Nzoia', 'State Department of Housing - Trans-Nzoia Representative', 'Trans-Nzoia County', 'Coordinates Trans-Nzoia AHP sites, contractor liaison, public engagement and field reporting.', 'We are here, on the ground, every day ensuring the programme reaches the right people.', 60, 1, 1, 1],
            ['project-monitoring-officers', 'field', null, 6, 'fa-clipboard-list', 'PMO', 'Project Monitoring Officers', 'Field Officers', 'Site inspection and compliance reporting', 'Field Officers', 'Monitor construction progress, safety, quality and compliance records.', '', 70, 1, 0, 0],
            ['site-supervisors', 'field', null, 6, 'fa-helmet-safety', 'SS', 'Site Supervisors', 'Construction Site Supervision', 'Daily construction management', 'Construction', 'Coordinate daily works, clerk reports, attendance validation and issue escalation.', '', 80, 1, 0, 0],
            ['community-liaison-officers', 'field', null, 6, 'fa-people-group', 'CLO', 'Community Liaison Officers', 'Community Outreach', 'Beneficiary engagement and registration support', 'Outreach', 'Support public communication, beneficiary questions and community feedback.', '', 90, 1, 0, 0],
        ];

        $stmt = $pdo->prepare("INSERT INTO leadership_profiles
            (slug, profile_type, parent_id, tier, icon, initials, name, title, organisation, appointment_label, bio, quote, sort_order, show_in_org_chart, show_in_cards, show_in_spotlight, status, is_visible, responsibilities_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', 1, ?)
            ON DUPLICATE KEY UPDATE
                profile_type = VALUES(profile_type), tier = VALUES(tier), icon = VALUES(icon), initials = VALUES(initials),
                name = VALUES(name), title = VALUES(title), organisation = VALUES(organisation), appointment_label = VALUES(appointment_label),
                bio = VALUES(bio), quote = VALUES(quote), sort_order = VALUES(sort_order), show_in_org_chart = VALUES(show_in_org_chart),
                show_in_cards = VALUES(show_in_cards), show_in_spotlight = VALUES(show_in_spotlight), responsibilities_json = VALUES(responsibilities_json)");

        foreach ($profiles as $profile) {
            $responsibilities = $profile[0] === 'moses-owuor'
                ? ['Oversight of all AHP project sites in Trans-Nzoia', 'Contractor liaison and IPC compliance monitoring', 'Beneficiary registration and Boma Yangu coordination', 'Monthly progress reporting to the AHP Secretariat', 'Community and stakeholder engagement across constituencies']
                : [];
            $stmt->execute([...$profile, json_encode($responsibilities, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
        }
    }

    private function seedContractors(\PDO $pdo): void
    {
        $projectColumns = $this->tableColumns($pdo, 'projects');
        $select = ['id', 'name'];
        foreach (['constituency_id', 'pct_complete', 'contractor_name'] as $column) {
            if (in_array($column, $projectColumns, true)) {
                $select[] = $column;
            }
        }
        $projects = $pdo->query('SELECT ' . implode(', ', $select) . ' FROM projects ORDER BY id ASC LIMIT 8')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $fallbacks = [
            ['apex-construct-ltd', 'Apex Construct Ltd', 'AC', 'NCA Grade 5', 'Maili Tatu Estate', 60, 'active'],
            ['nexus-builders-kenya', 'Nexus Builders Kenya', 'NB', 'NCA Grade 5', 'Matunda AHP', 35, 'active'],
            ['buildcore-east-africa', 'BuildCore East Africa', 'BC', 'NCA Grade 6', 'Kitale Ex-Prison Site', 5, 'tendering'],
            ['landmark-structures-ltd', 'Landmark Structures Ltd', 'LS', 'NCA Grade 5', 'Suam Border Post Estate', 20, 'active'],
            ['greenfield-construction-co', 'Greenfield Construction Co.', 'GC', 'NCA Grade 5', 'Saboti Township Estate', 45, 'active'],
            ['probuild-kenya-ltd', 'ProBuild Kenya Ltd', 'PB', 'NCA Grade 5', 'Endebess Scheme', 30, 'active'],
            ['atlas-contractors-ltd', 'Atlas Contractors Ltd', 'AT', 'NCA Grade 5', 'Kwanza Units', 25, 'active'],
            ['horizon-builders-ltd', 'Horizon Builders Ltd', 'HB', 'NCA Grade 5', 'Kiminini Estate', 50, 'active'],
        ];

        $stmt = $pdo->prepare("INSERT INTO contractors
            (slug, company_name, initials, nca_grade, project_id, constituency_id, status, progress_pct, quote, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), initials = VALUES(initials), nca_grade = VALUES(nca_grade),
                project_id = VALUES(project_id), constituency_id = VALUES(constituency_id), status = VALUES(status),
                progress_pct = VALUES(progress_pct), quote = VALUES(quote), sort_order = VALUES(sort_order)");

        foreach ($fallbacks as $index => $fallback) {
            $project = $projects[$index] ?? [];
            $name = trim((string)($project['contractor_name'] ?? '')) !== '' ? (string)$project['contractor_name'] : $fallback[1];
            $slug = $this->slug($name);
            $stmt->execute([
                $slug,
                $name,
                $this->initials($name),
                $fallback[3],
                $project['id'] ?? null,
                $project['constituency_id'] ?? null,
                $fallback[6],
                (int)($project['pct_complete'] ?? $fallback[5]),
                "Quality, safety and transparent delivery remain our operating standard on every Trans-Nzoia AHP site.",
                ($index + 1) * 10,
            ]);
        }
    }

    private function seedQuotes(\PDO $pdo): void
    {
        $quotes = [
            ['The Affordable Housing Programme is economic justice for ordinary Kenyan families.', 'H.E. Dr. William S. Ruto', 'President of Kenya', 'leader', 'WR', 'gold', 10],
            ['Trans-Nzoia is one of the counties where the national housing programme is delivering visible, measurable progress.', 'Moses Owuor', 'County Director of Housing, Trans-Nzoia', 'leader', 'MO', 'lime', 20],
            ['The Secretariat is committed to ensuring no site stalls for lack of funding or oversight.', 'Eng. Charles Hinga', 'Principal Secretary, State Department of Housing', 'leader', 'CH', 'teal', 30],
            ['Quality is non-negotiable. Every slab and every column must be inspected and certified before we proceed.', 'AHP Contractor Representative', 'Contractor - Trans-Nzoia Programme', 'contractor', 'AC', 'green', 40],
        ];

        $stmt = $pdo->prepare("INSERT INTO leadership_quotes (quote_text, author_name, author_title, source_type, avatar_label, theme, sort_order, status, is_featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'published', 1)");
        $exists = (int)$pdo->query("SELECT COUNT(*) FROM leadership_quotes")->fetchColumn();
        if ($exists > 0) {
            return;
        }
        foreach ($quotes as $quote) {
            $stmt->execute($quote);
        }
    }

    private function seedStakeholders(\PDO $pdo): void
    {
        $partners = [
            ['state-dept-housing', 'State Dept. of Housing', 'Programme Lead & Secretariat', 'government', 'fa-building-columns', 'National programme leadership and housing delivery secretariat.', 'https://www.ardhi.go.ke', 10],
            ['affordable-housing-board', 'Affordable Housing Board', 'Statutory Fund & Levy Administrator', 'statutory', 'fa-scale-balanced', 'Affordable Housing Fund oversight, pricing and allocation integrity.', 'https://ahb.go.ke', 20],
            ['nema', 'NEMA', 'Environmental Impact Assessment', 'regulatory', 'fa-leaf', 'Environmental assessment and approvals for project sites.', 'https://www.nema.go.ke', 30],
            ['nca', 'NCA', 'Contractor Registration & Standards', 'regulatory', 'fa-helmet-safety', 'Contractor registration, compliance and safety standards.', 'https://www.nca.go.ke', 40],
            ['kmrc', 'KMRC', 'Mortgage Refinancing & Subsidy', 'finance', 'fa-piggy-bank', 'Long-term mortgage refinancing support.', 'https://www.kmrc.co.ke', 50],
            ['boma-yangu-ecitizen', 'Boma Yangu / eCitizen', 'Application & Ballot Platform', 'digital', 'fa-laptop-code', 'Beneficiary registration, application and allocation portal.', 'https://bomayangu.go.ke', 60],
        ];

        $stmt = $pdo->prepare("INSERT INTO stakeholders
            (slug, organisation, role, category, icon, description, website, sort_order, is_visible, status, featured_on_leadership)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'published', 1)
            ON DUPLICATE KEY UPDATE organisation = VALUES(organisation), role = VALUES(role), category = VALUES(category),
                icon = VALUES(icon), description = VALUES(description), website = VALUES(website), sort_order = VALUES(sort_order),
                is_visible = 1, status = 'published', featured_on_leadership = 1");
        foreach ($partners as $partner) {
            $stmt->execute($partner);
        }
    }

    private function seedCms(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);
        $sections = [
            ['leadership_hero', 'Hero', 'leadership_hero', 10, [
                'background_image' => 'uploads/heroes/hero-main.jpg',
                'background_alt' => 'Affordable housing construction leadership site visit in Trans-Nzoia County',
                'eyebrow' => 'National Government - State Dept. of Housing & Urban Development',
                'title' => "The People Delivering Trans-Nzoia's Housing Future",
                'subtitle' => 'A nationally-led programme with dedicated field representation in Trans-Nzoia County - from Cabinet level to construction site supervisors, every tier accountable.',
                'active_projects_label' => 'Active Projects',
                'units_label' => 'Units Planned',
                'constituencies_label' => 'Constituencies',
                'contractors_label' => 'Contractors Active',
            ]],
            ['leadership_org_chart', 'Chain of Command', 'leadership_org_chart', 20, [
                'eyebrow' => 'Chain of Command',
                'title' => 'From National Government to Your Doorstep',
                'subtitle' => 'The programme flows from national policy oversight to field representatives on the ground in Trans-Nzoia County.',
                'empty_text' => 'Add published leadership profiles to populate the hierarchy.',
            ]],
            ['leadership_national', 'Senior Officials', 'leadership_national', 30, [
                'eyebrow' => 'National Leadership',
                'title' => 'Senior Officials Driving the Programme',
                'subtitle' => 'Published leadership profiles marked for cards appear in this section.',
                'display_count' => '4',
            ]],
            ['leadership_spotlight', 'County Director Spotlight', 'leadership_spotlight', 40, [
                'eyebrow' => 'County Field Representative',
                'fallback_title' => 'County Director profile will appear here',
                'contact_button_label' => 'Contact the Field Office',
                'contact_button_url' => 'contact.php',
            ]],
            ['leadership_contractors', 'Contractor Showcase', 'leadership_contractors', 50, [
                'eyebrow' => 'Contractors on the Ground',
                'title' => "Building Trans-Nzoia's Affordable Homes",
                'subtitle' => 'NCA-registered contractors are delivering project sites across Trans-Nzoia County.',
                'display_count' => '8',
                'disclaimer' => 'All contractors are NCA-registered and procured through open competitive tendering under the Public Procurement & Asset Disposal Act, 2015.',
            ]],
            ['leadership_quotes', 'Quotes', 'leadership_quotes', 60, [
                'eyebrow' => 'In Their Own Words',
                'title' => 'What Our Leaders & Builders Say',
                'empty_text' => 'Published quotes will appear here.',
            ]],
            ['leadership_partners', 'Implementing Partners', 'leadership_partners', 70, [
                'eyebrow' => 'Implementing Partners',
                'title' => 'The Organisations Behind the Programme',
                'subtitle' => 'Partner cards are pulled from the stakeholder registry.',
            ]],
            ['leadership_contact_cta', 'Field Office CTA', 'leadership_contact_cta', 80, [
                'eyebrow' => 'Trans-Nzoia Field Office',
                'title' => 'Reach the Programme Leadership',
                'subtitle' => 'For project enquiries, contractor matters, or beneficiary questions, the field office is open to the public.',
                'primary_label' => 'Send a Message',
                'primary_url' => 'contact.php',
                'secondary_label' => 'Apply via Boma Yangu',
                'secondary_url' => 'https://bomayangu.go.ke',
                'map_label' => 'Ardhi House',
                'map_sublabel' => "County Commissioner's Premises, Kitale",
            ]],
        ];

        foreach ($sections as [$key, $label, $type, $sort, $content]) {
            $this->section($pdo, $pageId, $key, $label, $type, $sort, $content);
        }
    }

    private function pageId(\PDO $pdo): int
    {
        $pdo->prepare("INSERT INTO cms_pages (slug, template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url, hero_image)
            VALUES ('leadership', 'content', 'leadership.php', 'published', ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE template = 'content', route_path = 'leadership.php', status = 'published', hero_image = VALUES(hero_image)")
            ->execute([
                'Programme Leadership | Trans-Nzoia AHP Tracker',
                'Meet the national government officials, county field office, contractors and partner organisations delivering affordable housing in Trans-Nzoia County.',
                'Trans-Nzoia affordable housing leadership, AHP Kenya, State Department Housing, county director housing',
                'https://housing.transnzoia.go.ke/leadership.php',
                'uploads/heroes/hero-main.jpg',
            ]);

        return (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'leadership' LIMIT 1")->fetchColumn();
    }

    private function section(\PDO $pdo, int $pageId, string $key, string $label, string $type, int $sort, array $content): void
    {
        $stmt = $pdo->prepare("INSERT INTO cms_sections (page_id, section_key, label, section_type, sort_order, editor_mode, is_visible, is_locked, content_json)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)
            ON DUPLICATE KEY UPDATE label = VALUES(label), section_type = VALUES(section_type), sort_order = VALUES(sort_order), editor_mode = VALUES(editor_mode), is_visible = 1, is_locked = 1");
        $stmt->execute([$pageId, $key, $label, $type, $sort, $type, json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
    }

    private function column(\PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function tableColumns(\PDO $pdo, string $table): array
    {
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
    }

    private function index(\PDO $pdo, string $table, string $index, string $columns, bool $unique = false): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            $type = $unique ? 'UNIQUE INDEX' : 'INDEX';
            $pdo->exec("ALTER TABLE `{$table}` ADD {$type} `{$index}` ({$columns})");
        }
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
        return $slug !== '' ? $slug : 'item-' . substr(sha1($value . microtime(true)), 0, 8);
    }

    private function initials(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $letters .= strtoupper(substr($part, 0, 1));
            }
            if (strlen($letters) >= 3) {
                break;
            }
        }
        return $letters !== '' ? $letters : 'AH';
    }
}
