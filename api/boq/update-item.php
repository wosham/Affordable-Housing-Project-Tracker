<?php
// API — BoQ: Update a BoQ item (certified qty, status)
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth(); Guard::role(['superadmin','consultant','clerk']);
// TODO: Phase 4 — Update certified_qty, paid_qty on boq_item
echo json_encode(['status' => 'stub']);
