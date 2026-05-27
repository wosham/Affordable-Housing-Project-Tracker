<?php
// API — Media Library: List all media with optional folder/type filter
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth(); Guard::role('superadmin');
// TODO: Phase 6 — Return paginated media library items
echo json_encode(['status' => 'stub']);
