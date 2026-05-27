<?php
// Clerk of Works — Labour Verification (verify contractor workforce numbers)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Independently record and compare workforce numbers to contractor's
