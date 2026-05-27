<?php
// Finance Officer — Budget Tracker
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','finance']);
// TODO: Phase 4 — Budget vs actual per project category, overspend alerts
