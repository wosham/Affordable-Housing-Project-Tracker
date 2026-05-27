<?php
// Clerk of Works — Messages Centre
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Direct messages, project channel for their site
