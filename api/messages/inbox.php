<?php
// API — Messages: Get inbox threads for current user
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 5 — Return threads with unread count, last message preview, participants
echo json_encode(['status' => 'stub']);
