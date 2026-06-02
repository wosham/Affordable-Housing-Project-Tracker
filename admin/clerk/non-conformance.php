<?php
// Clerk of Works — Non-Conformance Reports
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('clerk'));
// TODO: Phase 5 — Raise NCRs for non-compliant work, track contractor resolution
