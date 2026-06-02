<?php

class Migration_072_CmsPublicPageScrape
{
    public function up(\PDO $pdo): void
    {
        $root = dirname(__DIR__, 3);
        $pages = [
            'home' => 'index.php',
            'about' => 'about.php',
            'projects' => 'projects.php',
            'project-detail' => 'project-detail.php',
            'constituencies' => 'constituencies.php',
            'constituency-detail' => 'constituency-detail.php',
            'news' => 'news.php',
            'news-article' => 'news-article.php',
            'gallery' => 'gallery.php',
            'faq' => 'faq.php',
            'leadership' => 'leadership.php',
            'stakeholders' => 'stakeholders.php',
            'contact' => 'contact.php',
            'sitemap' => 'sitemap.php',
            'privacy' => 'legal/privacy.php',
            'terms' => 'legal/terms.php',
            'disclaimer' => 'legal/disclaimer.php',
            'not-found' => '404.php',
            'server-error' => '500.php',
            'maintenance' => 'maintenance.php',
            'offline' => 'offline.php',
        ];

        $pageStmt = $pdo->prepare("
            INSERT INTO cms_pages (slug, template, route_path, status, seo_title, seo_description, seo_keywords, canonical_url)
            VALUES (?, ?, ?, 'published', ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE route_path = VALUES(route_path), seo_title = COALESCE(cms_pages.seo_title, VALUES(seo_title)),
                seo_description = COALESCE(cms_pages.seo_description, VALUES(seo_description)),
                seo_keywords = COALESCE(cms_pages.seo_keywords, VALUES(seo_keywords)),
                canonical_url = COALESCE(cms_pages.canonical_url, VALUES(canonical_url))
        ");
        $pageIdStmt = $pdo->prepare("SELECT id FROM cms_pages WHERE slug = ? LIMIT 1");
        $snapshotStmt = $pdo->prepare("
            INSERT INTO cms_sections (page_id, section_key, label, section_type, sort_order, editor_mode, is_visible, content_json)
            VALUES (?, 'source_snapshot', 'Frontend Source Snapshot', 'source', 9999, 'reference', 1, ?)
            ON DUPLICATE KEY UPDATE content_json = VALUES(content_json), section_type = 'source', editor_mode = 'reference'
        ");
        $legalStmt = $pdo->prepare("
            INSERT INTO cms_sections (page_id, section_key, label, section_type, sort_order, editor_mode, is_visible, content_json)
            VALUES (?, 'legal_document', ?, 'document', 10, 'document', 1, ?)
            ON DUPLICATE KEY UPDATE label = VALUES(label), section_type = 'document', editor_mode = 'document'
        ");

        foreach ($pages as $slug => $file) {
            $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
            if (!is_file($path)) {
                continue;
            }

            $source = (string)file_get_contents($path);
            $meta = $this->extractPhpMeta($source);
            $template = in_array($slug, ['privacy', 'terms', 'disclaimer'], true) ? 'legal' : null;

            $pageStmt->execute([
                $slug,
                $template,
                $file,
                $meta['pageTitle'] ?? ucwords(str_replace('-', ' ', $slug)),
                $meta['pageDescription'] ?? null,
                $meta['pageKeywords'] ?? null,
                $meta['canonicalUrl'] ?? null,
            ]);

            $pageIdStmt->execute([$slug]);
            $pageId = (int)$pageIdStmt->fetchColumn();
            if ($pageId <= 0) {
                continue;
            }

            $snapshotStmt->execute([$pageId, json_encode([
                'source_file' => $file,
                'meta' => $meta,
                'headings' => $this->extractHeadings($source),
                'sections' => $this->extractSections($source),
                'images' => $this->extractImages($source),
                'links' => $this->extractLinks($source),
                'html_snapshot' => $source,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);

            if (in_array($slug, ['privacy', 'terms', 'disclaimer'], true)) {
                $defaults = $this->legalDocumentDefaults($slug);
                $article = $defaults['body'];
                $title = $defaults['title'];
                $legalStmt->execute([$pageId, $title . ' Document', json_encode([
                    'title' => $title,
                    'subtitle' => $defaults['subtitle'],
                    'icon' => $defaults['icon'],
                    'last_updated' => $defaults['last_updated'],
                    'body' => $article,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DELETE FROM cms_sections WHERE section_key = 'legal_document'");
    }

    private function extractPhpMeta(string $source): array
    {
        $meta = [];
        foreach (['pageTitle', 'pageDescription', 'pageKeywords', 'canonicalUrl', 'activePage'] as $key) {
            if (preg_match('/\\$' . preg_quote($key, '/') . '\\s*=\\s*([\\\'"])(.*?)\\1\\s*;/s', $source, $match)) {
                $meta[$key] = html_entity_decode($match[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }
        return $meta;
    }

    private function legalDocumentDefaults(string $slug): array
    {
        $defaults = [
            'privacy' => [
                'title' => 'Privacy Policy',
                'subtitle' => 'How Trans-Nzoia County Government collects, uses, and protects your personal data in connection with the Affordable Housing Programme.',
                'icon' => 'fa-shield-halved',
                'body' => <<<'HTML'
<h2>Introduction</h2>
<p>Trans-Nzoia County Government is committed to protecting personal data submitted through the Affordable Housing Programme Tracker and related public service channels.</p>
<h2>Information We Collect</h2>
<p>We may collect contact details, enquiry details, application references, device information, and correspondence submitted through public forms or official programme workflows.</p>
<h2>How We Use Information</h2>
<p>Information is used to respond to enquiries, support programme administration, improve public services, maintain security, and comply with legal obligations.</p>
<h2>Data Sharing</h2>
<p>Data may be shared with authorised county departments, national housing agencies, service providers, or regulators where required for official programme delivery.</p>
<h2>Security and Retention</h2>
<p>Reasonable technical and organisational safeguards are applied. Records are retained only for as long as needed for programme, audit, legal, and public accountability purposes.</p>
<h2>Your Rights</h2>
<p>You may request access, correction, restriction, or deletion of personal data subject to applicable Kenyan law and official records retention requirements.</p>
<h2>Contact</h2>
<p>Privacy questions may be sent to the Department of Land, Housing and Physical Planning through the official contacts published on this website.</p>
HTML,
            ],
            'terms' => [
                'title' => 'Terms of Use',
                'subtitle' => 'Rules and conditions for using the Trans-Nzoia County Affordable Housing Programme Tracker website and digital services.',
                'icon' => 'fa-file-contract',
                'body' => <<<'HTML'
<h2>Acceptance of Terms</h2>
<p>By using this website, you agree to use the Affordable Housing Programme Tracker for lawful public information and service access purposes only.</p>
<h2>Public Information</h2>
<p>Programme data is published to support transparency and accountability. Official records remain held by the responsible county and national government offices.</p>
<h2>User Responsibilities</h2>
<p>Users must not misuse the website, attempt unauthorised access, submit false information, interfere with service availability, or reproduce content in a misleading manner.</p>
<h2>Applications and External Portals</h2>
<p>Housing applications, allocation processes, and beneficiary services may be handled through official national or county systems linked from this website.</p>
<h2>Intellectual Property</h2>
<p>Website content, photographs, documents, and design elements remain protected by applicable law unless expressly stated otherwise.</p>
<h2>Changes to Terms</h2>
<p>These terms may be updated as programme operations, legal requirements, or digital service arrangements change.</p>
<h2>Contact</h2>
<p>Questions about these terms may be sent through the official contacts published on this website.</p>
HTML,
            ],
            'disclaimer' => [
                'title' => 'Disclaimer',
                'subtitle' => 'Important limitations and qualifications on the data, information, and content published on the Trans-Nzoia County Affordable Housing Programme Tracker website.',
                'icon' => 'fa-triangle-exclamation',
                'body' => <<<'HTML'
<h2>General Notice</h2>
<p>The Affordable Housing Programme Tracker is provided for public information, transparency, and accountability. It does not replace certified government records.</p>
<h2>Project Data and Construction Progress</h2>
<p>Construction progress, unit counts, timelines, and status updates are based on available programme records and may change after site verification, approvals, or reporting updates.</p>
<h2>Financial Information</h2>
<p>Budget, contract, levy, payment, and financing information is presented for public transparency and may be subject to audit, reconciliation, or official publication cycles.</p>
<h2>Allocation and Eligibility Information</h2>
<p>Information about applications, eligibility, balloting, and allocation is general guidance only. Official eligibility decisions are made through authorised government processes.</p>
<h2>Maps and Media</h2>
<p>Maps, photographs, videos, and visual materials are published for illustration and public awareness. They may not represent final designs, boundaries, or completed works.</p>
<h2>Third-Party Content</h2>
<p>External links are provided for convenience. The county is not responsible for third-party website availability, accuracy, or content.</p>
<h2>Liability</h2>
<p>To the extent permitted by law, the county is not liable for loss arising from reliance on information published on this website without independent official verification.</p>
<h2>Contact</h2>
<p>For certified records or official clarifications, contact the Department of Land, Housing and Physical Planning through the official contacts published on this website.</p>
HTML,
            ],
        ];

        return ($defaults[$slug] ?? $defaults['disclaimer']) + ['last_updated' => '2026-01-01'];
    }

    private function extractHeadings(string $source): array
    {
        preg_match_all('/<h([1-6])\\b[^>]*>(.*?)<\\/h\\1>/is', $source, $matches, PREG_SET_ORDER);
        return array_map(static fn (array $match): array => [
            'level' => (int)$match[1],
            'text' => trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
        ], $matches);
    }

    private function extractSections(string $source): array
    {
        preg_match_all('/<section\\b([^>]*)>/is', $source, $matches, PREG_SET_ORDER);
        $sections = [];
        foreach ($matches as $index => $match) {
            $attrs = $match[1];
            $sections[] = [
                'order' => $index + 1,
                'id' => $this->attr($attrs, 'id'),
                'class' => $this->attr($attrs, 'class'),
                'aria_label' => $this->attr($attrs, 'aria-label'),
                'labelled_by' => $this->attr($attrs, 'aria-labelledby'),
            ];
        }
        return $sections;
    }

    private function extractImages(string $source): array
    {
        preg_match_all('/<img\\b([^>]*)>/is', $source, $matches, PREG_SET_ORDER);
        $images = [];
        foreach ($matches as $match) {
            $attrs = $match[1];
            $src = $this->attr($attrs, 'src');
            if ($src === '') {
                continue;
            }
            $images[] = [
                'src' => $src,
                'alt' => $this->attr($attrs, 'alt'),
                'loading' => $this->attr($attrs, 'loading'),
            ];
        }
        return $images;
    }

    private function extractLinks(string $source): array
    {
        preg_match_all('/<a\\b([^>]*)>(.*?)<\\/a>/is', $source, $matches, PREG_SET_ORDER);
        $links = [];
        foreach ($matches as $match) {
            $href = $this->attr($match[1], 'href');
            if ($href === '') {
                continue;
            }
            $links[] = [
                'href' => $href,
                'text' => trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            ];
        }
        return array_slice($links, 0, 80);
    }

    private function extractArticle(string $source): string
    {
        if (preg_match('/<article\\b[^>]*class=([\\\'"])[^\\\'"]*lg-article[^\\\'"]*\\1[^>]*>(.*?)<\\/article>/is', $source, $match)) {
            return trim($match[2]);
        }
        return '';
    }

    private function attr(string $attrs, string $name): string
    {
        if (preg_match('/\\b' . preg_quote($name, '/') . '\\s*=\\s*([\\\'"])(.*?)\\1/is', $attrs, $match)) {
            return html_entity_decode($match[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return '';
    }
}
