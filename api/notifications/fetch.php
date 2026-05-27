<?php
// API — Notifications: Fetch unread notifications for current user (polled every 30s)
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 3 — Return unread notifications count and list for bell icon
echo json_encode(['status' => 'stub', 'unread' => 0, 'items' => []]);
