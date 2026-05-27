<?php
// Programme Manager — Subcontractors Register
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 4 — View all subcontractors across projects, scope, value, status
