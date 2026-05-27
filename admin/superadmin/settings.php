<?php
// Super Admin — System Settings
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role('superadmin');
// TODO: Phase 6 — Site config: geo-fence radius, IPC thresholds, retention %, attendance window
