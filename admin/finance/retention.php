<?php
// Finance Officer — Retention Tracker
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','finance']);
// TODO: Phase 4 — 10% retention per contract, DLP tracking, release schedule
