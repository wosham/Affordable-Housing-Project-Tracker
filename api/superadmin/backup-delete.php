<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!Csrf::verify($_POST['csrf_token'] ?? '', 'backup')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh.']);
    exit;
}

$id = Security::cleanInt($_POST['id'] ?? 0);
$backup = Database::fetch('SELECT * FROM database_backups WHERE id = ?', [$id]);

if (!$backup) {
    echo json_encode(['success' => false, 'error' => 'Backup not found.']);
    exit;
}

try {
    try {
        $path = DatabaseBackupService::path($backup);
        if ($path !== '' && is_file($path)) {
            unlink($path);
        }
    } catch (Throwable $e) {
        // Ignored if storage_key is empty/invalid
    }
    
    Database::query('DELETE FROM database_backups WHERE id = ?', [$id]);
    Logger::log('delete', 'database_backups', $id, ['filename' => basename((string)($backup['filename'] ?? ''))]);
    
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error deleting backup.']);
}
