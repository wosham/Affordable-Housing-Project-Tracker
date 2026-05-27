<?php
// API — Public: Newsletter subscription (no auth required)
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
// TODO: Phase 6 — Validate email, insert/update subscribers table
echo json_encode(['status' => 'stub']);
