<?php
// Programme Manager — Messages Centre
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 5 — Direct messages, project channels, announcements inbox
