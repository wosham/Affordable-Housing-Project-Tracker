<?php

class LeadershipQuote extends Model
{
    protected static string $table = 'leadership_quotes';

    public static function ordered(array $filters = []): array
    {
        [$where, $bindings] = self::filters($filters);
        return Database::fetchAll(
            "SELECT *
             FROM leadership_quotes
             {$where}
             ORDER BY sort_order ASC, id ASC",
            $bindings
        );
    }

    public static function featured(int $limit = 6): array
    {
        return Database::fetchAll(
            "SELECT *
             FROM leadership_quotes
             WHERE status = 'published' AND is_featured = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT " . max(1, $limit)
        );
    }

    public static function stats(): array
    {
        return Database::fetch(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN is_featured = 1 AND status = 'published' THEN 1 ELSE 0 END) AS featured
             FROM leadership_quotes"
        ) ?: [];
    }

    public static function saveQuote(array $data, ?int $id = null): int
    {
        $status = strtolower((string)($data['status'] ?? 'published')) === 'draft' ? 'draft' : 'published';
        $payload = [
            'quote_text' => trim((string)($data['quote_text'] ?? '')),
            'author_name' => Security::cleanString((string)($data['author_name'] ?? '')),
            'author_title' => Security::cleanString((string)($data['author_title'] ?? '')),
            'source_type' => Security::cleanString((string)($data['source_type'] ?? 'leader')),
            'source_id' => ((int)($data['source_id'] ?? 0)) > 0 ? (int)$data['source_id'] : null,
            'avatar_label' => Security::cleanString((string)($data['avatar_label'] ?? '')),
            'theme' => Security::cleanString((string)($data['theme'] ?? 'green')),
            'status' => $status,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'sort_order' => max(0, (int)($data['sort_order'] ?? 0)),
        ];

        if ($id !== null && $id > 0) {
            self::update($id, $payload);
            return $id;
        }

        return self::create($payload);
    }

    private static function filters(array $filters): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filters['q'])) {
            $term = '%' . trim((string)$filters['q']) . '%';
            $where[] = '(quote_text LIKE ? OR author_name LIKE ? OR author_title LIKE ?)';
            array_push($bindings, $term, $term, $term);
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $bindings[] = (string)$filters['status'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $bindings];
    }
}
