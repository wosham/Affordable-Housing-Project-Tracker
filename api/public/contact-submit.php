<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'csrf' => false,
]);

$name = Security::cleanString((string)($_POST['name'] ?? ''));
$email = Security::cleanEmail((string)($_POST['email'] ?? ''));
$phone = Security::cleanString((string)($_POST['phone'] ?? ''));
$subject = Security::cleanString((string)($_POST['subject'] ?? ''));
$message = trim(strip_tags((string)($_POST['message'] ?? '')));
$honeypot = trim((string)($_POST['_gotcha'] ?? ''));
$ipAddress = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
$userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$sourceUrl = substr((string)($_SERVER['HTTP_REFERER'] ?? Url::to('contact.php')), 0, 500);

if ($honeypot !== '') {
    Response::json(['success' => true, 'message' => 'Message received.']);
}

$errors = [];
if (strlen($name) < 2) {
    $errors['name'] = 'Please enter your full name.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if ($subject === '') {
    $errors['subject'] = 'Please select a subject.';
}
if (strlen($message) < 10) {
    $errors['message'] = 'Please enter at least 10 characters.';
}
if (strlen($message) > 1000) {
    $errors['message'] = 'Please keep your message under 1000 characters.';
}

if ($ipAddress !== '') {
    try {
        $limit = max(1, SystemConfig::int('public.contact_rate_limit_10m', 5));
        $recent = Database::fetch(
            'SELECT COUNT(*) AS total FROM contact_submissions WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)',
            [$ipAddress]
        );
        if ((int)($recent['total'] ?? 0) >= $limit) {
            Response::json([
                'success' => false,
                'message' => 'Please wait a few minutes before sending another message.',
            ], 429);
        }
    } catch (Throwable) {
        // Older schemas may not have ip_address yet; validation continues safely.
    }
}

$attachmentPath = null;
if (isset($_FILES['attachment']) && is_array($_FILES['attachment']) && (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $attachmentPath = contact_store_attachment($_FILES['attachment'], $errors);
}

if ($errors !== []) {
    Response::json([
        'success' => false,
        'message' => 'Please correct the highlighted fields.',
        'errors' => $errors,
    ], 422);
}

try {
    $payload = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone !== '' ? $phone : null,
        'subject' => $subject,
        'message' => $message,
    ];

    try {
        Database::query(
            'INSERT INTO contact_submissions (name, email, phone, subject, message, attachment_path, is_read, status, ip_address, user_agent, source_url)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)',
            [$payload['name'], $payload['email'], $payload['phone'], $payload['subject'], $payload['message'], $attachmentPath, 'new', $ipAddress ?: null, $userAgent ?: null, $sourceUrl ?: null]
        );
    } catch (Throwable) {
        Database::query(
            'INSERT INTO contact_submissions (name, email, phone, subject, message, is_read)
             VALUES (?, ?, ?, ?, ?, 0)',
            [$payload['name'], $payload['email'], $payload['phone'], $payload['subject'], $payload['message']]
        );
    }

    $submissionId = (int)Database::lastInsertId();
    if ($submissionId > 0 && class_exists('Notification')) {
        Notification::pushRole(
            'superadmin',
            'contact',
            'New public contact message',
            $payload['name'] . ' sent: ' . $payload['subject'],
            'admin/superadmin/contact-inbox.php'
        );
    }

    Response::json([
        'success' => true,
        'message' => 'Message received. We will respond within one business day.',
    ]);
} catch (Throwable $e) {
    Logger::error('Contact submission failed', ['error' => $e->getMessage()]);
    Response::json([
        'success' => false,
        'message' => 'Unable to send your message right now. Please try again.',
    ], 500);
}

function contact_store_attachment(array $file, array &$errors): ?string
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        $errors['attachment'] = 'The attachment could not be uploaded.';
        return null;
    }

    $size = (int)($file['size'] ?? 0);
    $maxMb = max(1, SystemConfig::int('public.contact_attachment_max_mb', 5));
    if ($size <= 0 || $size > $maxMb * 1024 * 1024) {
        $errors['attachment'] = 'Attachment must be ' . $maxMb . 'MB or smaller.';
        return null;
    }

    $original = (string)($file['name'] ?? '');
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        $errors['attachment'] = 'Only PDF, JPG and PNG attachments are allowed.';
        return null;
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    $mime = is_file($tmp) ? (mime_content_type($tmp) ?: '') : '';
    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($mime, $allowedMimes, true)) {
        $errors['attachment'] = 'Attachment type is not allowed.';
        return null;
    }

    $root = dirname(__DIR__, 2);
    $dir = $root . DIRECTORY_SEPARATOR . 'secure-uploads' . DIRECTORY_SEPARATOR . 'contact-submissions';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = 'contact-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        $errors['attachment'] = 'The attachment could not be saved.';
        return null;
    }

    return 'secure-uploads/contact-submissions/' . $filename;
}
