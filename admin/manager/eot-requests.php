<?php
// Programme Manager — Extension of Time Requests
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','manager']);
// TODO: Phase 5 — Review EoT requests, forward to Director
