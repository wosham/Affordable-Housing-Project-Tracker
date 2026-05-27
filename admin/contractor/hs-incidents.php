<?php
// Contractor — Health & Safety Incidents
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — Log H&S incidents: type, severity, persons involved, corrective action
