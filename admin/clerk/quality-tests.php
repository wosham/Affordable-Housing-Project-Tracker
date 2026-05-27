<?php
// Clerk of Works — Quality Tests
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Log quality tests: concrete cube, slump, compaction, results
