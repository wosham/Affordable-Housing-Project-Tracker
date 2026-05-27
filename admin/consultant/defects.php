<?php
// Consultant — Defects / Snagging Log
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — View, raise, track defects; assign to contractor for rectification
