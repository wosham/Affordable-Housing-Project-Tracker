<?php

class Uploader
{
    private static array $allowedImages = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private static array $allowedDocs = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    private static int $maxSizeBytes = 10485760;

    public static function image(array $file, string $folder = 'uploads/'): array
    {
        return self::store($file, $folder, self::$allowedImages, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    public static function document(array $file, string $folder = 'secure-uploads/'): array
    {
        return self::store($file, $folder, self::$allowedDocs, ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
    }

    public static function sitePhoto(array $file, int $projectId): array
    {
        return self::image($file, 'uploads/projects/' . max(0, $projectId) . '/');
    }

    private static function store(array $file, string $folder, array $allowedMimes, array $allowedExtensions): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => self::uploadError((int)($file['error'] ?? UPLOAD_ERR_NO_FILE))];
        }

        $original = basename((string)($file['name'] ?? 'upload'));
        $size = (int)($file['size'] ?? 0);
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($size <= 0 || $size > self::$maxSizeBytes || $tmp === '' || !is_uploaded_file($tmp)) {
            return ['success' => false, 'error' => 'Invalid or oversized file.'];
        }

        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            return ['success' => false, 'error' => 'File type is not allowed.'];
        }

        $mime = self::detectMime($tmp);
        if (!in_array($mime, $allowedMimes, true)) {
            return ['success' => false, 'error' => 'File content type is not allowed.'];
        }

        $root = dirname(__DIR__, 2);
        $folder = trim(str_replace('\\', '/', $folder), '/');
        $absoluteDir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            return ['success' => false, 'error' => 'Upload folder is not writable.'];
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmp, $absolutePath)) {
            return ['success' => false, 'error' => 'File could not be saved.'];
        }

        $relative = $folder . '/' . $filename;
        return [
            'success' => true,
            'path' => $relative,
            'url' => Url::asset($relative),
            'filename' => $filename,
            'original_name' => $original,
            'size' => $size,
            'mime' => $mime,
        ];
    }

    private static function detectMime(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $path);
                finfo_close($finfo);
                return $mime;
            }
        }

        return (string)(mime_content_type($path) ?: 'application/octet-stream');
    }

    private static function uploadError(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
            UPLOAD_ERR_PARTIAL => 'File upload was incomplete.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            default => 'File upload failed.',
        };
    }
}
