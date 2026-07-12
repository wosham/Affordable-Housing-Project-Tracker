<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['intern'],
    'csrf_form' => 'intern_work',
]);

$userId = (int)Auth::id();
$projectId = Security::cleanInt($_POST['project_id'] ?? 0);

if (!InternProjectWork::canAccessProject($userId, $projectId)) {
    Response::json(['success' => false, 'message' => 'You are not assigned to this project.'], 403);
}

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    Response::json(['success' => false, 'message' => 'Choose a site photo to upload.'], 422);
}

$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::json(['success' => false, 'message' => intern_upload_error((int)$file['error'])], 422);
}

$config = $GLOBALS['app_config']['security'] ?? [];
$maxBytes = (int)($config['max_upload_bytes'] ?? 5242880);
if ((int)$file['size'] > $maxBytes) {
    Response::json(['success' => false, 'message' => 'Photo exceeds the upload limit.'], 422);
}

$original = (string)($file['name'] ?? '');
if (!Security::extensionAllowed($original, ['jpg', 'jpeg', 'png', 'webp'])) {
    Response::json(['success' => false, 'message' => 'Upload a JPG, PNG or WebP photo.'], 422);
}

$tmpPath = (string)($file['tmp_name'] ?? '');
$imageInfo = @getimagesize($tmpPath);
if (!$imageInfo) {
    Response::json(['success' => false, 'message' => 'Upload a valid site photo.'], 422);
}

$root = dirname(__DIR__, 2);
$folder = 'site-photos';
$uploadDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder;
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    Response::json(['success' => false, 'message' => 'Upload folder could not be created.'], 500);
}

$ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
$baseName = strtolower(pathinfo($original, PATHINFO_FILENAME));
$baseName = preg_replace('/[^a-z0-9_-]+/', '-', $baseName) ?: 'intern-site-photo';
$filename = $baseName . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
$destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($tmpPath, $destination)) {
    Response::json(['success' => false, 'message' => 'Photo could not be stored.'], 500);
}

$relativePath = 'uploads/' . $folder . '/' . $filename;
$caption = Security::cleanString((string)($_POST['caption'] ?? ''));
$title = $caption ?: MediaLibrary::titleFromFilename(pathinfo($original, PATHINFO_FILENAME));
$mime = (string)($imageInfo['mime'] ?? mime_content_type($destination) ?: 'image/jpeg');

Database::beginTransaction();
try {
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
            (int)$imageInfo[0],
            (int)$imageInfo[1],
            $ext,
            $title,
            $caption,
            $userId,
            $folder,
            'intern_site_photo',
        ]
    );
    $mediaId = (int)Database::lastInsertId();
    $photoId = InternProjectWork::createPhoto($userId, $projectId, $mediaId, $_POST);
    Database::commit();
} catch (Throwable $exception) {
    Database::rollBack();
    @unlink($destination);
    Logger::log('error', 'intern_site_photos', null, ['message' => $exception->getMessage()]);
    Response::json(['success' => false, 'message' => 'Photo could not be linked to your project.'], 500);
}

Logger::log('upload', 'intern_site_photos', $photoId, ['project_id' => $projectId, 'media_id' => $mediaId]);

Response::json([
    'success' => true,
    'message' => 'Site photo uploaded.',
    'photo' => Database::fetch(
        'SELECT sp.*, m.url, m.title, m.original_name FROM intern_site_photos sp JOIN media_library m ON m.id = sp.media_id WHERE sp.id = ? LIMIT 1',
        [$photoId]
    ),
]);

function intern_upload_error(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Photo exceeds the upload limit.',
        UPLOAD_ERR_PARTIAL => 'The upload was interrupted.',
        UPLOAD_ERR_NO_FILE => 'Choose a site photo to upload.',
        default => 'Upload failed.',
    };
}
