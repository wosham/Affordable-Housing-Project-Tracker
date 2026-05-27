<?php
// API — Public: Handle public contact form submission (no auth required)
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
// TODO: Phase 6 — Validate, insert contact_submissions, notify Manager via email + notification
echo json_encode(['status' => 'stub']);
