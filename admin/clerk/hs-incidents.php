<?php
// Clerk of Works — H&S Incidents
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Log H&S near-misses, injuries, fatalities on site
