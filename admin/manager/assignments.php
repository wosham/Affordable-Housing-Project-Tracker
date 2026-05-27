<?php
// Programme Manager — User Assignments (assign Clerks/Interns to projects)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 2 — Assign/unassign users to project sites
