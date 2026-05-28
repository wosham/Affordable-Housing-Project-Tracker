<?php

class Migration_076_AboutCmsBlocks
{
    public function up(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);

        $this->section($pdo, $pageId, 'about_hero', 'About Hero', 'about_hero', 10, [
            'background_image' => 'uploads/gallery/maili-tatu-2.jpg',
            'background_alt' => 'Affordable housing construction works in Trans-Nzoia County',
            'eyebrow' => 'National Affordable Housing Programme - Trans-Nzoia County',
            'title' => 'Delivering Affordable Homes for Trans-Nzoia County Residents',
            'subtitle' => 'A government-led initiative to provide quality, subsidised housing to low-to-middle-income earners across all five constituencies - tracked transparently for every resident.',
            'primary_label' => 'View All Projects',
            'primary_url' => 'projects.php',
            'secondary_label' => 'Apply on Boma Yangu',
            'secondary_url' => 'https://bomayangu.go.ke',
            'launch_year' => '2024',
            'launch_label' => 'Programme Launch',
        ]);

        $this->section($pdo, $pageId, 'programme_overview', 'Programme Overview', 'about_overview', 20, [
            'eyebrow' => 'Programme Overview',
            'title' => 'What Is the Affordable Housing Programme?',
            'lead' => "The National Affordable Housing Programme (AHP) is a flagship initiative of the Kenya Kwanza Administration, aimed at bridging the nation's housing deficit by delivering quality, affordable units to low-to-middle-income earners.",
            'body_1' => 'In Trans-Nzoia County, the programme is implemented jointly by the State Department of Housing & Urban Development and the Trans-Nzoia County Government - Department of Land, Housing & Physical Planning. Projects span all five constituencies, from the flagship Maili Tatu Estate in Kitale to the strategic Suam Border Post Estate in Endebess.',
            'body_2' => 'Units are allocated exclusively through the Boma Yangu Portal, using a transparent ballot to ensure equitable access for all registered applicants across Trans-Nzoia County.',
            'link_label' => 'Explore all active projects',
            'link_url' => 'projects.php',
            'snapshot_title' => 'Programme at a Glance',
            'glance_1_label' => 'Launch Year',
            'glance_1_value' => '2024',
            'glance_1_url' => '',
            'glance_2_label' => 'Implementing Ministry',
            'glance_2_value' => 'State Dept. of Housing',
            'glance_2_url' => '',
            'glance_3_label' => 'Primary Funding',
            'glance_3_value' => 'National AHP Fund',
            'glance_3_url' => '',
            'glance_4_label' => 'Target Beneficiaries',
            'glance_4_value' => 'Low-to-Middle Income Earners',
            'glance_4_url' => '',
            'glance_5_label' => 'Unit Price Range',
            'glance_5_value' => 'KES 1.0M - 3.0M',
            'glance_5_url' => '',
            'glance_6_label' => 'Eligibility Portal',
            'glance_6_value' => 'bomayangu.go.ke',
            'glance_6_url' => 'https://bomayangu.go.ke',
        ]);

        $this->section($pdo, $pageId, 'core_commitments', 'Core Commitments', 'about_cards', 30, [
            'eyebrow' => 'Our Pillars',
            'title' => 'Built on Four Core Commitments',
            'subtitle' => 'Every decision, project and process is guided by four foundational principles.',
            'pillar_1_icon' => 'fa-coins',
            'pillar_1_title' => 'Affordability',
            'pillar_1_body' => 'Units priced KES 1.0M-3.0M below market rate, with mortgage products making repayments accessible for eligible households.',
            'pillar_2_icon' => 'fa-magnifying-glass-chart',
            'pillar_2_title' => 'Transparency',
            'pillar_2_body' => 'This tracker publishes construction progress, contractor identities, milestone timelines and site inspection signals for public accountability.',
            'pillar_3_icon' => 'fa-gauge-high',
            'pillar_3_title' => 'Speed of Delivery',
            'pillar_3_body' => 'Contractors are monitored through milestone-based delivery controls and certified site progress reviews.',
            'pillar_4_icon' => 'fa-people-roof',
            'pillar_4_title' => 'Community Impact',
            'pillar_4_body' => 'Estates are planned around access, services and proximity to community infrastructure so housing investment lifts neighbourhoods.',
            'families_target' => '8650',
        ]);

