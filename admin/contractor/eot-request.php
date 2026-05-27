<?php
// Contractor — Extension of Time Request
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — Submit EoT: days requested, reason, evidence
