<?php
// Super Admin — Bill of Quantities (all projects)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role('superadmin');
// TODO: Phase 4 — View BoQ per project, certified vs paid quantities
