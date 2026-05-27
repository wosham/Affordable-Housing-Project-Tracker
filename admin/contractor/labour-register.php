<?php
// Contractor — Labour Register (daily workforce numbers)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — Daily skilled/unskilled/supervisor count per site
