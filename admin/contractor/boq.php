<?php
// Contractor — Bill of Quantities (view their BoQ)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 4 — View BoQ items, quantities, rates, certified vs paid
