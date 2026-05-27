<?php
// Finance Officer — Approved IPCs (ready for payment processing)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','finance']);
// TODO: Phase 4 — List all director-approved IPCs with amounts, ready for payment
