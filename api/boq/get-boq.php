<?php
// API — BoQ: Get Bill of Quantities for a project
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 4 — Return boq_items for project_id with certified/paid quantities
echo json_encode(['status' => 'stub']);
