<?php
// API — Media Library: Delete media item
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth(); Guard::role('superadmin');
// TODO: Phase 6 — Check not in use, delete file + DB record
echo json_encode(['status' => 'stub']);
