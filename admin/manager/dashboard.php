<?php
// Programme Manager — Dashboard
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 3 — Kanban project board, milestone alerts, IPC forwarding queue
