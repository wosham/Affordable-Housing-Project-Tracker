<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => $_POST['csrf_form'] ?? 'media_library',
]);

$id = Security::cleanInt($_POST['id'] ?? 0);
$media = $id > 0 ? MediaLibrary::findDetailed($id) : null;
if (!$media) {
    Response::json(['success' => false, 'message' => 'Media item not found.'], 404);
}

$folder = MediaLibrary::normaliseFolder((string)($_POST['folder'] ?? $media['folder']));
Database::query(
    'UPDATE media_library
     SET title = ?, alt_text = ?, caption = ?, folder = ?, tags_json = ?
     WHERE id = ?',
    [
        Security::cleanString((string)($_POST['title'] ?? '')),
        Security::cleanString((string)($_POST['alt_text'] ?? '')),
        Security::cleanString((string)($_POST['caption'] ?? '')),
        $folder,
        json_encode(array_values(array_filter(array_map('trim', explode(',', (string)($_POST['tags'] ?? ''))))), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $id,
    ]
);

Logger::log('update', 'media_library', $id, ['folder' => $folder]);
Response::json(['success' => true, 'message' => 'Media details saved.']);
