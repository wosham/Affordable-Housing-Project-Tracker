<?php
// Clerk of Works — Daily Site Diary
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Structured diary: weather, labour, plant, work done, issues, next day
