<?php
// API — IPCs: Contractor submits a new IPC
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 4 — Validate, insert IPC + ipc_lines, notify Clerk of Works
echo json_encode(['status' => 'stub']);
