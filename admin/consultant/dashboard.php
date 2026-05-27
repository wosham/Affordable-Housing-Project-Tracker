<?php
// Consultant / Supervising Engineer — Dashboard
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 3 — IPC inbox count, pending certifications, quality alerts, defect summary
