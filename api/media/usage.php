<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin'],
    'csrf' => false,
]);

$id = Security::cleanInt($_GET['id'] ?? 0);
Response::json([
    'success' => true,
    'usage' => $id > 0 ? MediaLibrary::usage($id) : [],
]);
