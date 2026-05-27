<?php
// Programme Manager — Reports
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 7 — Generate constituency and programme-level reports
