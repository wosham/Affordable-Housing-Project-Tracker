<?php
// Programme Manager — Bill of Quantities
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 4 — Review BoQ per project
