<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['POST'],
    'roles' => ['superadmin'],
    'csrf_form' => $_POST['csrf_form'] ?? 'media_library',
]);

$result = MediaLibrary::syncUploads();
Logger::log('sync', 'media_library', null, $result);

Response::json([
    'success' => true,
    'message' => 'Upload folders synced.',
    'result' => $result,
]);
