<?php
// Consultant — Inspection & Test Plans (ITPs)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — Create, assign, record ITPs per construction activity
