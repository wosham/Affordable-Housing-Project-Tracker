<?php
// API — Programme: Get Gantt chart tasks for a project
require_once dirname(__DIR__, 2) . '/app/core/bootstrap.php';
header('Content-Type: application/json');
Guard::auth();
// TODO: Phase 5 — Return programme_tasks with dependencies for Gantt rendering
echo json_encode(['status' => 'stub']);
