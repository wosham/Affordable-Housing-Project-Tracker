<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

$id = Security::cleanInt($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if (!Csrf::verify($token, 'backup')) {
    die('Invalid security token. Please go back and try again.');
}

$backup = Database::fetch('SELECT * FROM database_backups WHERE id = ?', [$id]);

if (!$backup || $backup['status'] !== 'completed') {
    die('Backup not found or not available for download.');
}

try {
    $path = DatabaseBackupService::path($backup);
    if (!is_file($path)) {
        die('The backup file is missing from secure storage.');
    }

    $filename = basename($backup['filename']);
    $size = filesize($path);

    Logger::log('download', 'database_backups', $id, ['filename' => $filename]);

    header('Content-Description: File Transfer');
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $size);

    readfile($path);
    exit;

} catch (Exception $e) {
    die('Error processing download: ' . htmlspecialchars($e->getMessage()));
}
