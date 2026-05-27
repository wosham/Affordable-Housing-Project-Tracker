<?php
// Consultant — IPC Inbox (IPCs awaiting certification)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 4 — List submitted IPCs, line-by-line quantity review tools
