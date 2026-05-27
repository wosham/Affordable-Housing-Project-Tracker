<?php
// Contractor — Dashboard
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 3 — My project overview, IPC status tracker, payment history, milestones
