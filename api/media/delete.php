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

if ((int)($media['is_protected'] ?? 0) === 1) {
    Response::json(['success' => false, 'message' => 'This media item is protected.'], 422);
}

$usage = MediaLibrary::usage($id);
if ($usage && (string)($_POST['force'] ?? '') !== '1') {
    Response::json(['success' => false, 'message' => 'This file is in use. Review usage before deleting.', 'usage' => $usage], 409);
}

$root = dirname(__DIR__, 2);
$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$media['path']);
$absolute = $root . DIRECTORY_SEPARATOR . $path;
if (is_file($absolute) && str_starts_with(realpath($absolute) ?: '', realpath($root . DIRECTORY_SEPARATOR . 'uploads') ?: '')) {
    @unlink($absolute);
}

Database::query('DELETE FROM media_library WHERE id = ?', [$id]);
Logger::log('delete', 'media_library', $id, ['path' => $media['path']]);

Response::json(['success' => true, 'message' => 'Media item deleted.']);
