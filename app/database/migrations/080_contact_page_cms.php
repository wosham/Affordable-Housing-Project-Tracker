<?php

class Migration_080_ContactPageCms
{
    public function up(\PDO $pdo): void
    {
        $this->column($pdo, 'contact_departments', 'subject_key', "VARCHAR(80) NULL AFTER name");
        $this->column($pdo, 'contact_departments', 'icon', "VARCHAR(80) NULL AFTER phone");
        $this->column($pdo, 'contact_departments', 'accent', "VARCHAR(30) NULL AFTER icon");
        $this->column($pdo, 'contact_submissions', 'attachment_path', "VARCHAR(255) NULL AFTER message");
        $this->column($pdo, 'contact_submissions', 'status', "ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new' AFTER is_read");
        $this->index($pdo, 'contact_departments', 'idx_contact_departments_visible', 'is_visible, sort_order');
        $this->index($pdo, 'contact_submissions', 'idx_contact_submissions_status', 'status, created_at');

        $this->seedSettings($pdo);
        $this->seedDepartments($pdo);
        $this->seedCmsSections($pdo);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DELETE FROM cms_sections WHERE section_key IN ('contact_hero','contact_quick_cards','contact_form','contact_office','contact_departments','contact_faq_banner')");
    }

    private function seedSettings(\PDO $pdo): void
    {
        $settings = [
            ['contact_phone', '+254 53 000 0000', 'text', 'Contact Phone', 'contact'],
            ['contact_phone_href', 'tel:+254530000000', 'text', 'Contact Phone Link', 'contact'],
            ['contact_email', 'housing@transnzoia.go.ke', 'text', 'Contact Email', 'contact'],
            ['contact_email_href', 'mailto:housing@transnzoia.go.ke', 'text', 'Contact Email Link', 'contact'],
            ['contact_whatsapp', '0700 000 000', 'text', 'WhatsApp Number', 'contact'],
            ['contact_whatsapp_href', 'https://wa.me/254700000000', 'text', 'WhatsApp Link', 'contact'],
            ['contact_office', 'Ardhi House, Kitale', 'text', 'Office Name', 'contact'],
            ['contact_address', 'Ardhi House, Moi Avenue, Kitale, Trans-Nzoia County', 'text', 'Physical Address', 'contact'],
            ['contact_postal_address', 'P.O. Box 123-30200', 'text', 'Postal Address', 'contact'],
            ['contact_maps_url', 'https://maps.google.com/?q=Kitale+Trans-Nzoia+County', 'text', 'Google Maps Link', 'contact'],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO cms_settings (`key`, value, type, label, `group`)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE label = VALUES(label), `group` = VALUES(`group`)
        ");

        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }

    private function seedDepartments(\PDO $pdo): void
    {
        $departments = [
            ['Field Operations', 'field_operations', 'Construction progress, site visits, contractor oversight', 'fieldops@transnzoia.go.ke', '+254 53 000 0001', 'fa-hard-hat', 'a', 1],
            ['Legal & Allocation', 'legal_allocation', 'Applications, balloting, title deeds, legal enquiries', 'legal@transnzoia.go.ke', '+254 53 000 0002', 'fa-scale-balanced', 'b', 2],
            ['Finance & Levy', 'finance_levy', 'Housing Levy, mortgage, refunds, payment queries', 'finance@transnzoia.go.ke', '+254 53 000 0003', 'fa-coins', 'c', 3],
            ['Communications', 'communications', 'Media, press, events, public announcements', 'comms@transnzoia.go.ke', '+254 53 000 0004', 'fa-bullhorn', 'd', 4],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO contact_departments (name, subject_key, role, email, phone, icon, accent, sort_order, is_visible)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE subject_key = VALUES(subject_key), role = VALUES(role), email = VALUES(email),
                                    phone = VALUES(phone), icon = VALUES(icon), accent = VALUES(accent),
                                    sort_order = VALUES(sort_order), is_visible = 1
        ");

        foreach ($departments as $department) {
            $stmt->execute($department);
        }
    }

    private function seedCmsSections(\PDO $pdo): void
    {
        $pageId = $this->pageId($pdo);
        if ($pageId === 0) {
            return;
        }

        $sections = [
            ['contact_hero', 'Contact Hero', 'contact_hero', 10, [
                'background_image' => 'uploads/heroes/hero-main.jpg',
                'background_alt' => 'Trans-Nzoia Affordable Housing Programme contact desk',
                'eyebrow' => 'Get in Touch',
                'title' => "We're Here\nto Help.",
                'subtitle' => 'Reach our county housing team for enquiries about the Affordable Housing Programme - applications, site progress, allocation status, or any other question.',
                'response_value' => '24 hrs',
                'response_label' => 'Response Time',
                'hours_value' => 'Mon - Fri',
                'hours_label' => '8am - 5pm EAT',
                'departments_value' => '4',
                'departments_label' => 'Departments',
            ]],
            ['contact_quick_cards', 'Quick Contact Cards', 'contact_quick_cards', 20, [
                'phone_label' => 'Call Us',
                'phone_hint' => 'Mon-Fri, 8am-5pm',
                'email_label' => 'Email Us',
                'email_hint' => 'Reply within 24 hours',
                'whatsapp_label' => 'WhatsApp',
                'whatsapp_hint' => 'Quick questions welcome',
                'visit_label' => 'Visit Us',
                'visit_hint' => 'Open in Google Maps',
            ]],
            ['contact_form', 'Message Form', 'contact_form', 30, [
                'title' => 'Send Us a Message',
                'subtitle' => 'Fill in the form below and a member of our team will get back to you within one business day.',
                'name_placeholder' => 'e.g. John Wafula',
                'phone_placeholder' => '07XX XXX XXX',
                'email_placeholder' => 'you@example.com',
                'subject_placeholder' => 'Select a subject...',
                'message_placeholder' => 'Please describe your enquiry in detail...',
                'file_label' => 'Choose file (PDF, JPG, PNG - max 5MB)',
                'privacy_note' => 'Your information is protected under our Privacy Policy and will not be shared with third parties.',
                'submit_label' => 'Send Message',
                'success_title' => 'Message Sent!',
                'success_text' => 'Thank you. We have received your message and will respond within one business day.',
                'error_text' => 'Something went wrong. Please try again or email us directly.',
            ]],
            ['contact_office', 'Office & Map', 'contact_office', 40, [
                'office_title' => 'AHP Field Office - Trans-Nzoia',
                'weekday_label' => 'Monday - Friday',
                'weekday_hours' => '8:00am - 5:00pm',
                'saturday_label' => 'Saturday',
                'saturday_hours' => '9:00am - 1:00pm',
                'holiday_label' => 'Sunday & Public Holidays',
                'holiday_hours' => 'Closed',
                'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3986.8!2d35.0062!3d1.0154!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sKitale%2C+Trans-Nzoia!5e0!3m2!1sen!2ske!4v1',
                'map_link_label' => 'Open in Google Maps',
                'helpline_title' => 'National AHB Helpline',
                'helpline_number' => '0800 723 133',
                'helpline_note' => 'Toll-free - Mon-Fri 8am-6pm',
            ]],
            ['contact_departments', 'Departments Directory', 'contact_departments', 50, [
                'eyebrow' => 'Departments',
                'title' => 'Who to Contact',
                'subtitle' => 'Reach the right team directly for faster assistance.',
                'empty_text' => 'Department contacts will appear after they are added to the contact directory.',
            ]],
            ['contact_faq_banner', 'FAQ Shortcut Banner', 'contact_faq_banner', 60, [
                'title' => 'Have a quick question?',
                'subtitle' => 'Browse our Frequently Asked Questions for instant answers.',
                'pill_1_label' => 'How do I apply?',
                'pill_1_url' => 'faq.php#q-how-apply',
                'pill_2_label' => 'What is the levy?',
                'pill_2_url' => 'faq.php#q-levy-amount',
                'pill_3_label' => 'When do units complete?',
                'pill_3_url' => 'faq.php#q-when-complete',
                'button_label' => 'View All FAQs',
                'button_url' => 'faq.php',
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

    private function pageId(\PDO $pdo): int
    {
        $pdo->prepare("
            INSERT INTO cms_pages (slug, template, route_path, status, seo_title, seo_description, canonical_url, hero_image)
            VALUES ('contact', 'contact', 'contact.php', 'published', 'Contact Us | Trans-Nzoia AHP Tracker', 'Contact the Trans-Nzoia Affordable Housing Programme team by phone, email or online form.', 'https://housing.transnzoia.go.ke/contact.php', 'uploads/heroes/hero-main.jpg')
            ON DUPLICATE KEY UPDATE template = VALUES(template), route_path = VALUES(route_path),
                                    seo_title = VALUES(seo_title), seo_description = VALUES(seo_description),
                                    canonical_url = VALUES(canonical_url), hero_image = VALUES(hero_image)
        ")->execute();

        $stmt = $pdo->query("SELECT id FROM cms_pages WHERE slug = 'contact' LIMIT 1");
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

    private function index(\PDO $pdo, string $table, string $index, string $columns): void
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
        }
    }
}
