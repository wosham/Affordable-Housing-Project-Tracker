<?php
// API — Messages: Send a message in a thread or create new thread
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 5 — Create message, notify recipients, support @mentions
echo json_encode(['status' => 'stub']);
