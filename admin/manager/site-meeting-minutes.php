<?php
// Programme Manager — Site Meeting Minutes
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 5 — View all site meeting minutes across projects
