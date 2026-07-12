<?php
// NOTE: Two migrations share the '077_' prefix (this file and 077_media_library_centre.php).
// Do NOT rename already-run files — the _migrations table tracks filenames.
// Future migrations must use unique sequential numbers starting at 162.

class Migration_077_FAQCentreUpgrade
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS faq_categories (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(120) NOT NULL,
            slug        VARCHAR(140) NOT NULL UNIQUE,
            icon        VARCHAR(80) NULL,
            description TEXT NULL,
            sort_order  SMALLINT UNSIGNED DEFAULT 0,
            status      ENUM('published','draft') NOT NULL DEFAULT 'published',
            created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->column(
            $pdo,
            "faq_items",
            "category_id",
            "INT UNSIGNED NULL AFTER id",
        );
        $this->column(
            $pdo,
            "faq_items",
            "slug",
            "VARCHAR(180) NULL AFTER category_id",
        );
        $this->column(
            $pdo,
            "faq_items",
            "is_popular",
            "TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order",
        );
        $this->column(
            $pdo,
            "faq_items",
            "status",
            "ENUM('published','draft') NOT NULL DEFAULT 'published' AFTER is_popular",
        );
        $this->column(
            $pdo,
            "faq_items",
            "search_keywords",
            "TEXT NULL AFTER status",
        );
        $this->column(
            $pdo,
            "faq_items",
            "created_by",
            "INT UNSIGNED NULL AFTER search_keywords",
        );
        $this->column(
            $pdo,
            "faq_items",
            "updated_by",
            "INT UNSIGNED NULL AFTER created_by",
        );
        $this->column(
            $pdo,
            "faq_items",
            "created_at",
            "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER updated_by",
        );
        $this->column(
            $pdo,
            "faq_items",
            "updated_at",
            "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        );

        $categories = [
            [
                "Eligibility",
                "eligibility",
                "fa-user-check",
                "Who qualifies for the programme and what conditions apply.",
                10,
            ],
            [
                "Application Process",
                "application",
                "fa-file-pen",
                "Step-by-step guidance on registration and applications.",
                20,
            ],
            [
                "Payments & Housing Levy",
                "payments",
                "fa-coins",
                "Housing levy, savings, refunds and repayment questions.",
                30,
            ],
            [
                "Unit Allocation",
                "allocation",
                "fa-house-circle-check",
                "How units are assigned, balloted and handed over.",
                40,
            ],
            [
                "Construction & Timeline",
                "construction",
                "fa-helmet-safety",
                "Progress updates, timelines and site quality controls.",
                50,
            ],
            [
                "Legal & Documents",
                "legal",
                "fa-scale-balanced",
                "Ownership titles, tenancy agreements and legal protections.",
                60,
            ],
            [
                "Beneficiary Rights",
                "rights",
                "fa-shield-halved",
                "Applicant rights, complaints and malpractice reporting.",
                70,
            ],
            [
                "Contact & Support",
                "contact",
                "fa-phone",
                "How to reach the AHP field office and housing desk.",
                80,
            ],
        ];

        $stmt = $pdo->prepare("INSERT INTO faq_categories (name, slug, icon, description, sort_order, status)
            VALUES (?, ?, ?, ?, ?, 'published')
            ON DUPLICATE KEY UPDATE name = VALUES(name), icon = VALUES(icon), description = VALUES(description), sort_order = VALUES(sort_order)");
        foreach ($categories as $category) {
            $stmt->execute($category);
        }

        $rows = $pdo
            ->query(
                "SELECT id, category FROM faq_items WHERE category_id IS NULL",
            )
            ->fetchAll(\PDO::FETCH_ASSOC);
        $categoryLookup = [];
        foreach (
            $pdo
                ->query("SELECT id, slug, name FROM faq_categories")
                ->fetchAll(\PDO::FETCH_ASSOC)
            as $category
        ) {
            $categoryLookup[strtolower((string) $category["slug"])] =
                (int) $category["id"];
            $categoryLookup[strtolower((string) $category["name"])] =
                (int) $category["id"];
        }
        $fallback = $categoryLookup["eligibility"] ?? null;
        $update = $pdo->prepare(
            "UPDATE faq_items SET category_id = ?, status = CASE WHEN is_visible = 1 THEN 'published' ELSE 'draft' END WHERE id = ?",
        );
        foreach ($rows as $row) {
            $key = strtolower(trim((string) ($row["category"] ?? "")));
            $update->execute([
                $categoryLookup[$key] ?? $fallback,
                (int) $row["id"],
            ]);
        }

        $this->faqCms($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);
        $pdo->prepare(
            "DELETE FROM cms_sections WHERE page_id = ? AND section_key IN ('faq_hero','faq_popular','faq_library','faq_contact_cta')",
        )->execute([$pageId]);
    }

    private function faqCms(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);

        $this->section($pdo, $pageId, "faq_hero", "FAQ Hero", "faq_hero", 10, [
            "background_image" => "uploads/heroes/hero-main.jpg",
            "background_alt" =>
                "Affordable housing construction site in Trans-Nzoia County",
            "eyebrow" => "Frequently Asked Questions",
            "title" => "Your Questions,\nAnswered.",
            "subtitle" =>
                "Everything you need to know about eligibility, applying, monthly contributions, unit allocation and your rights as an AHP beneficiary in Trans-Nzoia County.",
            "search_placeholder" => 'Search questions, e.g. "How do I apply?"',
            "questions_label" => "Questions Answered",
            "categories_label" => "Categories",
            "updates_value" => "Monthly",
            "updates_label" => "Content Updates",
        ]);

        $this->section(
            $pdo,
            $pageId,
            "faq_popular",
            "Popular Questions",
            "faq_popular",
            20,
            [
                "label" => "Popular Questions",
                "display_count" => "6",
                "empty_text" =>
                    "Mark questions as popular in the FAQ manager to populate this row.",
            ],
        );

        $this->section(
            $pdo,
            $pageId,
            "faq_library",
            "FAQ Library",
            "faq_library",
            30,
            [
                "all_label" => "All",
                "no_results_title" => "No questions match this search",
                "no_results_text" =>
                    "Try a different keyword or clear the search.",
            ],
        );

        $this->section(
            $pdo,
            $pageId,
            "faq_contact_cta",
            "Question Support CTA",
            "faq_cta",
            40,
            [
                "title" => "Still Have Questions?",
                "subtitle" =>
                    "Our county housing team is available Monday to Friday, 8am-5pm. Reach us by phone, email or in person at the Kitale field office.",
                "contact_button_label" => "Send Us a Message",
                "contact_button_url" => "contact.php",
                "apply_title" => "Ready to Apply?",
                "apply_subtitle" =>
                    "Applications are processed entirely through the Government's eCitizen portal - free, secure and available 24/7.",
                "apply_button_label" => "Apply via eCitizen",
                "apply_button_url" => "https://ecitizen.go.ke",
            ],
        );

        $stmt = $pdo->prepare("
            UPDATE cms_pages
            SET template = 'content',
                route_path = 'faq.php',
                status = 'published',
                seo_title = ?,
                seo_description = ?,
                seo_keywords = ?,
                canonical_url = ?,
                hero_image = ?
            WHERE id = ?
        ");
        $stmt->execute([
            "FAQ | Trans-Nzoia AHP Tracker",
            "Frequently asked questions about the Trans-Nzoia Affordable Housing Programme - eligibility, applications, payments, allocation, construction timelines and beneficiary rights.",
            "Trans-Nzoia affordable housing FAQ, AHP Kenya questions, housing levy Kenya, Boma Yangu application",
            "https://housing.transnzoia.go.ke/faq.php",
            "uploads/heroes/hero-main.jpg",
            $pageId,
        ]);
    }

    private function pageId(\PDO $pdo): int
    {
        $pdo->prepare(
            "
            INSERT INTO cms_pages (slug, template, route_path, status, seo_title)
            VALUES ('faq', 'content', 'faq.php', 'published', 'FAQ | AHPTC')
            ON DUPLICATE KEY UPDATE template = 'content', route_path = 'faq.php'
        ",
        )->execute();

        $stmt = $pdo->query(
            "SELECT id FROM cms_pages WHERE slug = 'faq' LIMIT 1",
        );
        return (int) $stmt->fetchColumn();
    }

    private function section(
        \PDO $pdo,
        int $pageId,
        string $key,
        string $label,
        string $type,
        int $sort,
        array $content,
    ): void {
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
            json_encode(
                $content,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        ]);
    }

    private function column(
        \PDO $pdo,
        string $table,
        string $column,
        string $definition,
    ): void {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec(
                "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}",
            );
        }
    }
}
