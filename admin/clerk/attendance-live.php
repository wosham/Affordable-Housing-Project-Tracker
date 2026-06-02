<?php
// Clerk of Works — Live Attendance (real-time who's signed in today)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::role(RoleAccess::area('clerk'));
// TODO: Phase 4 — Real-time intern sign-in list, GPS verification status
