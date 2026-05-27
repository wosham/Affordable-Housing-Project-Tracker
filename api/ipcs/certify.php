<?php
// API — IPCs: Consultant certifies the IPC
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 4 — Update to certified, log approval, notify Manager + Director
echo json_encode(['status' => 'stub']);
