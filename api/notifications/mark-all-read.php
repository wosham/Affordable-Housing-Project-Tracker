<?php
// API — Notifications: Mark all notifications as read for current user
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
echo json_encode(['status' => 'stub']);
