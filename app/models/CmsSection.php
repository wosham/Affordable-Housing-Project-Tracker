<?php

class CmsSection extends Model
{
    protected static string $table = 'cms_sections';

    public static function forPage(int $pageId): array
    {
        $rows = Database::fetchAll(
            'SELECT cs.*, CONCAT(u.first_name, " ", u.last_name) AS updated_by_name
             FROM cms_sections cs
             LEFT JOIN users u ON u.id = cs.updated_by
             WHERE cs.page_id = ?
             ORDER BY cs.sort_order ASC, cs.id ASC',
            [$pageId]
        );

        foreach ($rows as &$row) {
            $decoded = json_decode((string)($row['content_json'] ?? ''), true);
            $row['content'] = is_array($decoded) ? $decoded : [];
        }

        return $rows;
    }

    public static function findForPage(int $pageId, string $sectionKey): ?array
    {
        $row = Database::fetch(
            'SELECT * FROM cms_sections WHERE page_id = ? AND section_key = ? LIMIT 1',
            [$pageId, $sectionKey]
        );

        if (!$row) {
            return null;
        }

        $decoded = json_decode((string)($row['content_json'] ?? ''), true);
        $row['content'] = is_array($decoded) ? $decoded : [];
        return $row;
    }

    public static function upsert(int $pageId, array $data): int
    {
        $content = $data['content'] ?? [];
        $json = json_encode(is_array($content) ? $content : [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $existing = self::findForPage($pageId, (string)$data['section_key']);
        $payload = [
            $data['label'] ?? ucwords(str_replace(['_', '-'], ' ', (string)$data['section_key'])),
            $data['section_type'] ?? 'rich_text',
            (int)($data['sort_order'] ?? 0),
            $data['editor_mode'] ?? 'structured',
            (int)($data['is_visible'] ?? 1),
            (int)($data['is_locked'] ?? 0),
            $json,
            (int)Auth::id(),
        ];

        if ($existing) {
            $payload[] = (int)$existing['id'];
            Database::query(
                'UPDATE cms_sections
                 SET label = ?, section_type = ?, sort_order = ?, editor_mode = ?, is_visible = ?,
                     is_locked = ?, content_json = ?, updated_by = ?
                 WHERE id = ?',
                $payload
            );
            return (int)$existing['id'];
        }

        Database::query(
            'INSERT INTO cms_sections
                (label, section_type, sort_order, editor_mode, is_visible, is_locked, content_json, updated_by, page_id, section_key)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_merge($payload, [$pageId, $data['section_key']])
        );

        return (int)Database::lastInsertId();
    }
}
