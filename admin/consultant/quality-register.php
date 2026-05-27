<?php
// Consultant — Quality Register
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — All quality tests per project: concrete, compaction, slump, pass/fail
