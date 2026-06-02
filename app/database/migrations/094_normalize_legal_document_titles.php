<?php

class Migration_094_NormalizeLegalDocumentTitles
{
    public function up(\PDO $pdo): void
    {
        $titles = [
            'privacy' => 'Privacy Policy',
            'terms' => 'Terms of Use',
            'disclaimer' => 'Disclaimer',
        ];

        $select = $pdo->prepare("
            SELECT cs.id, cs.content_json
            FROM cms_pages cp
            INNER JOIN cms_sections cs ON cs.page_id = cp.id
            WHERE cp.slug = ? AND cs.section_key = 'legal_document'
            LIMIT 1
        ");
        $update = $pdo->prepare('UPDATE cms_sections SET content_json = ?, updated_at = NOW() WHERE id = ?');

        foreach ($titles as $slug => $title) {
            $select->execute([$slug]);
            $row = $select->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                continue;
            }

            $content = json_decode((string)($row['content_json'] ?? ''), true);
            if (!is_array($content)) {
                $content = [];
            }

            $content['title'] = $title;
            unset($content['document_reference']);

            $update->execute([
                json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                (int)$row['id'],
            ]);
        }
    }

    public function down(\PDO $pdo): void
    {
        // No rollback needed; browser-title suffixes should not live in document headings.
    }
}
