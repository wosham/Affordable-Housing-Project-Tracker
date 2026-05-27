<?php
// Contractor — Submit New IPC
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 4 — IPC submission wizard: period, BoQ items, quantities, supporting docs