        $this->section($pdo, $pageId, 'programme_history', 'Programme History', 'about_timeline', 40, [
            'eyebrow' => 'Programme History',
            'title' => 'From Announcement to Active Construction',
            'subtitle' => 'A chronological record of how affordable housing came to life in Trans-Nzoia County.',
            'item_1_period' => '2022',
            'item_1_icon' => 'fa-flag',
            'item_1_title' => 'National Programme Announced',
            'item_1_body' => "President Ruto announced the Affordable Housing Programme as a flagship national commitment, targeting affordable units nationwide to address Kenya's housing deficit.",
            'item_1_status' => 'Completed',
            'item_2_period' => '2023',
            'item_2_icon' => 'fa-map-pin',
            'item_2_title' => 'Trans-Nzoia Sites Identified & Gazetted',
            'item_2_body' => 'Trans-Nzoia County Government, working with the State Department of Housing, identified and gazetted priority sites across constituencies.',
            'item_2_status' => 'Completed',
            'item_3_period' => 'Early 2024',
            'item_3_icon' => 'fa-file-signature',
            'item_3_title' => 'Contractors Appointed & Groundbreaking Ceremonies',
            'item_3_body' => 'Competitive procurement and community engagement unlocked the first construction sites and mobilisation activities.',
            'item_3_status' => 'Completed',
            'item_4_period' => '2024 - 2025',
            'item_4_icon' => 'fa-helmet-safety',
            'item_4_title' => 'Active Construction Begins Across Three Constituencies',
            'item_4_body' => 'Construction commenced across key estates, with flagship sites moving through structural frame, roofing and finishing milestones.',
            'item_4_status' => 'Completed',
            'item_5_period' => '2026 - Now',
            'item_5_icon' => 'fa-rotate',
            'item_5_title' => 'Roofing, Finishing & Planning Stage Expansion',
            'item_5_body' => 'Advanced sites are moving toward handover while planning-stage estates progress through approvals, designs and procurement.',
            'item_5_status' => 'In Progress',
            'item_6_period' => '2026 - 2028',
            'item_6_icon' => 'fa-key',
            'item_6_title' => 'Unit Handovers & Boma Yangu Allocation Ballot',
            'item_6_body' => 'Completed units will be allocated through the national Boma Yangu platform to eligible registered applicants.',
            'item_6_status' => 'Upcoming',
        ]);

        $this->section($pdo, $pageId, 'legal_framework', 'Legal Framework', 'about_legal', 50, [
            'eyebrow' => 'Legal Framework',
            'title' => 'Backed by Law & Policy',
            'subtitle' => 'A robust legal and regulatory framework ensures compliance, accountability and environmental responsibility across every project site.',
            'card_1_icon' => 'fa-scale-balanced',
            'card_1_title' => 'Affordable Housing Act, 2024',
            'card_1_body' => 'The principal legislation establishing the Affordable Housing Fund, levy and governance framework for delivery.',
            'card_1_link_label' => 'Read on Kenya Law',
            'card_1_url' => 'https://www.kenyalaw.org',
            'card_2_icon' => 'fa-book',
            'card_2_title' => 'Housing Act, CAP 117',
            'card_2_body' => 'Kenya foundational housing statute governing standards and public housing delivery.',
            'card_2_link_label' => 'Read on Kenya Law',
            'card_2_url' => 'https://www.kenyalaw.org',
            'card_3_icon' => 'fa-leaf',
            'card_3_title' => 'NEMA Environmental Approvals',
            'card_3_body' => 'Project sites require environmental review and clearance before construction starts.',
            'card_3_link_label' => 'Visit NEMA',
            'card_3_url' => 'https://www.nema.go.ke',
            'card_4_icon' => 'fa-helmet-safety',
            'card_4_title' => 'NCA Contractor Registration',
            'card_4_body' => 'Contractors must hold current registration for the scale and category of works.',
            'card_4_link_label' => 'Visit NCA',
            'card_4_url' => 'https://www.nca.go.ke',
            'card_5_icon' => 'fa-handshake',
            'card_5_title' => 'Intergovernmental Relations Act, 2012',
            'card_5_body' => 'Supports cooperation between national and county governments in coordinated delivery.',
            'card_5_link_label' => 'Read on Kenya Law',
            'card_5_url' => 'https://www.kenyalaw.org',
            'card_6_icon' => 'fa-file-contract',
            'card_6_title' => 'Public Procurement Act, 2015',
            'card_6_body' => 'Guides open, competitive and transparent procurement for construction contracts.',
            'card_6_link_label' => 'Visit PPRA',
            'card_6_url' => 'https://ppra.go.ke',
        ]);

