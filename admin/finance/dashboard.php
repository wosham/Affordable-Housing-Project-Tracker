<?php
// Finance Officer — Dashboard
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','finance']);
// TODO: Phase 3 — Approved IPCs awaiting payment, total certified, paid, balance, retention
