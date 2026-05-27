<?php
// Consultant — Site Reports (Clerk diaries, photos, quality tests)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','consultant']);
// TODO: Phase 5 — View site diaries, quality test results, timestamped photos
