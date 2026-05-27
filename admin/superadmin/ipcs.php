<?php
// Super Admin — IPC Management (all projects)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role('superadmin');
// TODO: Phase 4 — View all IPCs across all projects, status pipeline, override approvals
