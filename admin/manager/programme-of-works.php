<?php
// Programme Manager — Programme of Works (Gantt)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 5 — Gantt chart view per project, timeline vs actual
