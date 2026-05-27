<?php
// Contractor — Equipment Register
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — Register plant/equipment on site: type, reg, dates, condition
