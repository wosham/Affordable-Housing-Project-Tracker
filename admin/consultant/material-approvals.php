<?php
// Consultant — Material Approvals
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — Approve submitted materials schedule against specification