        $this->section($pdo, $pageId, 'partners_teaser', 'Partners & Stakeholders Teaser', 'about_partners', 60, [
            'eyebrow' => 'Partners',
            'title' => 'Implementing Partners & Stakeholders',
            'subtitle' => 'Delivered through collaboration between national and county government agencies, financial institutions and regulatory bodies.',
            'display_count' => '5',
            'empty_text' => 'Published stakeholders will appear here when configured.',
        ]);

        $this->section($pdo, $pageId, 'faq_teaser', 'FAQ Teaser', 'about_faq', 70, [
            'eyebrow' => 'Common Questions',
            'title' => 'Frequently Asked Questions',
            'subtitle' => 'Quick answers to the most common questions about the programme.',
            'display_count' => '4',
            'link_label' => 'Read all frequently asked questions',
            'link_url' => 'faq.php',
        ]);

        $this->section($pdo, $pageId, 'leadership_contact', 'Leadership & Contact', 'about_leadership', 80, [
            'eyebrow' => 'National Programme Leadership',
            'title' => 'Programme Leadership',
            'subtitle' => 'This is a national government initiative delivered through the State Department of Housing & Urban Development. The Trans-Nzoia field office coordinates local programme delivery and public support.',
            'link_label' => 'Meet the full leadership team',
            'link_url' => 'leadership.php',
            'contact_title' => 'Get In Touch',
            'contact_intro' => 'Have a question about the programme or your application? Reach the housing department directly.',
            'contact_button_label' => 'Send a Message',
            'contact_button_url' => 'contact.php',
            'office_hours' => 'Monday - Friday, 8:00 AM - 5:00 PM',
        ]);

        $this->section($pdo, $pageId, 'apply_cta', 'Apply CTA', 'about_cta', 90, [
            'title' => 'Ready to Apply for Affordable Housing in Trans-Nzoia?',
            'subtitle' => 'Register on the national Boma Yangu portal, save your monthly deposit and select Trans-Nzoia as your allocation preference to join the housing ballot.',
            'primary_label' => 'Apply on Boma Yangu',
            'primary_url' => 'https://bomayangu.go.ke',
            'secondary_label' => 'View All Projects',
            'secondary_url' => 'projects.php',
        ]);

        $stmt = $pdo->prepare("
            UPDATE cms_pages
            SET template = 'content',
                route_path = 'about.php',
                status = 'published',
                seo_title = ?,
                seo_description = ?,
                seo_keywords = ?,
                canonical_url = ?,
                hero_image = ?
            WHERE id = ?
        ");
        $stmt->execute([
            'About the Programme | Trans-Nzoia AHP Tracker',
            'Learn about the Trans-Nzoia County Affordable Housing Programme - mandate, partners, legal framework and how to apply.',
            'Trans-Nzoia affordable housing, AHP Kenya, housing programme, Boma Yangu, county housing policy',
            'https://housing.transnzoia.go.ke/about.php',
            'uploads/gallery/maili-tatu-2.jpg',
            $pageId,
        ]);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);
        $pdo->prepare("DELETE FROM cms_sections WHERE page_id = ? AND section_key IN ('about_hero','programme_overview','core_commitments','programme_history','legal_framework','partners_teaser','faq_teaser','leadership_contact','apply_cta')")
            ->execute([$pageId]);
    }

    private function pageId(\PDO $pdo): int
    {
        $pdo->prepare("
            INSERT INTO cms_pages (slug, template, route_path, status, seo_title)
            VALUES ('about', 'content', 'about.php', 'published', 'About | AHPTC')
            ON DUPLICATE KEY UPDATE template = 'content', route_path = 'about.php'
        ")->execute();

        $stmt = $pdo->prepare("SELECT id FROM cms_pages WHERE slug = 'about' LIMIT 1");
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
