<?php
// Clerk of Works — Equipment Check (verify plant on site)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Verify contractor's equipment register against actual on-site
