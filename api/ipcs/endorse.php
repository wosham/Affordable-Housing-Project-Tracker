<?php
// API — IPCs: Clerk of Works endorses (verifies on-site quantities)
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 4 — Update IPC status to clerk-endorsed, log ipc_approvals, notify Consultant
echo json_encode(['status' => 'stub']);
