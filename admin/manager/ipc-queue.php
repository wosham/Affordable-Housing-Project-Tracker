<?php
// Programme Manager — IPC Forwarding Queue
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 4 — Review certified IPCs, forward to Director for final approval
