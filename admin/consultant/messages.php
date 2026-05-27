<?php
// Consultant — Messages Centre
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — Direct messages, project channels for assigned projects
