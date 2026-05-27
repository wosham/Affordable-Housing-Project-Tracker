<?php
// API — Messages: Soft-delete a message (own messages only)
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 5 — Soft delete: set is_deleted=1, hide from thread view
echo json_encode(['status' => 'stub']);
