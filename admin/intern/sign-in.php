<?php
// Intern — Geo-locked Attendance Sign-in
// Checks: gateway open + time window (8:00-8:40 AM EAT) + GPS within site radius
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','intern']);
// TODO: Phase 4 — Full geo-lock implementation with server-side Haversine + time check
