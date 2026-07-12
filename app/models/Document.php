<?php

/**
 * Project document register helpers.
 * Upload paths remain in role centres / MediaLibrary / Uploader.
 */
class Document extends Model
{
    protected static string $table = 'documents';

    public const CATEGORIES = [
        'contract',
        'drawing',
        'spec',
        'report',
        'correspondence',
        'shop-drawing',
        'quality-test',
        'general',
    ];

    public static function forProject(int $projectId, int $limit = 50, bool $includeConfidential = false): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $where = 'd.project_id = ?';
        $bindings = [$projectId];
        if (!$includeConfidential) {
            $where .= ' AND d.is_confidential = 0';
        }

        return Database::fetchAll(
            self::selectSql() . " WHERE {$where} ORDER BY d.created_at DESC, d.id DESC LIMIT " . max(1, $limit),
            $bindings
        );
    }

    public static function findDetailed(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return Database::fetch(self::selectSql() . ' WHERE d.id = ? LIMIT 1', [$id]);
    }

    public static function countForProject(int $projectId, bool $includeConfidential = false): int
    {
        if ($projectId <= 0) {
            return 0;
        }

        $where = 'project_id = ?';
        $bindings = [$projectId];
        if (!$includeConfidential) {
            $where .= ' AND is_confidential = 0';
        }

        $row = Database::fetch("SELECT COUNT(*) AS total FROM documents WHERE {$where}", $bindings) ?: [];
        return (int)($row['total'] ?? 0);
    }

    /**
     * Insert document metadata after a file has been stored on disk.
     *
     * @param array<string, mixed> $data
     */
    public static function createMeta(array $data): int
    {
        $projectId = (int)($data['project_id'] ?? 0);
        $filename = trim((string)($data['filename'] ?? $data['path'] ?? ''));
        $original = trim((string)($data['original_name'] ?? basename($filename)));
        if ($projectId <= 0 || $filename === '') {
            throw new InvalidArgumentException('Project and filename are required for a document record.');
        }

        $category = strtolower(trim((string)($data['category'] ?? 'general')));
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'general';
        }

        return (int)self::create([
            'project_id' => $projectId,
            'uploaded_by' => (int)($data['uploaded_by'] ?? 0) ?: null,
            'category' => $category,
            'clerk_document_type' => trim((string)($data['clerk_document_type'] ?? '')) ?: null,
            'filename' => $filename,
            'original_name' => $original !== '' ? $original : basename($filename),
            'size' => (int)($data['size'] ?? 0),
            'version' => (int)($data['version'] ?? 1) ?: 1,
            'site_record_date' => $data['site_record_date'] ?? null,
            'linked_record_type' => trim((string)($data['linked_record_type'] ?? '')) ?: null,
            'linked_record_id' => (int)($data['linked_record_id'] ?? 0) ?: null,
            'description' => trim((string)($data['description'] ?? '')) ?: null,
            'is_confidential' => !empty($data['is_confidential']) ? 1 : 0,
            'review_required' => !empty($data['review_required']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    private static function selectSql(): string
    {
        return "SELECT d.*,
                       p.name AS project_name,
                       CONCAT(u.first_name, ' ', u.last_name) AS uploader_name
                FROM documents d
                LEFT JOIN projects p ON p.id = d.project_id
                LEFT JOIN users u ON u.id = d.uploaded_by";
    }
}
