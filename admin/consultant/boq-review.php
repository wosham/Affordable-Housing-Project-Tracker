<?php
// Consultant — Bill of Quantities Review
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('consultant'));
// TODO: Phase 4 — Review BoQ for assigned projects, check quantities vs certified
