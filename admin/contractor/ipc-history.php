<?php
// Contractor — IPC History & Status Tracker
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 4 — All submitted IPCs with pipeline status: submitted/endorsed/certified/approved/paid
