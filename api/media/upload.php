<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => $_POST['csrf_form'] ?? 'cms_editor',
]);

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    Response::json(['success' => false, 'message' => 'No file was uploaded.'], 422);
}

$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::json(['success' => false, 'message' => media_upload_error((int)$file['error'])], 422);
}

$config = $GLOBALS['app_config']['security'] ?? [];
$maxBytes = (int)($config['max_upload_bytes'] ?? 5242880);
if ((int)$file['size'] > $maxBytes) {
    Response::json(['success' => false, 'message' => 'File exceeds the upload limit.'], 422);
}

$original = (string)($file['name'] ?? '');
$allowed = MediaLibrary::allowedExtensions();
if (!Security::extensionAllowed($original, $allowed)) {
    Response::json(['success' => false, 'message' => 'This file type is not allowed in the media library.'], 422);
}

$tmpPath = (string)($file['tmp_name'] ?? '');
$imageInfo = @getimagesize($tmpPath);
$folder = MediaLibrary::normaliseFolder((string)($_POST['folder'] ?? 'cms'));

$root = dirname(__DIR__, 2);
$uploadDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder;
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    Response::json(['success' => false, 'message' => 'Upload folder could not be created.'], 500);
}

$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
$baseName = strtolower(pathinfo($original, PATHINFO_FILENAME));
$baseName = preg_replace('/[^a-z0-9_-]+/', '-', $baseName) ?: 'cms-image';
$filename = $baseName . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
$destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($tmpPath, $destination)) {
    Response::json(['success' => false, 'message' => 'Could not move uploaded file.'], 500);
}

$relativePath = 'uploads/' . $folder . '/' . $filename;
$mime = (string)($imageInfo['mime'] ?? mime_content_type($destination) ?: 'application/octet-stream');
$altText = Security::cleanString((string)($_POST['alt_text'] ?? pathinfo($original, PATHINFO_FILENAME)));
$title = Security::cleanString((string)($_POST['title'] ?? MediaLibrary::titleFromFilename(pathinfo($original, PATHINFO_FILENAME))));
$caption = Security::cleanString((string)($_POST['caption'] ?? ''));

Database::query(
    'INSERT INTO media_library
        (filename, original_name, title, path, url, type, size, width, height, extension, alt_text, caption, uploaded_by, folder, source)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        $filename,
        $original,
        $title,
        $relativePath,
        Url::asset($relativePath),
        $mime,
        (int)filesize($destination),
        $imageInfo ? (int)$imageInfo[0] : null,
        $imageInfo ? (int)$imageInfo[1] : null,
        $ext,
        $altText,
        $caption,
        (int)Auth::id(),
        $folder,
        'upload',
    ]
);

$id = (int)Database::lastInsertId();
Logger::log('upload', 'media_library', $id, ['path' => $relativePath, 'folder' => $folder]);

Response::json([
    'success' => true,
    'message' => 'File uploaded.',
    'media' => [
        'id' => $id,
        'path' => $relativePath,
        'url' => Url::asset($relativePath),
        'filename' => $filename,
        'original_name' => $original,
        'title' => $title,
        'type' => $mime,
        'type_group' => MediaLibrary::typeGroup($mime, $ext),
        'size' => (int)filesize($destination),
        'width' => $imageInfo ? (int)$imageInfo[0] : null,
        'height' => $imageInfo ? (int)$imageInfo[1] : null,
        'alt_text' => $altText,
        'folder' => $folder,
    ],
]);

function media_upload_error(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds the upload limit.',
        UPLOAD_ERR_PARTIAL => 'The upload was interrupted.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        default => 'Upload failed.',
    };
}
