<?php
// Consultant — Extension of Time Review
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — Review EoT applications, recommend days to grant
