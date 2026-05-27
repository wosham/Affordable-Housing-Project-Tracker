<?php
// API — Projects: Update milestone status
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 3 — Update milestone status (pending/current/done), set actual_date
echo json_encode(['status' => 'stub']);
