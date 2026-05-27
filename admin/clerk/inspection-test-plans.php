<?php
// Clerk of Works — Inspection & Test Plans
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Record ITP hold points, inspection results per activity
