<?php
// Consultant — Shop Drawings Review
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — Review, approve/reject/request resubmission of shop drawings
