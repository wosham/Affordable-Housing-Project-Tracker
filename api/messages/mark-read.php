<?php
// API — Messages: Mark message(s) as read
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 5 — Update message_reads table
echo json_encode(['status' => 'stub']);
