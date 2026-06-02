<?php

class Migration_093_CleanLegalBodyReferences
{
    public function up(\PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, content_json FROM cms_sections WHERE section_key = "legal_document"')->fetchAll(\PDO::FETCH_ASSOC);
        $update = $pdo->prepare('UPDATE cms_sections SET content_json = ?, updated_at = NOW() WHERE id = ?');

        foreach ($rows as $row) {
            $content = json_decode((string)($row['content_json'] ?? ''), true);
            if (!is_array($content) || empty($content['body'])) {
                continue;
            }

            $body = (string)$content['body'];
            $cleaned = preg_replace([
                '/\s*Document reference:\s*<code>[^<]*<\/code>\.?/i',
                '/\s*Document reference:\s*[A-Z0-9\-]+\.?/i',
            ], '', $body) ?? $body;

            if ($cleaned === $body) {
                continue;
            }

            $content['body'] = $cleaned;
            $update->execute([
                json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                (int)$row['id'],
            ]);
        }
    }

    public function down(\PDO $pdo): void
    {
        // Body-level document references were removed intentionally.
    }
}
