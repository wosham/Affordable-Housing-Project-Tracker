<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin', 'manager', 'consultant', 'contractor', 'clerk', 'finance', 'intern'],
    'csrf' => false,
]);

$id = Security::cleanInt($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Attachment not found.');
}

$message = ContactSubmission::findDetailed($id);
$path = trim((string)($message['attachment_path'] ?? ''));
if (!$message || preg_match('#^secure-uploads/contact-submissions/[a-zA-Z0-9._-]+$#', $path) !== 1) {
    http_response_code(404);
    exit('Attachment not found.');
}

if (!ContactSubmission::canAccess($message, (int)Auth::id(), (string)(Auth::role() ?: ''))) {
    http_response_code(403);
    exit('Access denied.');
}

$root = dirname(__DIR__, 2);
$absolute = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
$allowedRoot = realpath($root . DIRECTORY_SEPARATOR . 'secure-uploads' . DIRECTORY_SEPARATOR . 'contact-submissions');
if (!$absolute || !$allowedRoot || !str_starts_with($absolute, $allowedRoot . DIRECTORY_SEPARATOR) || !is_file($absolute)) {
    http_response_code(404);
    exit('Attachment not found.');
}

$filename = basename($absolute);
$mime = mime_content_type($absolute) ?: 'application/octet-stream';
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($mime, $allowedMimes, true)) {
    $mime = 'application/octet-stream';
}

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($absolute));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
header('X-Content-Type-Options: nosniff');
readfile($absolute);
exit;
