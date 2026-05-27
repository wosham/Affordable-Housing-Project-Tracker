<?php
// Intern — Site Data Entry (reviewed by Clerk before submission)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','intern']);
// TODO: Phase 5 — Simplified daily diary entry, milestone updates
