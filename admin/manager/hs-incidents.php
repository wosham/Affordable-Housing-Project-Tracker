<?php
// Programme Manager — Health & Safety Incidents
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 5 — View all H&S incidents across projects, severity, resolutions
