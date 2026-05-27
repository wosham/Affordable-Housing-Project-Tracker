<?php
// Clerk of Works — Non-Conformance Reports
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Raise NCRs for non-compliant work, track contractor resolution
