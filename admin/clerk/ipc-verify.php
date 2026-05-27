<?php
// Clerk of Works — IPC Verification (on-site quantity check)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 4 — Verify contractor IPC quantities on-site, endorse or flag discrepancies
