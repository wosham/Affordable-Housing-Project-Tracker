<?php

class Migration_079_StakeholdersPageUpgrade
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS stakeholder_groups (
            id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug                  VARCHAR(80) NOT NULL UNIQUE,
            name                  VARCHAR(160) NOT NULL,
            icon                  VARCHAR(80) NULL,
            count_label           VARCHAR(80) NULL,
            summary               VARCHAR(255) NULL,
            description           TEXT NULL,
            tags_json             LONGTEXT NULL,
            cta_label             VARCHAR(120) NULL,
            cta_url               VARCHAR(255) NULL,
            legal_basis           TEXT NULL,
            responsibilities_json LONGTEXT NULL,
            reporting_lines       TEXT NULL,
            sort_order            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            status                ENUM('published','draft') NOT NULL DEFAULT 'published',
            created_at            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_stakeholder_groups_status (status, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->column($pdo, 'stakeholders', 'group_id', 'INT UNSIGNED NULL AFTER id');
        $this->column($pdo, 'stakeholders', 'mandate', 'TEXT NULL AFTER description');
        $this->column($pdo, 'stakeholders', 'partner_type', "VARCHAR(100) NULL AFTER category");
        $this->column($pdo, 'stakeholders', 'agreement_type', "VARCHAR(150) NULL AFTER partner_type");
        $this->column($pdo, 'stakeholders', 'is_formal_partner', "TINYINT(1) NOT NULL DEFAULT 0 AFTER featured_on_leadership");
        $this->column($pdo, 'stakeholders', 'featured_on_stakeholders', "TINYINT(1) NOT NULL DEFAULT 1 AFTER is_formal_partner");
        $this->column($pdo, 'stakeholders', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER featured_on_stakeholders');
        $this->index($pdo, 'stakeholders', 'idx_stakeholders_group_status', 'group_id, status, sort_order');
        $this->index($pdo, 'stakeholders', 'idx_stakeholders_formal', 'is_formal_partner, status, sort_order');

        $pdo->exec("CREATE TABLE IF NOT EXISTS stakeholder_milestones (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            group_id       INT UNSIGNED NULL,
            milestone_date DATE NULL,
            date_label     VARCHAR(80) NULL,
            title          VARCHAR(180) NOT NULL,
            summary        TEXT NULL,
            icon           VARCHAR(80) NULL,
            badge_label    VARCHAR(80) NULL,
            status         ENUM('published','draft') NOT NULL DEFAULT 'published',
            sort_order     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_stakeholder_milestones_status (status, sort_order),
            KEY idx_stakeholder_milestones_group (group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS stakeholder_testimonials (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            group_id    INT UNSIGNED NULL,
            name        VARCHAR(160) NOT NULL,
            role        VARCHAR(220) NULL,
            initials    VARCHAR(12) NULL,
            photo_path  VARCHAR(255) NULL,
            rating      TINYINT UNSIGNED NOT NULL DEFAULT 5,
            quote       TEXT NOT NULL,
            tags_json   LONGTEXT NULL,
            status      ENUM('published','draft') NOT NULL DEFAULT 'published',
            sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_stakeholder_testimonials_status (status, sort_order),
            KEY idx_stakeholder_testimonials_group (group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS stakeholder_engagement_paths (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug          VARCHAR(100) NOT NULL UNIQUE,
            title         VARCHAR(180) NOT NULL,
            icon          VARCHAR(80) NULL,
            description   TEXT NULL,
            steps_json    LONGTEXT NULL,
            button_label  VARCHAR(120) NULL,
            button_url    VARCHAR(255) NULL,
            tone          VARCHAR(40) NOT NULL DEFAULT 'standard',
            status        ENUM('published','draft') NOT NULL DEFAULT 'published',
            sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_stakeholder_engagement_status (status, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->seedGroups($pdo);
        $this->seedStakeholders($pdo);
        $this->seedMilestones($pdo);
        $this->seedTestimonials($pdo);
        $this->seedEngagement($pdo);
        $this->seedCms($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS stakeholder_engagement_paths');
        $pdo->exec('DROP TABLE IF EXISTS stakeholder_testimonials');
        $pdo->exec('DROP TABLE IF EXISTS stakeholder_milestones');
        $pdo->exec('DROP TABLE IF EXISTS stakeholder_groups');
    }

    private function seedGroups(\PDO $pdo): void
    {
        $groups = [
            ['national', 'National Government', 'fa-building-columns', '4 entities', 'Policy, funding and national mandate', 'The apex leadership setting policy, allocating funds and providing legal mandate for the Affordable Housing Programme countywide.', ['Policy', 'Funding', 'Mandate'], 'See entities', 'stakeholders.php#stakeholder-groups', 'Affordable Housing Act, national housing policy and Cabinet-level programme oversight.', ['Programme policy direction', 'Funding allocation and levy governance', 'National delivery standards', 'Reporting to national accountability organs'], 'National Ministry and State Department oversight.', 10],
            ['county', 'County Government', 'fa-map-location-dot', '3 entities', 'Land, facilitation and local governance', 'The Trans-Nzoia County Government provides land, facilitation and local governance to support programme implementation on the ground.', ['Land', 'Facilitation', 'Field Office'], 'See entities', 'stakeholders.php#stakeholder-groups', 'Intergovernmental coordination framework and county land facilitation mandate.', ['Land identification and allocation support', 'Community engagement coordination', 'County-level facilitation', 'Local issue escalation'], 'County administration and field office coordination.', 20],
            ['contractors', 'Implementing Contractors', 'fa-helmet-safety', '8 firms', 'Construction and site delivery', 'NCA-registered construction firms awarded tenders to build housing units across the five Trans-Nzoia constituencies.', ['Construction', 'NCA Certified', 'Active Tenders'], 'See firms', 'stakeholders.php#stakeholder-groups', 'Public procurement contracts, NCA registration and site supervision requirements.', ['Execute construction works', 'Maintain safety and quality compliance', 'Submit progress and IPC records', 'Resolve defects and site issues'], 'Report through site supervision, consultants and county field office.', 30],
            ['financial', 'Financial Partners', 'fa-building-columns', '4 institutions', 'Housing levy, mortgages and beneficiary payments', 'Government and private financial institutions mobilising housing levies, mortgage capital and digital payment infrastructure for beneficiaries.', ['Housing Levy', 'Mortgage', 'eCitizen'], 'See institutions', 'stakeholders.php#stakeholder-groups', 'Housing levy and mortgage refinancing framework.', ['Mobilise and account for funds', 'Support beneficiary payment pathways', 'Enable mortgage refinancing', 'Publish payment and allocation data'], 'Financial audit, treasury and programme fund reporting.', 40],
            ['oversight', 'Oversight & Regulation', 'fa-scale-balanced', '4 bodies', 'Environment, quality, audit and compliance', 'Independent statutory bodies ensuring environmental compliance, construction quality, industry standards and transparent accountability in the programme.', ['Environment', 'Standards', 'Compliance'], 'See bodies', 'stakeholders.php#stakeholder-groups', 'NEMA, NCA, public procurement and audit compliance frameworks.', ['Environmental approvals', 'Construction quality standards', 'Procurement compliance', 'Audit and impact reporting'], 'Independent statutory and audit reporting lines.', 50],
            ['community', 'Community & Beneficiaries', 'fa-people-group', '5,000+ households', 'Residents, ward reps and beneficiary voices', 'Residents, ward committees, women groups and registered beneficiaries are the ultimate reason the programme exists.', ['Beneficiaries', 'Ward Reps', 'Women Groups'], 'See more', 'stakeholders.php#community-voices', 'Public participation and beneficiary registration framework.', ['Participate in public engagement', 'Register and apply for units', 'Report issues and grievances', 'Monitor community impact'], 'Community liaison, ward administration and contact inbox.', 60],
        ];

        $stmt = $pdo->prepare("INSERT INTO stakeholder_groups
            (slug, name, icon, count_label, summary, description, tags_json, cta_label, cta_url, legal_basis, responsibilities_json, reporting_lines, sort_order, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')
            ON DUPLICATE KEY UPDATE name = VALUES(name), icon = VALUES(icon), count_label = VALUES(count_label),
                summary = VALUES(summary), description = VALUES(description), tags_json = VALUES(tags_json),
                cta_label = VALUES(cta_label), cta_url = VALUES(cta_url), legal_basis = VALUES(legal_basis),
                responsibilities_json = VALUES(responsibilities_json), reporting_lines = VALUES(reporting_lines),
                sort_order = VALUES(sort_order), status = 'published'");

        foreach ($groups as $group) {
            $stmt->execute([
                $group[0], $group[1], $group[2], $group[3], $group[4], $group[5],
                $this->json($group[6]), $group[7], $group[8], $group[9], $this->json($group[10]), $group[11], $group[12],
            ]);
        }
    }

    private function seedStakeholders(\PDO $pdo): void
    {
        $records = [
            ['state-dept-housing', 'national', 'State Dept. Housing', 'Policy & Funding Principal', 'government', 'Government Tier', 'MoU / National Mandate', 'fa-building-columns', 'National housing policy direction, programme secretariat and funding coordination.', 'Sets the national delivery framework, approves programme targets and coordinates national reporting.', 'https://www.ardhi.go.ke', 1, 10],
            ['affordable-housing-board', 'national', 'Affordable Housing Board', 'Programme Implementing Agency', 'statutory', 'Implementing Agency', 'Affordable Housing Act', 'fa-house-chimney', 'Statutory programme body overseeing the Affordable Housing Fund and beneficiary allocation integrity.', 'Administers fund governance, levy accountability and fair unit allocation controls.', 'https://ahb.go.ke', 1, 20],
            ['trans-nzoia-county', 'county', 'Trans-Nzoia County', 'Land Provision & Facilitation', 'county', 'County Partner', 'Intergovernmental MoU', 'fa-map-location-dot', 'County facilitation partner supporting land allocation, ward consultation and local coordination.', 'Provides local facilitation, ward-level engagement and issue escalation pathways.', '', 1, 30],
            ['nema', 'oversight', 'NEMA', 'Environmental Oversight', 'regulatory', 'Oversight Body', 'EIA Clearance', 'fa-leaf', 'Environmental impact assessment and mitigation oversight for programme sites.', 'Reviews environmental assessments and monitors mitigation requirements.', 'https://www.nema.go.ke', 1, 40],
            ['nca', 'oversight', 'NCA', 'Construction Quality & Standards', 'regulatory', 'Oversight Body', 'Contractor Registration', 'fa-helmet-safety', 'Contractor registration, construction standards and site safety compliance.', 'Ensures contractors meet registration, safety and technical quality obligations.', 'https://www.nca.go.ke', 1, 50],
            ['kmrc', 'financial', 'KMRC', 'Mortgage Refinancing', 'finance', 'Financial Partner', 'Mortgage Refinancing', 'fa-building-columns', 'Mortgage refinancing partner supporting affordable long-term home ownership financing.', 'Supports long-term capital flows for qualifying affordable housing mortgages.', 'https://www.kmrc.co.ke', 1, 60],
            ['boma-yangu', 'financial', 'Boma Yangu', 'Beneficiary Registration Portal', 'digital', 'Digital Platform', 'eCitizen Integration', 'fa-house-user', 'National beneficiary registration and application portal operated through eCitizen.', 'Receives beneficiary applications and supports transparent allocation workflows.', 'https://bomayangu.go.ke', 1, 70],
            ['office-of-auditor-general', 'oversight', 'Office of Auditor General', 'Financial Audit & Accountability', 'audit', 'Oversight Body', 'Audit Mandate', 'fa-magnifying-glass-chart', 'Independent public audit partner for financial accountability and reporting integrity.', 'Reviews financial stewardship and supports public accountability.', '', 1, 80],
            ['knbs', 'oversight', 'KNBS', 'Impact Data & Statistics', 'statistics', 'Data Partner', 'Statistical Reporting', 'fa-chart-simple', 'Impact data and official statistics support for programme monitoring.', 'Supports evidence-led reporting on housing delivery and beneficiary impact.', '', 1, 90],
        ];

        $stmt = $pdo->prepare("INSERT INTO stakeholders
            (slug, group_id, organisation, role, category, partner_type, agreement_type, icon, description, mandate, website, is_formal_partner, featured_on_stakeholders, featured_on_leadership, sort_order, is_visible, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, 1, 'published')
            ON DUPLICATE KEY UPDATE group_id = VALUES(group_id), organisation = VALUES(organisation), role = VALUES(role),
                category = VALUES(category), partner_type = VALUES(partner_type), agreement_type = VALUES(agreement_type),
                icon = VALUES(icon), description = VALUES(description), mandate = VALUES(mandate), website = VALUES(website),
                is_formal_partner = VALUES(is_formal_partner), featured_on_stakeholders = 1, sort_order = VALUES(sort_order),
                is_visible = 1, status = 'published'");

        foreach ($records as $record) {
            $stmt->execute([
                $record[0], $this->groupId($pdo, $record[1]), $record[2], $record[3], $record[4],
                $record[5], $record[6], $record[7], $record[8], $record[9], $record[10], $record[11], $record[12],
            ]);
        }
    }

    private function seedMilestones(\PDO $pdo): void
    {
        $records = [
            ['national', '2022-06-01', 'June 2022', 'Presidential Directive - AHP Launch', 'H.E. the President directs immediate rollout of affordable housing nationwide, including Trans-Nzoia allocation.', 'fa-flag', 'National Govt', 10],
            ['financial', '2023-01-01', 'Jan 2023', 'Housing Levy Activation', 'Housing levy takes effect, enabling collection through national payroll channels and beneficiary registration.', 'fa-coins', 'Financial', 20],
            ['county', '2023-03-01', 'Mar 2023', 'Trans-Nzoia Land Allocation MoU', 'County signs memorandum for allocation of strategic sites across the five constituencies.', 'fa-map-location-dot', 'County Govt', 30],
            ['oversight', '2023-07-01', 'Jul 2023', 'NEMA EIAs Completed', 'Environmental assessments completed for priority sites and mitigation requirements issued.', 'fa-leaf', 'Oversight', 40],
            ['community', '2023-09-01', 'Sep 2023', 'Ward Baraza Series - 5 Constituencies', 'Public participation barazas held in every constituency to gather residents feedback and design concerns.', 'fa-people-group', 'Community', 50],
            ['contractors', '2023-12-01', 'Dec 2023', 'First 4 Contracts Awarded', 'Procurement concludes for first wave of implementing contractors.', 'fa-helmet-safety', 'Contractors', 60],
            ['national', '2024-02-01', 'Feb 2024', 'Trans-Nzoia Groundbreaking Ceremony', 'Programme formally enters site mobilisation and construction preparation.', 'fa-shovel', 'National Govt', 70],
            ['oversight', '2024-06-01', 'Jun 2024', 'NCA Q1 Site Audit - All Sites Pass', 'Quality and safety inspections confirm active sites meet minimum compliance requirements.', 'fa-clipboard-check', 'Oversight', 80],
            ['community', '2025-01-01', 'Jan 2025', 'Beneficiary Balloting - Phase 1', 'First beneficiary allocation communication begins for qualifying registered applicants.', 'fa-ticket', 'Community', 90],
            ['contractors', null, 'Present', '8 Sites Active - 1,730 Units Underway', 'Active construction and procurement continue across the county pipeline.', 'fa-person-digging', 'Contractors', 100],
        ];

        if ((int)$pdo->query('SELECT COUNT(*) FROM stakeholder_milestones')->fetchColumn() > 0) {
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO stakeholder_milestones
            (group_id, milestone_date, date_label, title, summary, icon, badge_label, sort_order, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        foreach ($records as $record) {
            $stmt->execute([$this->groupId($pdo, $record[0]), $record[1], $record[2], $record[3], $record[4], $record[5], $record[6], $record[7]]);
        }
    }

    private function seedTestimonials(\PDO $pdo): void
    {
        $records = [
            ['community', 'Margaret Wanjiku', 'Registered Beneficiary - Kitale Township Ward', 'MW', 5, 'I have been a Boma Yangu applicant since 2023. When I attended the baraza in our ward, I could ask questions directly to the Field Director. They told us exactly when to expect our units. That kind of transparency gives me confidence.', ['Beneficiary', 'Ward Baraza'], 10],
            ['community', 'James Omwange', 'Ward Rep - Saboti Constituency Development Committee', 'JO', 5, 'As a ward representative I attended all five site-selection meetings. The community was genuinely heard and the Matunda site was repositioned based on drainage concerns. That is real stakeholder engagement.', ['Ward Rep', 'Site Selection'], 20],
            ['community', 'Fatuma Koech', 'Trans-Nzoia Women Housing Network - Chairperson', 'FK', 4, 'Our network lobbied for unit allocation to single mothers and women-headed households. The AHP listened and committed to this in the balloting criteria. We are seeing it being honoured.', ["Women's Group", 'Advocacy'], 30],
            ['community', 'David Mutai', 'Youth Cohort Rep - Cherangany & Tuwani Ward', 'DM', 5, 'As a 28-year-old teacher, I could never afford to own a home under normal circumstances. The Boma Yangu process was straightforward. I applied, attended the baraza, and I am ballot number 114. Progress is real.', ['Beneficiary', 'Youth Cohort'], 40],
        ];

        if ((int)$pdo->query('SELECT COUNT(*) FROM stakeholder_testimonials')->fetchColumn() > 0) {
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO stakeholder_testimonials
            (group_id, name, role, initials, rating, quote, tags_json, sort_order, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        foreach ($records as $record) {
            $stmt->execute([$this->groupId($pdo, $record[0]), $record[1], $record[2], $record[3], $record[4], $record[5], $this->json($record[6]), $record[7]]);
        }
    }

    private function seedEngagement(\PDO $pdo): void
    {
        $records = [
            ['register-beneficiary', 'Register as a Beneficiary', 'fa-house-user', 'Apply for an affordable housing unit through the national Boma Yangu portal. Trans-Nzoia residents who contribute to the Housing Levy are eligible to apply.', ['Create account on Boma Yangu', 'Upload ID, KRA PIN and payslip', 'Select Trans-Nzoia as preferred county', 'Await allocation ballot notification'], 'Apply on Boma Yangu', 'https://bomayangu.go.ke', 'standard', 10],
            ['report-site-issue', 'Report a Site Issue', 'fa-triangle-exclamation', 'Noticed a construction quality concern, delayed works or a governance issue? Submit a formal grievance through the Field Office or contact page.', ['Document the concern with date and location', 'Contact the Kitale Field Office', 'Submit via the Contact page', 'Receive response within five working days'], 'Contact Field Office', 'contact.php', 'warning', 20],
            ['attend-community-baraza', 'Attend a Community Baraza', 'fa-people-roof', 'Ward-level public participation meetings are held periodically at each project site. Residents can ask questions and provide input.', ['Watch for announcements on this tracker', 'Notify your ward administrator', 'Bring your National ID', 'Sign the attendance register'], 'See upcoming events', 'announcements.php', 'standard', 30],
            ['partner-programme', 'Partner with the Programme', 'fa-handshake', 'Institutions, NGOs, research bodies and media houses may collaborate with the Trans-Nzoia AHP through formal partnership proposals.', ['Prepare a one-page partnership concept note', "Send to the Field Director's office", 'Attend an introductory meeting', 'MoU reviewed and signed if approved'], 'Submit Proposal', 'contact.php', 'highlight', 40],
        ];

        $stmt = $pdo->prepare("INSERT INTO stakeholder_engagement_paths
            (slug, title, icon, description, steps_json, button_label, button_url, tone, sort_order, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')
            ON DUPLICATE KEY UPDATE title = VALUES(title), icon = VALUES(icon), description = VALUES(description),
                steps_json = VALUES(steps_json), button_label = VALUES(button_label), button_url = VALUES(button_url),
                tone = VALUES(tone), sort_order = VALUES(sort_order), status = 'published'");
        foreach ($records as $record) {
            $stmt->execute([$record[0], $record[1], $record[2], $record[3], $this->json($record[4]), $record[5], $record[6], $record[7], $record[8]]);
        }
    }

    private function seedCms(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);
        $sections = [
            ['stakeholders_hero', 'Hero', 'stakeholders_hero', 10, [
                'background_image' => 'uploads/heroes/hero-main.jpg',
                'background_alt' => 'Affordable housing programme site visit in Trans-Nzoia County',
                'eyebrow' => 'Programme Ecosystem - Partners, Institutions & Communities',
                'title' => "Everyone Who\nMakes It Happen",
                'subtitle' => 'A transparent map of every institution, partner, contractor, regulator and community driving the Affordable Housing Programme in Trans-Nzoia County - from national policy to ground-level delivery.',
                'kpi_1_value' => '6', 'kpi_1_label' => 'Government Tiers',
                'kpi_2_value' => '12+', 'kpi_2_label' => 'Implementing Partners',
                'kpi_3_value' => '5,000+', 'kpi_3_label' => 'Households Targeted',
                'kpi_4_value' => '4', 'kpi_4_label' => 'Oversight Bodies',
                'scroll_label' => 'Explore the Ecosystem',
            ]],
            ['stakeholders_ecosystem', 'Programme Web', 'stakeholders_ecosystem', 20, [
                'eyebrow' => 'Ecosystem Overview',
                'title' => 'The Programme Web',
                'subtitle' => 'Click any stakeholder category to highlight its role and connections within the programme delivery chain.',
                'hub_label' => 'Trans-Nzoia AHP',
                'empty_text' => 'Add published stakeholder groups to populate the ecosystem map.',
            ]],
            ['stakeholders_pillars', 'Stakeholder Groups', 'stakeholders_pillars', 30, [
                'eyebrow' => 'Stakeholder Groups',
                'title' => 'Six Pillars of Programme Delivery',
                'subtitle' => 'Every entity in the Trans-Nzoia AHP belongs to one of six interconnected stakeholder groups - each with distinct responsibilities and accountabilities.',
                'empty_text' => 'Stakeholder groups will appear here after they are published.',
            ]],
            ['stakeholders_mandates', 'Mandates', 'stakeholders_mandates', 40, [
                'eyebrow' => 'Mandates & Accountabilities',
                'title' => 'Who Does What?',
                'subtitle' => 'Expand each stakeholder group to understand its legal mandate, core responsibilities and how it interacts with other programme actors.',
            ]],
            ['stakeholders_milestones', 'Engagement Milestones', 'stakeholders_milestones', 50, [
                'eyebrow' => 'Programme Journey',
                'title' => 'Key Stakeholder Engagement Milestones',
                'subtitle' => 'From presidential directive to community baraza - a chronological record of how stakeholders have shaped this programme.',
                'empty_text' => 'Milestones will appear here after they are added to the registry.',
            ]],
            ['stakeholders_voices', 'Community Voice', 'stakeholders_voices', 60, [
                'eyebrow' => 'Community Voice',
                'title' => 'The People Behind the Programme',
                'subtitle' => 'Ward representatives, registered beneficiaries and community advocates share their experiences with the Trans-Nzoia AHP.',
                'empty_text' => 'Published community voices will appear here.',
            ]],
            ['stakeholders_formal_partners', 'Formal Partners', 'stakeholders_formal_partners', 70, [
                'eyebrow' => 'Formal Partnerships',
                'title' => 'MoU Signatories & Institutional Partners',
                'subtitle' => 'Formal implementing partners and oversight institutions with active agreements or collaborative mandates.',
                'empty_text' => 'Formal partner records will appear here after publication.',
            ]],
            ['stakeholders_engagement', 'Engage the Programme', 'stakeholders_engagement', 80, [
                'eyebrow' => 'Get Involved',
                'title' => 'How to Engage the Programme',
                'subtitle' => 'Whether you are a potential beneficiary, community leader, journalist or interested partner - here is how to connect with the Trans-Nzoia AHP.',
                'empty_text' => 'Engagement paths will appear here after they are added.',
            ]],
        ];

        foreach ($sections as [$key, $label, $type, $sort, $content]) {
            $this->section($pdo, $pageId, $key, $label, $type, $sort, $content);
        }
    }

    private function pageId(\PDO $pdo): int
    {
        $stmt = $pdo->prepare("INSERT INTO cms_pages
            (slug, route_path, template, status, seo_title, seo_description, seo_keywords, canonical_url, hero_image)
            VALUES ('stakeholders', 'stakeholders.php', 'content', 'published',
                'Stakeholders | Trans-Nzoia Affordable Housing Programme',
                'Explore the partners, institutions, regulators, contractors and communities delivering the Trans-Nzoia Affordable Housing Programme.',
                'Trans-Nzoia stakeholders, affordable housing partners, AHP contractors, housing regulators',
                'https://housing.transnzoia.go.ke/stakeholders.php',
                'uploads/heroes/hero-main.jpg')
            ON DUPLICATE KEY UPDATE route_path = VALUES(route_path), template = VALUES(template), status = VALUES(status),
                seo_title = VALUES(seo_title), seo_description = VALUES(seo_description), seo_keywords = VALUES(seo_keywords),
                canonical_url = VALUES(canonical_url), hero_image = VALUES(hero_image)");
        $stmt->execute();

        return (int)$pdo->query("SELECT id FROM cms_pages WHERE slug = 'stakeholders' LIMIT 1")->fetchColumn();
    }

    private function section(\PDO $pdo, int $pageId, string $key, string $label, string $type, int $sort, array $content): void
    {
        $row = $pdo->prepare('SELECT id FROM cms_sections WHERE page_id = ? AND section_key = ? LIMIT 1');
        $row->execute([$pageId, $key]);
        if ($row->fetchColumn()) {
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO cms_sections
            (page_id, section_key, label, section_type, editor_mode, sort_order, is_visible, is_locked, content_json)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)");
        $stmt->execute([$pageId, $key, $label, $type, $type, $sort, $this->json($content)]);
    }

    private function groupId(\PDO $pdo, string $slug): ?int
    {
        $stmt = $pdo->prepare('SELECT id FROM stakeholder_groups WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    private function column(\PDO $pdo, string $table, string $column, string $definition): void
    {
        if (in_array($column, $this->tableColumns($pdo, $table), true)) {
            return;
        }

        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }

    private function index(\PDO $pdo, string $table, string $index, string $columns, bool $unique = false): void
    {
        $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
        $stmt->execute([$index]);
        if ($stmt->fetch()) {
            return;
        }

        $pdo->exec('ALTER TABLE `' . $table . '` ADD ' . ($unique ? 'UNIQUE ' : '') . 'INDEX `' . $index . '` (' . $columns . ')');
    }

    private function tableColumns(\PDO $pdo, string $table): array
    {
        return array_map(static fn ($row) => $row['Field'], $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
