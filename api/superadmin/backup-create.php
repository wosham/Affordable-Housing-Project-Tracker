<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::exactRole('superadmin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!Csrf::verify($token, 'backup')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh.']);
    exit;
}

try {
    $result = DatabaseBackupService::create((int)Auth::id(), 'manual');
    echo json_encode(['success' => true, 'backup' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => substr($e->getMessage(), 0, 200)]);
}
