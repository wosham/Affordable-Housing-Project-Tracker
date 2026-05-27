<?php
// API — Messages: Get full message thread by ID
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 5 — Return all messages in thread, mark as read
echo json_encode(['status' => 'stub']);
