<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'contractor'],
    'csrf' => false,
]);

$page = max(1, Security::cleanInt($_GET['page'] ?? 1, 1));
$perPage = min(96, max(12, Security::cleanInt($_GET['per_page'] ?? 48, 48)));
$filters = [
    'folder' => (string)($_GET['folder'] ?? ''),
    'type' => (string)($_GET['type'] ?? ''),
    'q' => (string)($_GET['q'] ?? ''),
];

if ((string)Auth::role() === 'contractor') {
    $filters['folder'] = 'site-photos';
    $filters['type'] = 'image';
    $filters['uploaded_by'] = (int)Auth::id();
}

$total = MediaLibrary::count($filters);
$items = MediaLibrary::query($filters, $perPage, ($page - 1) * $perPage);

Response::json([
    'success' => true,
    'media' => array_map('media_payload', $items),
    'stats' => MediaLibrary::stats(),
    'folders' => MediaLibrary::folders(),
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => max(1, (int)ceil($total / $perPage)),
    ],
]);

function media_payload(array $row): array
{
    $extension = (string)($row['extension'] ?: pathinfo((string)$row['filename'], PATHINFO_EXTENSION));
    return [
        'id' => (int)$row['id'],
        'title' => (string)($row['title'] ?: MediaLibrary::titleFromFilename(pathinfo((string)$row['filename'], PATHINFO_FILENAME))),
        'filename' => (string)$row['filename'],
        'original_name' => (string)$row['original_name'],
        'path' => (string)$row['path'],
        'url' => Url::asset((string)$row['path']),
        'type' => (string)$row['type'],
        'type_group' => MediaLibrary::typeGroup((string)$row['type'], $extension),
        'size' => (int)$row['size'],
        'size_label' => media_size_label((int)$row['size']),
        'width' => $row['width'] !== null ? (int)$row['width'] : null,
        'height' => $row['height'] !== null ? (int)$row['height'] : null,
        'extension' => $extension,
        'folder' => (string)$row['folder'],
        'folder_label' => MediaLibrary::folders()[$row['folder']] ?? status_label((string)$row['folder']),
        'alt_text' => (string)($row['alt_text'] ?? ''),
        'caption' => (string)($row['caption'] ?? ''),
        'uploaded_by_name' => (string)($row['uploaded_by_name'] ?? 'System'),
        'created_at' => (string)$row['created_at'],
        'created_label' => time_ago($row['created_at'] ?? null),
        'usage_count' => (int)($row['usage_count'] ?? 0),
        'is_protected' => (int)($row['is_protected'] ?? 0) === 1,
    ];
}

function media_size_label(int $bytes): string
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 1) . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}
