<?php
// Clerk of Works — Attendance Gateway Control
// The most critical Clerk feature: open/close intern sign-in window
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 4 — Big gateway open button, auto-close at 8:40 AM, geo-fence display
