<?php
// Uploader — secure file upload handler
// Validates type, size, and stores files in correct directory
class Uploader
{
    private static array $allowedImages = ['image/jpeg','image/png','image/webp','image/gif'];
    private static array $allowedDocs   = ['application/pdf','application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    private static int $maxSizeBytes = 10485760; // 10MB default

    public static function image(array $file, string $folder = 'uploads/'): array
    {
        // TODO: Phase 3 — Validate image, generate unique filename, move to folder
        return ['success' => false, 'error' => 'Not implemented'];
    }

    public static function document(array $file, string $folder = 'secure-uploads/'): array
    {
        // TODO: Phase 4 — Validate document, store in secure-uploads/
        return ['success' => false, 'error' => 'Not implemented'];
    }

    public static function sitePhoto(array $file, int $projectId): array
    {
        // TODO: Phase 5 — Validate, timestamp, store in uploads/projects/{id}/
        return ['success' => false, 'error' => 'Not implemented'];
    }
}
