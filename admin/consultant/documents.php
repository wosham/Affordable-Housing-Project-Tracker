<?php
// Consultant — Documents (drawings, specs, contracts for assigned projects)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — Browse, download, view documents for assigned projects
