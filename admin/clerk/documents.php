<?php
// Clerk of Works — Documents
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Upload site diaries, quality reports, photos to document centre
