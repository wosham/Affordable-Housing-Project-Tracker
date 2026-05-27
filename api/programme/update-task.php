<?php
// API — Programme: Update a Gantt task (dates, % complete)
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth(); Guard::role(['superadmin','manager','contractor']);
// TODO: Phase 5 — Update programme_task dates and completion
echo json_encode(['status' => 'stub']);
