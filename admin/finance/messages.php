<?php
// Finance Officer — Messages Centre
require_once __DIR__ . '/../../app/core/bootstrap.php';
Guard::auth(); Guard::role(['superadmin','finance']);
// TODO: Phase 5 — Direct messages with Manager and Director
