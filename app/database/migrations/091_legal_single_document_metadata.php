<?php

class Migration_091_LegalSingleDocumentMetadata
{
    public function up(\PDO $pdo): void
    {
        $defaults = [
            'privacy' => [
                'title' => 'Privacy Policy',
                'subtitle' => 'How Trans-Nzoia County Government collects, uses, and protects your personal data in connection with the Affordable Housing Programme.',
                'icon' => 'fa-shield-halved',
                'last_updated' => '2026-01-01',
            ],
            'terms' => [
                'title' => 'Terms of Use',
                'subtitle' => 'Rules and conditions for using the Trans-Nzoia County Affordable Housing Programme Tracker website and digital services.',
                'icon' => 'fa-file-contract',
                'last_updated' => '2026-01-01',
            ],
            'disclaimer' => [
                'title' => 'Disclaimer',
                'subtitle' => 'Important limitations and qualifications on the data, information, and content published on the Trans-Nzoia County Affordable Housing Programme Tracker website.',
                'icon' => 'fa-triangle-exclamation',
                'last_updated' => '2026-01-01',
            ],
        ];

        $selectPage = $pdo->prepare('SELECT id FROM cms_pages WHERE slug = ? LIMIT 1');
        $selectSection = $pdo->prepare('SELECT id, content_json FROM cms_sections WHERE page_id = ? AND section_key = "legal_document" LIMIT 1');
        $update = $pdo->prepare('UPDATE cms_sections SET content_json = ?, editor_mode = "document", section_type = "document", updated_at = NOW() WHERE id = ?');

        foreach ($defaults as $slug => $metadata) {
            $selectPage->execute([$slug]);
            $pageId = (int)$selectPage->fetchColumn();
            if ($pageId <= 0) {
                continue;
            }

            $selectSection->execute([$pageId]);
            $section = $selectSection->fetch(\PDO::FETCH_ASSOC);
            if (!$section) {
                continue;
            }

            $content = json_decode((string)($section['content_json'] ?? ''), true);
            if (!is_array($content)) {
                $content = [];
            }

            foreach ($metadata as $key => $value) {
                if (!isset($content[$key]) || trim((string)$content[$key]) === '') {
                    $content[$key] = $value;
                }
            }

            $update->execute([
                json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                (int)$section['id'],
            ]);
        }
    }

    public function down(\PDO $pdo): void
    {
        $keys = ['subtitle', 'icon', 'last_updated'];
        $rows = $pdo->query('SELECT id, content_json FROM cms_sections WHERE section_key = "legal_document"')->fetchAll(\PDO::FETCH_ASSOC);
        $update = $pdo->prepare('UPDATE cms_sections SET content_json = ? WHERE id = ?');

        foreach ($rows as $row) {
            $content = json_decode((string)($row['content_json'] ?? ''), true);
            if (!is_array($content)) {
                continue;
            }

            foreach ($keys as $key) {
                unset($content[$key]);
            }

            $update->execute([
                json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                (int)$row['id'],
            ]);
        }
    }
}
