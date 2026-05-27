<?php
// Finance Officer — Process Payment
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','finance']);
// TODO: Phase 4 — Record payment: date, reference number, bank, amount, receipt upload
