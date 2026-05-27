<?php
// Clerk of Works — Site Photos Upload
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Upload timestamped, GPS-tagged site progress photos
