<?php
// Clerk of Works — Dashboard
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 3 — Gateway status, today's attendance, pending IPC verifications, site alerts
