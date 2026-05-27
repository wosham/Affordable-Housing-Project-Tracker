<?php
// Contractor — Requests for Information (RFIs)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — Raise RFIs, track responses from consultant/engineer
