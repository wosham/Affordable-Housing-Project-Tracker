<?php
// Contractor — Payment History
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 4 — Certified vs paid amounts, retention held, balance remaining
