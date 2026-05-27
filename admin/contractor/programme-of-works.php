<?php
// Contractor — Programme of Works
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — View/update their project Gantt, % per task
