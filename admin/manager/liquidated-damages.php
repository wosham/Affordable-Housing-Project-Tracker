<?php
// Programme Manager — Liquidated Damages Tracker
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 5 — Track LDs per contract, apply to IPCs
