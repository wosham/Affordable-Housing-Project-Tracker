<?php

require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';

ApiMiddleware::handle([
    'methods' => ['GET'],
    'roles' => ['superadmin'],
    'csrf' => false,
]);

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    Response::json(['success' => false, 'message' => 'Page slug is required.'], 422);
}

$page = CmsPage::findBySlug($slug);
if (!$page) {
    Response::json(['success' => false, 'message' => 'CMS page not found.'], 404);
}

Response::json([
    'success' => true,
    'page' => $page,
    'sections' => CmsSection::forPage((int)$page['id']),
    'settings' => CmsSetting::grouped(),
]);
