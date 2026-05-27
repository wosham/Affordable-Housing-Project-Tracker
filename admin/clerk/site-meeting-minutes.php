<?php
// Clerk of Works — Site Meeting Minutes
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','clerk']);
// TODO: Phase 5 — Record site meeting minutes, attendees, action items
