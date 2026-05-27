<?php
// Intern — Dashboard (geo-locked attendance sign-in is hero widget)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','intern']);
// TODO: Phase 4 — Gateway status, geo-location check, sign-in button, my project summary
