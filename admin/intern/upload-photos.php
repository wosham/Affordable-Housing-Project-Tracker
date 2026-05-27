<?php
// Intern — Upload Site Photos
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','intern']);
// TODO: Phase 5 — Upload and caption progress photos for assigned site
