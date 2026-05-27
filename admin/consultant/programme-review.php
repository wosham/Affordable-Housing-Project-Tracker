<?php
// Consultant — Programme of Works Review
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — Review Gantt, flag delays, comment on tasks
