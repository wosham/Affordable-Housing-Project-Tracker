<?php
// Contractor — Subcontractors Register
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — Register subcontractors, scope, value
