<?php
// Programme Manager — Projects Overview
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 3 — All projects, constituency filter, progress overview
