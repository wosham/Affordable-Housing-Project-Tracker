<?php
// API — CMS: Upload image to media library
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth(); Guard::role('superadmin');
// TODO: Phase 6 — Validate image, store in uploads/, insert into media_library
echo json_encode(['status' => 'stub']);
