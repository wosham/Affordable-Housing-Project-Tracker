<?php

class Migration_092_RemoveLegalDocumentReferences
{
    public function up(\PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, content_json FROM cms_sections WHERE section_key = "legal_document"')->fetchAll(\PDO::FETCH_ASSOC);
        $update = $pdo->prepare('UPDATE cms_sections SET content_json = ?, updated_at = NOW() WHERE id = ?');

        foreach ($rows as $row) {
            $content = json_decode((string)($row['content_json'] ?? ''), true);
            if (!is_array($content) || !array_key_exists('document_reference', $content)) {
                continue;
            }

            unset($content['document_reference']);
            $update->execute([
                json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                (int)$row['id'],
            ]);
        }
    }

    public function down(\PDO $pdo): void
    {
        // Intentionally left empty. Document references are no longer part of the legal editor.
    }
}
