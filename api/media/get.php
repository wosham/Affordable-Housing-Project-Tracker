<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin'],
    'csrf' => false,
]);

$id = Security::cleanInt($_GET['id'] ?? 0);
$media = $id > 0 ? MediaLibrary::findDetailed($id) : null;
if (!$media) {
    Response::json(['success' => false, 'message' => 'Media item not found.'], 404);
}

Response::json([
    'success' => true,
    'media' => [
        'id' => (int)$media['id'],
        'title' => (string)($media['title'] ?: $media['original_name']),
        'filename' => (string)$media['filename'],
        'path' => (string)$media['path'],
        'url' => Url::asset((string)$media['path']),
        'type' => (string)$media['type'],
        'folder' => (string)$media['folder'],
        'alt_text' => (string)($media['alt_text'] ?? ''),
        'caption' => (string)($media['caption'] ?? ''),
        'size' => (int)$media['size'],
        'width' => $media['width'] !== null ? (int)$media['width'] : null,
        'height' => $media['height'] !== null ? (int)$media['height'] : null,
        'uploaded_by_name' => (string)($media['uploaded_by_name'] ?? 'System'),
        'created_label' => time_ago($media['created_at'] ?? null),
        'usage_count' => (int)($media['usage_count'] ?? 0),
        'usage' => MediaLibrary::usage((int)$media['id']),
    ],
]);
