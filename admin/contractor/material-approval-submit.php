<?php
// Contractor — Submit Materials for Approval
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — Submit materials schedule for consultant approval
