<?php
// Clerk of Works — Defects / Snagging Log
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Photograph and log defects, assign to contractor, track resolution
