<?php
// Contractor — Progress Update (must attach photo to update %)
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','contractor']);
// TODO: Phase 3 — Update milestone %, mandatory photo upload before % can be saved
