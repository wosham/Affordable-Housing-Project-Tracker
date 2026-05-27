<?php
// Contractor — Messages Centre
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 5 — Direct messages, project channel for their project
