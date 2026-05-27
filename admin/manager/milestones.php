<?php
// Programme Manager — Milestones Tracker
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 3 — All milestones across projects, overdue alerts, update status
